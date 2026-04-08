<?php

namespace App\Http\Controllers;

use App\Models\RequestType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RequestTypeController extends Controller
{
    /**
     * Get the template file for a request type
     */
    public function getTemplate($id)
    {
        $requestType = RequestType::with(['defaultTemplate', 'requestTypeTemplates.formTemplate'])->findOrFail($id);
        $template = $requestType->getDefaultTemplate();
        
        if (!$template) {
            return response()->json(['error' => 'No default template assigned'], 404);
        }
        
        $filePath = $template->file_path;
        
        if (!Storage::disk('public')->exists($filePath)) {
            return response()->json(['error' => 'Template file not found'], 404);
        }
        
        $fileContents = Storage::disk('public')->get($filePath);
        $mimeType = Storage::disk('public')->mimeType($filePath);
        
        // Ensure proper headers for iframe display
        return response($fileContents)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline; filename="' . basename($filePath) . '"')
            ->header('X-Frame-Options', 'SAMEORIGIN') // Allow iframe embedding
            ->header('Cache-Control', 'public, max-age=3600'); // Cache for 1 hour
    }
}
