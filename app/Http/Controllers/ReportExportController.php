<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ReportExport;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportController extends Controller
{
    use AuthorizesRequests;

    public function download(ReportExport $export): StreamedResponse
    {
        $this->authorize('download', $export);

        $disk = Storage::disk($export->disk ?? 'local');
        abort_unless($export->path !== null && $disk->exists($export->path), 404);

        return $disk->download($export->path, $export->downloadName());
    }
}
