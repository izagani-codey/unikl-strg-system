<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-transparent">Blank Forms</h1>
                <p class="text-gray-600 mt-1">Upload, preview, and manage reusable blank form templates</p>
            </div>
            @if(in_array(auth()->user()->role, ['staff1', 'staff2']))
                <button onclick="document.getElementById('upload-form').classList.toggle('hidden')" 
                        class="inline-flex items-center px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-sm font-semibold rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all shadow-lg">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Upload New Form
                </button>
            @endif
        </div>
    </x-slot>

    <div class="py-6">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            <!-- Upload Form (Hidden by default) -->
            @if(in_array(auth()->user()->role, ['staff1', 'staff2']))
                <div id="upload-form" class="hidden">
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h3 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
                            <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                            </svg>
                            Upload Blank Form Template
                        </h3>
                        
                        <form action="{{ route('form-templates.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                            @csrf
                            
                            @if($errors->any())
                                <div class="bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                                    <div class="font-medium mb-2">Please fix the following errors:</div>
                                    @foreach($errors->all() as $error)
                                        <p class="text-sm">• {{ $error }}</p>
                                    @endforeach
                                </div>
                            @endif
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Form Title</label>
                                <input type="text" name="title" value="{{ old('title') }}" 
                                       placeholder="e.g., Travel Grant Application Form"
                                       class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-blue-500 focus:border-blue-500"
                                       required>
                                <p class="mt-1 text-sm text-gray-500">Give this form a descriptive title for easy identification</p>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">PDF File</label>
                                <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-400 transition-colors">
                                    <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                    </svg>
                                    <input type="file" name="file" accept=".pdf,.jpg,.jpeg,.png" class="hidden" id="file-input" required>
                                    <label for="file-input" class="cursor-pointer">
                                        <span class="text-blue-600 font-medium hover:text-blue-700">Choose file</span>
                                        <span class="text-gray-600"> or drag and drop</span>
                                    </label>
                                    <p class="text-xs text-gray-500 mt-2">PDF, JPG, or PNG (max 5MB)</p>
                                </div>
                            </div>
                            
                            <div class="flex justify-end space-x-3">
                                <button type="button" onclick="document.getElementById('upload-form').classList.add('hidden')"
                                        class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                                    Cancel
                                </button>
                                <button type="submit" 
                                        class="px-4 py-2 bg-gradient-to-r from-blue-600 to-indigo-600 text-white rounded-lg hover:from-blue-700 hover:to-indigo-700 transition-all">
                                    Upload Form
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <!-- Success Messages -->
            @if(session('success'))
                <div class="bg-gradient-to-r from-green-50 to-emerald-50 border border-green-200 text-green-800 px-6 py-4 rounded-xl shadow-sm flex items-center">
                    <svg class="w-5 h-5 mr-3 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-gradient-to-r from-red-50 to-pink-50 border border-red-200 text-red-800 px-6 py-4 rounded-xl shadow-sm flex items-center">
                    <svg class="w-5 h-5 mr-3 text-red-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    {{ session('error') }}
                </div>
            @endif

            <!-- Templates Table -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="px-6 py-5 border-b border-slate-200 bg-slate-50">
                    <div class="flex flex-col gap-1 md:flex-row md:items-center md:justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-slate-900">Uploaded Blank Forms</h3>
                            <p class="text-sm text-slate-500">Manage all uploaded templates from your team.</p>
                        </div>
                        <span class="text-sm text-slate-500">Showing {{ $templates->count() }} result{{ $templates->count() === 1 ? '' : 's' }}</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-sm text-left">
                        <thead class="bg-white text-slate-500 uppercase tracking-wide text-[11px]">
                            <tr>
                                <th class="px-6 py-4">Title</th>
                                <th class="px-6 py-4">Uploaded By</th>
                                <th class="px-6 py-4">Date</th>
                                <th class="px-6 py-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            @forelse($templates as $template)
                                <tr class="border-b border-slate-200 odd:bg-white even:bg-slate-50">
                                    <td class="px-6 py-4 font-medium text-slate-900">{{ $template->title }}</td>
                                    <td class="px-6 py-4 text-slate-600">{{ $template->uploader?->name ?? 'N/A' }}</td>
                                    <td class="px-6 py-4 text-slate-500">{{ $template->created_at?->format('d M Y') }}</td>
                                    <td class="px-6 py-4">
                                        <div class="flex flex-wrap items-center gap-3">
                                            <a href="{{ asset('storage/' . $template->file_path) }}" target="_blank" class="rounded-full border border-blue-200 bg-blue-50 px-3 py-1 text-sm font-semibold text-blue-700 hover:bg-blue-100 transition">
                                                View
                                            </a>
                                            @if(auth()->user()->role === 'staff2')
                                                <form action="{{ route('form-templates.destroy', $template->id) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="rounded-full border border-red-200 bg-red-50 px-3 py-1 text-sm font-semibold text-red-700 hover:bg-red-100 transition">
                                                        Delete
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-10 text-center text-slate-500">No templates uploaded yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
