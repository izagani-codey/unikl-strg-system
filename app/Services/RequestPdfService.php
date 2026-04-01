<?php

namespace App\Services;

use App\Models\Request as GrantRequest;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class RequestPdfService
{
    /**
     * Generate a filled PDF for the given request and store it.
     * Returns the stored file path.
     */
    public static function generate(GrantRequest $request): string
    {
        $pdf = Pdf::loadView('requests.pdf-template', [
            'request' => $request,
        ])->setPaper('a4', 'portrait');

        $filename = 'requests/pdf/' . $request->ref_number . '_' . now()->format('Ymd_His') . '.pdf';

        Storage::disk('public')->put($filename, $pdf->output());

        return $filename;
    }
}
