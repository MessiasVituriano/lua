<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class PdfService
{
    /**
     * Gera e retorna um Response de download de PDF.
     *
     * @param  string  $template  Caminho do view Blade (ex: 'pdf.pedido-compra')
     * @param  array   $data      Dados passados ao template
     * @param  string  $filename  Nome do arquivo para download
     * @param  string  $title     Título exibido no cabeçalho do PDF
     * @return \Illuminate\Http\Response
     */
    public function download(string $template, array $data, string $filename, string $title = ''): \Illuminate\Http\Response
    {
        $user = Auth::user();
        $loja = $user?->lojaAtiva;

        $shared = [
            'lojaNome'    => $loja?->nome ?? 'LUA PetShop',
            'usuarioNome' => $user?->nome ?? '',
            'geradoEm'    => now()->format('d/m/Y \à\s H:i'),
            'titulo'      => $title,
        ];

        $pdf = Pdf::loadView($template, array_merge($shared, $data))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont'        => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'    => false,
            ]);

        return $pdf->download($filename . '.pdf');
    }

    /**
     * Retorna o PDF como stream (para visualização inline no browser).
     */
    public function stream(string $template, array $data, string $filename, string $title = ''): \Illuminate\Http\Response
    {
        $user = Auth::user();
        $loja = $user?->lojaAtiva;

        $shared = [
            'lojaNome'    => $loja?->nome ?? 'LUA PetShop',
            'usuarioNome' => $user?->nome ?? '',
            'geradoEm'    => now()->format('d/m/Y \à\s H:i'),
            'titulo'      => $title,
        ];

        $pdf = Pdf::loadView($template, array_merge($shared, $data))
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont'        => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled'    => false,
            ]);

        return $pdf->stream($filename . '.pdf');
    }
}
