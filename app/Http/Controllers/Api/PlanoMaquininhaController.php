<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\PlanoMaquininhaRequest;
use App\Models\Bandeira;
use App\Models\PlanoMaquininha;
use Illuminate\Support\Facades\DB;

class PlanoMaquininhaController extends Controller
{
    public function index()
    {
        $lojaId = auth()->user()->loja_id;

        return response()->json(
            PlanoMaquininha::where('loja_id', $lojaId)
                ->orderByDesc('ativo')
                ->orderBy('nome')
                ->get()
        );
    }

    public function ativo()
    {
        $lojaId = auth()->user()->loja_id;

        $plano = PlanoMaquininha::with(['taxas.bandeira'])
            ->where('loja_id', $lojaId)
            ->where('ativo', true)
            ->first();

        if (!$plano) {
            return response()->json(['message' => 'Nenhum plano ativo para esta loja.'], 404);
        }

        $bandeiras = Bandeira::where('loja_id', $lojaId)
            ->where('ativo', true)
            ->orderBy('nome')
            ->get();

        $taxasMap = [];
        foreach ($plano->taxas as $t) {
            $taxasMap[$t->bandeira_id][$t->modalidade] = $t->taxa !== null ? (float) $t->taxa : null;
        }

        return response()->json([
            'plano' => [
                'id' => $plano->id,
                'nome' => $plano->nome,
                'taxa_antecipacao' => $plano->taxa_antecipacao !== null ? (float) $plano->taxa_antecipacao : null,
            ],
            'bandeiras' => $bandeiras->map(fn ($b) => [
                'id' => $b->id,
                'nome' => $b->nome,
                'taxas' => $taxasMap[$b->id] ?? [],
            ]),
        ]);
    }

    public function show(PlanoMaquininha $plano)
    {
        $this->autorizarLoja($plano);

        $lojaId = auth()->user()->loja_id;
        $bandeiras = Bandeira::where('loja_id', $lojaId)->orderBy('nome')->get();

        $taxasMap = [];
        foreach ($plano->taxas as $t) {
            $taxasMap[$t->bandeira_id][$t->modalidade] = $t->taxa !== null ? (float) $t->taxa : null;
        }

        return response()->json([
            'plano' => $plano,
            'bandeiras' => $bandeiras->map(fn ($b) => [
                'id' => $b->id,
                'nome' => $b->nome,
                'ativo' => $b->ativo,
                'taxas' => $taxasMap[$b->id] ?? [],
            ]),
        ]);
    }

    public function store(PlanoMaquininhaRequest $request)
    {
        $lojaId = auth()->user()->loja_id;

        $plano = DB::transaction(function () use ($request, $lojaId) {
            // Enforce: 1 ativo por loja
            $ativo = $request->boolean('ativo', true);
            if ($ativo) {
                PlanoMaquininha::where('loja_id', $lojaId)->update(['ativo' => false]);
            }

            $plano = PlanoMaquininha::create([
                'loja_id' => $lojaId,
                'nome' => $request->input('nome'),
                'taxa_antecipacao' => $request->input('taxa_antecipacao'),
                'ativo' => $ativo,
            ]);

            // Inicializar taxas nulas para todas as bandeiras da loja
            $bandeiras = Bandeira::where('loja_id', $lojaId)->get();
            $modalidades = ['debito', 'credito_avista', 'credito_2_6', 'credito_7_12'];
            foreach ($bandeiras as $b) {
                foreach ($modalidades as $mod) {
                    $plano->taxas()->create([
                        'bandeira_id' => $b->id,
                        'modalidade' => $mod,
                        'taxa' => null,
                    ]);
                }
            }

            // Aplicar taxas enviadas no payload (se houver)
            if ($request->filled('taxas')) {
                $this->aplicarTaxas($plano, $request->input('taxas'));
            }

            return $plano;
        });

        return response()->json($plano->load('taxas'), 201);
    }

    public function update(PlanoMaquininhaRequest $request, PlanoMaquininha $plano)
    {
        $this->autorizarLoja($plano);

        DB::transaction(function () use ($request, $plano) {
            $ativo = $request->boolean('ativo', $plano->ativo);
            if ($ativo && !$plano->ativo) {
                PlanoMaquininha::where('loja_id', $plano->loja_id)
                    ->where('id', '!=', $plano->id)
                    ->update(['ativo' => false]);
            }

            $plano->update([
                'nome' => $request->input('nome', $plano->nome),
                'taxa_antecipacao' => $request->input('taxa_antecipacao'),
                'ativo' => $ativo,
            ]);

            if ($request->filled('taxas')) {
                $this->aplicarTaxas($plano, $request->input('taxas'));
            }
        });

        return response()->json($plano->fresh()->load('taxas'));
    }

    public function destroy(PlanoMaquininha $plano)
    {
        $this->autorizarLoja($plano);
        $plano->delete();
        return response()->json(null, 204);
    }

    private function aplicarTaxas(PlanoMaquininha $plano, array $taxas): void
    {
        $lojaId = $plano->loja_id;
        foreach ($taxas as $t) {
            $bandeira = Bandeira::where('id', $t['bandeira_id'])->where('loja_id', $lojaId)->first();
            if (!$bandeira) continue;

            $plano->taxas()->updateOrCreate(
                [
                    'bandeira_id' => $bandeira->id,
                    'modalidade' => $t['modalidade'],
                ],
                [
                    'taxa' => isset($t['taxa']) && $t['taxa'] !== '' ? $t['taxa'] : null,
                ]
            );
        }
    }

    private function autorizarLoja(PlanoMaquininha $plano): void
    {
        abort_unless($plano->loja_id === auth()->user()->loja_id, 403);
    }
}
