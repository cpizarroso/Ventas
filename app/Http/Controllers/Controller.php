<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

abstract class Controller
{
    /**
     * Render a report as a downloadable PDF with a repeated page counter.
     *
     * @param  array<string, mixed>  $datos
     */
    protected function generarPdf(string $vista, array $datos, string $nombreArchivo): Response
    {
        $pdf = Pdf::loadView($vista, $datos)
            ->setPaper('a4', 'landscape')
            ->setWarnings(false);

        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $fuente = $dompdf->getFontMetrics()->getFont('Helvetica', 'normal');

        $canvas->page_text(
            $canvas->get_width() - 95,
            $canvas->get_height() - 18,
            'Página {PAGE_NUM}/{PAGE_COUNT}',
            $fuente,
            8,
            [0.35, 0.35, 0.35]
        );

        return $pdf->download($nombreArchivo);
    }
}
