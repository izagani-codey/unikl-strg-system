<?php

namespace App\Http\Controllers;

use App\Models\FormTemplate;
use App\Models\Request as GrantRequest;
use App\Services\PdfFormFillerService;
use App\Services\RequestPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RequestPdfController extends Controller
{
    protected $requestPdfService;
    protected $pdfFormFillerService;

    public function __construct(
        RequestPdfService $requestPdfService,
        PdfFormFillerService $pdfFormFillerService
    ) {
        $this->requestPdfService = $requestPdfService;
        $this->pdfFormFillerService = $pdfFormFillerService;
    }

    public function downloadPdf($id)
    {
        try {
            $grantRequest = GrantRequest::with(['user', 'requestType'])->findOrFail($id);
            
            // Authorize access
            $this->authorize('view', $grantRequest);
            
            // Generate PDF using existing service
            $pdfPath = $this->requestPdfService->generateRequestPdf($grantRequest);
            
            return response()->download($pdfPath, 'request-' . $grantRequest->ref_number . '.pdf');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to generate PDF: ' . $e->getMessage());
        }
    }

    public function fillPdfForm(Request $request, $id)
    {
        try {
            $grantRequest = GrantRequest::findOrFail($id);
            $templates = FormTemplate::where('is_active', true)->get();
            
            // Authorize access
            $this->authorize('view', $grantRequest);
            
            // Handle form submission
            if ($request->isMethod('post')) {
                $template = FormTemplate::findOrFail($request->input('template_id'));
                
                $filledPdfPath = $this->pdfFormFillerService->fillForm($grantRequest, $template);
                
                return response()->download($filledPdfPath, 'filled-form-' . $grantRequest->ref_number . '.pdf');
            }
            
            return view('requests.fill-pdf-form', compact('grantRequest', 'templates'));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to fill PDF form: ' . $e->getMessage());
        }
    }

    public function printSummary($id)
    {
        try {
            $grantRequest = GrantRequest::with([
                'user',
                'requestType',
                'verifiedBy',
                'recommendedBy',
                'auditLogs' => function ($query) {
                    $query->with('actor')->latest();
                }
            ])->findOrFail($id);
            
            // Authorize access
            $this->authorize('view', $grantRequest);
            
            return view('requests.print', compact('grantRequest'));
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to load print view: ' . $e->getMessage());
        }
    }
}
