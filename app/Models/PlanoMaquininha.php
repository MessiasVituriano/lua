<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanoMaquininha extends Model
{
    use HasFactory;

    protected $table = 'planos_maquininha';

    protected $fillable = [
        'loja_id',
        'nome',
        'taxa_antecipacao',
        'ativo',
    ];

    protected $casts = [
        'taxa_antecipacao' => 'decimal:2',
        'ativo' => 'boolean',
    ];

    public const MODALIDADES = [
        'debito' => 'Debito',
        'credito_avista' => 'Credito a vista',
        'credito_2_6' => '2x a 6x',
        'credito_7_12' => '7x a 12x',
    ];

    public function loja()
    {
        return $this->belongsTo(Loja::class);
    }

    public function taxas()
    {
        return $this->hasMany(PlanoMaquininhaTaxa::class);
    }

    public static function modalidadePara(string $formaRecebimento, ?int $parcelas): ?string
    {
        if ($formaRecebimento === 'cartao_debito') {
            return 'debito';
        }

        if ($formaRecebimento !== 'cartao_credito' || !$parcelas) {
            return null;
        }

        if ($parcelas === 1) return 'credito_avista';
        if ($parcelas >= 2 && $parcelas <= 6) return 'credito_2_6';
        if ($parcelas >= 7 && $parcelas <= 12) return 'credito_7_12';

        return null;
    }

    public function calcularLiquido(float $valorBruto, int $bandeiraId, string $modalidade, bool $isCredito): array
    {
        $taxa = $this->taxas()
            ->where('bandeira_id', $bandeiraId)
            ->where('modalidade', $modalidade)
            ->value('taxa');

        if ($taxa === null) {
            return [
                'ok' => false,
                'erro' => 'Bandeira nao aceita esta modalidade neste plano.',
            ];
        }

        $taxaAntecipacao = $isCredito && $this->taxa_antecipacao ? (float) $this->taxa_antecipacao : 0;
        $taxaTotal = (float) $taxa + $taxaAntecipacao;
        $liquido = round($valorBruto * (1 - $taxaTotal / 100), 2);

        return [
            'ok' => true,
            'taxa' => (float) $taxa,
            'taxa_antecipacao' => $taxaAntecipacao,
            'taxa_total' => $taxaTotal,
            'valor_liquido' => $liquido,
            'com_antecipacao' => $taxaAntecipacao > 0,
        ];
    }
}
