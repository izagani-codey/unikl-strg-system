<?php

namespace App\Http\Controllers;

use App\Models\FormTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FormTemplateController extends Controller
{
    public function index()
    {
        $templates = FormTemplate::query()->with('uploader')->latest('created_at')->get();

        return view('form-templates.index', compact('templates'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'title' => ['required', 'string', 'max:120', 'regex:/^[a-zA-Z0-9\s\-_\.]+$/'],
            'file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'mimetypes:application/pdf,image/jpeg,image/png',
                'max:5120', // 5MB max
                'dimensions:min_width=100,min_height=100', // Minimum dimensions for images
            ],
        ]);

        $file = $request->file('file');
        
        // Enhanced security checks
        if ($file->isValid()) {
            // Check file size manually for additional security
            if ($file->getSize() > 5 * 1024 * 1024) { // 5MB
                return back()->withErrors(['file' => 'File size exceeds maximum allowed size of 5MB.']);
            }
            
            // Scan for malicious content (basic check)
            $content = file_get_contents($file->getPathname());
            if (strpos($content, '<?php') !== false || strpos($content, '<script') !== false) {
                return back()->withErrors(['file' => 'File contains potentially malicious content.']);
            }
            
            // Generate safe filename
            $originalName = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();
            $safeFilename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '', pathinfo($originalName, PATHINFO_FILENAME));
            $filename = $safeFilename . '_' . time() . '.' . $extension;
            
            $path = $file->storeAs('blank-forms', $filename, 'public');
            
            FormTemplate::create([
                'title' => $request->input('title'),
                'template_type' => 'general_form', // Default type
                'file_path' => $path,
                'uploaded_by' => $request->user()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return back()->with('success', 'Blank form uploaded successfully.');
        } else {
            return back()->withErrors(['file' => 'File upload failed. Please try again.']);
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        $template = FormTemplate::query()->findOrFail($id);

        Storage::disk('public')->delete($template->file_path);
        $template->delete();

        return back()->with('success', 'Blank form removed.');
    }
}
