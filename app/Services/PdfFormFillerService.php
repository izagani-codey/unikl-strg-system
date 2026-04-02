<?php

namespace App\Services;

use App\Models\Request;
use App\Models\FormTemplate;
use App\Models\User;
use Illuminate\Support\Str;
use setasign\Fpdi\Fpdi;
use setasign\Fpdi\PdfParser;

class PdfFormFillerService
{
    /**
     * Fill PDF form template with request data.
     */
    public function fillForm(Request $request, FormTemplate $template): string
    {
        // Load the PDF template
        $templatePath = storage_path('app/public/' . $template->file_path);
        
        if (!file_exists($templatePath)) {
            throw new \Exception('Template file not found: ' . $template->file_path);
        }

        // Create new PDF from template
        $fpdi = new Fpdi();
        $fpdi->setSourceFile($templatePath);
        
        // Get page count
        $pageCount = $fpdi->getPageCount();
        $templatePdf = new PdfParser($templatePath);
        
        // Get user and request data
        $user = $request->user;
        $mappedFields = $template->getMappedFields();
        
        // Create a new PDF for the filled form
        $newPdf = new Fpdi();
        
        // Process each page
        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $newPdf->AddPage();
            $fpdi->useTemplatePage($templatePdf, $pageNo);
            
            // Fill fields based on mappings
            foreach ($mappedFields as $field => $pdfField) {
                $value = $this->getFieldValue($field, $request, $user);
                if ($value !== null) {
                    $newPdf->SetFont('Arial', '', 10);
                    $newPdf->SetTextColor(0, 0, 0);
                    $newPdf->SetXY($pdfField['x'] ?? 50, $pdfField['y'] ?? 50);
                    $newPdf->Write($value);
                }
            }
            
            // Add VOT items if it's a VOT form
            if ($template->template_type === 'vot_form') {
                $this->addVotItemsToPdf($newPdf, $request);
            }
        }
        
        // Output the filled PDF
        $outputPath = 'filled_forms/' . Str::uuid() . '.pdf';
        $fullPath = storage_path('app/public/' . $outputPath);
        
        // Ensure directory exists
        $directory = dirname($fullPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
        
        $newPdf->Output($fullPath, 'F');
        
        return $outputPath;
    }
    
    /**
     * Get field value from request or user data.
     */
    private function getFieldValue(string $field, Request $request, User $user): ?string
    {
        return match($field) {
            'user.name' => $user->name,
            'user.staff_id' => $user->staff_id,
            'user.designation' => $user->designation,
            'user.department' => $user->department,
            'user.phone' => $user->phone,
            'user.email' => $user->email,
            'user.employee_level' => $user->employee_level,
            'user.signature_data' => $user->signature_data,
            'request.ref_number' => $request->ref_number,
            'request.request_type' => $request->requestType->name ?? '',
            'request.total_amount' => number_format($request->total_amount, 2),
            'request.deadline' => $request->deadline?->format('Y-m-d') : '',
            'request.description' => $request->description,
            'request.submitted_at' => $request->submitted_at->format('Y-m-d H:i:s'),
            'vot_items' => $this->formatVotItems($request),
            default => null,
        };
    }
    
    /**
     * Format VOT items for PDF display.
     */
    private function formatVotItems(Request $request): string
    {
        $votItems = $request->vot_items ?? [];
        $formatted = '';
        
        foreach ($votItems as $index => $item) {
            $formatted .= ($index + 1) . '. ' . $item['vot_code'] . ' - ' . $item['description'] . "\n";
            $formatted .= '   Amount: RM ' . number_format($item['amount'], 2) . "\n\n";
        }
        
        return $formatted;
    }
    
    /**
     * Add VOT items table to PDF.
     */
    private function addVotItemsToPdf(Fpdi $fpdi, Request $request): void
    {
        $votItems = $request->vot_items ?? [];
        $yPosition = 400;
        
        // Add VOT header
        $fpdi->SetFont('Arial', 'B', 12);
        $fpdi->SetXY(50, $yPosition);
        $fpdi->Write('VOT Budget Breakdown:');
        $yPosition += 20;
        
        // Add each VOT item
        foreach ($votItems as $index => $item) {
            $fpdi->SetFont('Arial', '', 10);
            $fpdi->SetXY(60, $yPosition);
            $fpdi->Write(($index + 1) . '. ' . $item['vot_code'] . ' - ' . $item['description']);
            $yPosition += 10;
            
            $fpdi->SetXY(70, $yPosition);
            $fpdi->Write('Amount: RM ' . number_format($item['amount'], 2));
            $yPosition += 20;
        }
        
        // Add total
        $fpdi->SetFont('Arial', 'B', 12);
        $fpdi->SetXY(50, $yPosition);
        $fpdi->Write('Total Amount: RM ' . number_format($request->total_amount, 2));
    }
}
