<?php

declare(strict_types=1);

namespace App\Domain\Reporting\Rendering;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Contracts\View\Factory as ViewFactory;

/**
 * Merender Blade → PDF (bytes) via Dompdf. Aset remote dinonaktifkan — semua
 * gaya inline, tanpa font/gambar eksternal, agar aman untuk lingkungan luring.
 */
class PdfRenderer
{
    public function __construct(private readonly ViewFactory $view) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function render(string $view, array $data, string $orientation = 'portrait'): string
    {
        $options = new Options;
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('chroot', [storage_path('app')]);

        $dompdf = new Dompdf($options);
        $dompdf->setPaper('A4', $orientation);
        $dompdf->loadHtml($this->view->make($view, $data)->render(), 'UTF-8');
        $dompdf->render();

        return (string) $dompdf->output();
    }
}
