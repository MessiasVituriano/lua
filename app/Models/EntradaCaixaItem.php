<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EntradaCaixaItem extends Model
{
    use HasFactory;

    protected $table = 'entrada_caixa_itens';

    protected $fillable = [
        'entrada_caixa_id',
        'produto_id',
        'quantidade',
        'preco_unitario',
        'subtotal',
        'peso_gramas',
        'perfil_pet_tipo',
        'pet_id',
        'cliente_id',
        'data_proxima_compra_estimada',
    ];

    protected $casts = [
        'quantidade' => 'decimal:3',
        'preco_unitario' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'peso_gramas' => 'integer',
        'data_proxima_compra_estimada' => 'date',
    ];

    public const PERFIS_PET = [
        'cao_filhote_pequeno',
        'cao_adulto_pequeno',
        'cao_senior_pequeno',
        'cao_filhote_medio',
        'cao_adulto_medio',
        'cao_senior_medio',
        'cao_filhote_grande',
        'cao_adulto_grande',
        'cao_senior_grande',
        'gato_filhote',
        'gato_adulto',
        'gato_senior',
        'bulldog_frances_adulto',
        'labrador_adulto',
        'shih_tzu_adulto',
        'persa_adulto',
        'siames_adulto',
        'cao_pequeno',
        'cao_medio',
        'cao_grande',
        'gato',
        'outros',
    ];

    public const CONSUMO_PADRAO_GRAMAS_DIA = [
        'cao_filhote_pequeno' => 110,
        'cao_adulto_pequeno' => 120,
        'cao_senior_pequeno' => 100,
        'cao_filhote_medio' => 220,
        'cao_adulto_medio' => 250,
        'cao_senior_medio' => 210,
        'cao_filhote_grande' => 360,
        'cao_adulto_grande' => 400,
        'cao_senior_grande' => 330,
        'gato_filhote' => 90,
        'gato_adulto' => 80,
        'gato_senior' => 70,
        'bulldog_frances_adulto' => 200,
        'labrador_adulto' => 380,
        'shih_tzu_adulto' => 105,
        'persa_adulto' => 75,
        'siames_adulto' => 78,
        'cao_pequeno' => 120,
        'cao_medio' => 250,
        'cao_grande' => 400,
        'gato' => 80,
        'outros' => 150,
    ];

    public function entradaCaixa()
    {
        return $this->belongsTo(EntradaCaixa::class);
    }

    public function produto()
    {
        return $this->belongsTo(Produto::class);
    }

    public function pet()
    {
        return $this->belongsTo(Pet::class);
    }

    public function cliente()
    {
        return $this->belongsTo(Cliente::class);
    }

    public static function resolverPerfilConsumo(?Pet $pet, ?string $perfilInformado): string
    {
        if ($perfilInformado && in_array($perfilInformado, self::PERFIS_PET, true)) {
            return $perfilInformado;
        }

        if (!$pet) {
            return 'outros';
        }

        $raca = strtolower(trim((string) ($pet->raca ?? '')));
        $tipoRaw = $pet->tipo ?? null;
        $tipo = match ($tipoRaw) {
            'cao', 'cao_pequeno', 'cao_medio', 'cao_grande' => 'cao',
            'gato' => 'gato',
            default => $tipoRaw,
        };

        if ($tipo === 'cao') {
            if (str_contains($raca, 'bulldog')) return 'bulldog_frances_adulto';
            if (str_contains($raca, 'labrador')) return 'labrador_adulto';
            if (str_contains($raca, 'shih')) return 'shih_tzu_adulto';
        }

        if ($tipo === 'gato') {
            if (str_contains($raca, 'persa')) return 'persa_adulto';
            if (str_contains($raca, 'siames')) return 'siames_adulto';
        }

        $faixa = self::faixaEtaria($pet->idade_meses);

        if ($tipo === 'gato') {
            return match ($faixa) {
                'filhote' => 'gato_filhote',
                'senior' => 'gato_senior',
                default => 'gato_adulto',
            };
        }

        if ($tipo === 'cao') {
            $porte = $pet->porte ?? self::portePadraoPorPerfilLegado($perfilInformado);

            if ($porte === 'pequeno') {
                return match ($faixa) {
                    'filhote' => 'cao_filhote_pequeno',
                    'senior' => 'cao_senior_pequeno',
                    default => 'cao_adulto_pequeno',
                };
            }

            if ($porte === 'grande') {
                return match ($faixa) {
                    'filhote' => 'cao_filhote_grande',
                    'senior' => 'cao_senior_grande',
                    default => 'cao_adulto_grande',
                };
            }

            return match ($faixa) {
                'filhote' => 'cao_filhote_medio',
                'senior' => 'cao_senior_medio',
                default => 'cao_adulto_medio',
            };
        }

        return match ($perfilInformado) {
            'cao_pequeno' => 'cao_adulto_pequeno',
            'cao_medio' => 'cao_adulto_medio',
            'cao_grande' => 'cao_adulto_grande',
            'gato' => 'gato_adulto',
            default => 'outros',
        };
    }

    public static function consumoDiarioPorPerfil(string $perfil): int
    {
        return self::CONSUMO_PADRAO_GRAMAS_DIA[$perfil]
            ?? self::CONSUMO_PADRAO_GRAMAS_DIA['outros'];
    }

    private static function faixaEtaria($idadeMeses): string
    {
        $idade = $idadeMeses !== null ? (int) $idadeMeses : null;
        if ($idade === null) return 'adulto';
        if ($idade < 12) return 'filhote';
        if ($idade >= 84) return 'senior';
        return 'adulto';
    }

    private static function portePadraoPorPerfilLegado(?string $perfil): string
    {
        return match ($perfil) {
            'cao_pequeno' => 'pequeno',
            'cao_grande' => 'grande',
            default => 'medio',
        };
    }
}
