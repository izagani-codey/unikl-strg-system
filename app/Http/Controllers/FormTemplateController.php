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
            'title' => ['required', 'string', 'max:120'],
            'file' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png',
                'mimetypes:application/pdf,image/jpeg,image/png',
                'max:5120',
            ],
        ]);

        $path = $request->file('file')->store('blank-forms', 'public');

        FormTemplate::create([
            'title' => $request->input('title'),
            'template_type' => 'general_form', // Default type
            'file_path' => $path,
            'uploaded_by' => $request->user()->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('success', 'Blank form uploaded successfully.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $template = FormTemplate::query()->findOrFail($id);

        Storage::disk('public')->delete($template->file_path);
        $template->delete();

        return back()->with('success', 'Blank form removed.');
    }
}
