<?php

namespace App\Services;

use App\Models\Invoice;
use Mpdf\Mpdf;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;

class PdfService
{
    public function generateInvoicePdf(Invoice $invoice): string
    {
        $mpdf = new Mpdf([
            'mode'              => 'utf-8',
            'format'            => 'A4',
            'margin_top'        => 0,
            'margin_bottom'     => 0,
            'margin_left'       => 0,
            'margin_right'      => 0,
            'tempDir'           => storage_path('app/tmp'),
        ]);

        $mpdf->SetDisplayMode('fullpage');
        $mpdf->showImageErrors = true;

        $html = view('pdf.invoice', [
            'invoice' => $invoice,
        ])->render();

        $mpdf->WriteHTML($html);

        $directory = storage_path('app/invoices');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }

        $path = "{$directory}/invoice-{$invoice->invoice_number}.pdf";
        $mpdf->Output($path, 'F');

        return $path;
    }

    public function streamInvoicePdf(Invoice $invoice): string
    {
        $mpdf = new Mpdf([
            'mode'              => 'utf-8',
            'format'            => 'A4',
            'margin_top'        => 0,
            'margin_bottom'     => 25, // ← espace pour le footer
            'margin_left'       => 0,
            'margin_right'      => 0,
            'margin_footer'     => 0,
            'tempDir'           => storage_path('app/tmp'),
        ]);

        $mpdf->SetHTMLFooter('
        <table style="width: 100%; background: #0f172a; padding: 20px 50px;">
            <tr>
                <td style="font-family: Arial, sans-serif; font-size: 13px; font-weight: bold; color: #ffffff;">
                    Facturo<span style="color: #3b82f6;">.</span>
                    <br>
                    <span style="font-size: 10px; color: #475569;">
                        Generated on {DATE F d, Y}
                    </span>
                </td>
                <td style="text-align: right; font-size: 11px; color: #475569;">
                    ' . $invoice->invoice_number . '
                    <br>
                    <span style="color: #94a3b8;">Page {PAGENO} of {nbpg}</span>
                </td>
            </tr>
        </table>
    ');

        $html = view('pdf.invoice', [
            'invoice' => $invoice,
        ])->render();

        $mpdf->WriteHTML($html);

        return $mpdf->Output('', 'S');
    }
}
