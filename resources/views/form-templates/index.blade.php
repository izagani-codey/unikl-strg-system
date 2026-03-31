<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-1">
            <h2 class="font-semibold text-xl text-gray-900 leading-tight">Blank Forms</h2>
            <p class="text-sm text-gray-500">Upload, preview, and manage reusable blank form templates.</p>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-6xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if($errors->any())
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        @foreach($errors->all() as $error)
            <p>{{ $error }}</p>
        @endforeach
    </div>
@endif
            @if(session('success'))
                <div class="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    {{ session('error') }}
                </div>
            @endif

            @if($errors->any())
                <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800">
                    <div class="font-semibold mb-2">Please fix the following issues:</div>
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="grid gap-6 xl:grid-cols-[1.2fr_1fr]">
                <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
                    <div class="flex flex-col gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Upload New Blank Form Template</h3>
                            <p class="text-sm text-slate-500">Allowed file types: PDF, JPG, PNG. Maximum size: 5MB.</p>
                        </div>

                        <form action="{{ route('form-templates.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                            @csrf

                            <div>
                                <label class="block text-sm font-medium text-slate-700">Template Title</label>
                                <input
                                    type="text"
                                    name="title"
                                    required
                                    class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                    placeholder="e.g., STRG Application Form"
                                >
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-slate-700">File Upload</label>
                                <input
                                    type="file"
                                    name="file"
                                    accept=".pdf,.jpg,.jpeg,.png"
                                    required
                                    class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm shadow-sm focus:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-100"
                                >
                            </div>

                            <button type="submit" class="inline-flex items-center justify-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                Upload Template
                            </button>
                        </form>
                    </div>
                </div>

                <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
                    <div class="flex items-center justify-between gap-4">
                        <div>
                            <h3 class="text-lg font-semibold text-slate-900">Template Summary</h3>
                            <p class="text-sm text-slate-500">Quick overview of uploaded blank forms.</p>
                        </div>
                        <span class="inline-flex items-center rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">
                            {{ $templates->count() }} templates
                        </span>
                    </div>

                    <div class="mt-6 space-y-4">
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="text-sm text-slate-500">Most recent upload</p>
                                    <p class="mt-1 text-sm font-semibold text-slate-900">
                                        {{ optional($templates->first()?->created_at)->format('d M Y') ?? 'No uploads yet' }}
                                    </p>
                                </div>
                                <span class="inline-flex items-center rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 border border-slate-200">Latest</span>
                            </div>
                        </div>
                        <div class="rounded-2xl bg-slate-50 p-4">
                            <p class="text-sm text-slate-500">Admin access</p>
                            <p class="mt-1 text-sm font-semibold text-slate-900">Only Staff 2 can upload or delete templates.</p>
                        </div>
                    </div>
                </div>
            </div>

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
