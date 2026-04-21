<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BandeiraRequest;
use App\Models\Bandeira;
use App\Models\PlanoMaquininha;
use Illuminate\Support\Facades\DB;

class BandeiraController extends Controller
{
    public function index()
    {
        $lojaId = auth()->user()->loja_id;

        return response()->json(
            Bandeira::where('loja_id', $lojaId)->orderBy('nome')->get()
        );
    }

    public function store(BandeiraRequest $request)
    {
        $lojaId = auth()->user()->loja_id;

        $bandeira = DB::transaction(function () use ($request, $lojaId) {
            $bandeira = Bandeira::create([
                'loja_id' => $lojaId,
                'nome' => $request->input('nome'),
                'ativo' => $request->boolean('ativo', true),
            ]);

            // Para cada plano da loja, criar linhas de taxa vazias (null) para a nova bandeira
            $planos = PlanoMaquininha::where('loja_id', $lojaId)->get();
            $modalidades = ['debito', 'credito_avista', 'credito_2_6', 'credito_7_12'];
            foreach ($planos as $plano) {
                foreach ($modalidades as $mod) {
                    $plano->taxas()->create([
                        'bandeira_id' => $bandeira->id,
                        'modalidade' => $mod,
                        'taxa' => null,
                    ]);
                }
            }

            return $bandeira;
        });

        return response()->json($bandeira, 201);
    }

    public function show(Bandeira $bandeira)
    {
        $this->autorizarLoja($bandeira);
        return response()->json($bandeira);
    }

    public function update(BandeiraRequest $request, Bandeira $bandeira)
    {
        $this->autorizarLoja($bandeira);
        $bandeira->update($request->validated());
        return response()->json($bandeira);
    }

    public function destroy(Bandeira $bandeira)
    {
        $this->autorizarLoja($bandeira);
        $bandeira->delete();
        return response()->json(null, 204);
    }

    private function autorizarLoja(Bandeira $bandeira): void
    {
        abort_unless($bandeira->loja_id === auth()->user()->loja_id, 403);
    }
}
