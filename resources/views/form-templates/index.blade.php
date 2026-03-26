<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Blank Forms Upload</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-4">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="font-bold mb-4">Upload New Template</h3>
                <form action="{{ route('form-templates.store') }}" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
                    @csrf
                    <div class="md:col-span-2">
                        <label class="text-sm text-gray-600">Template Title</label>
                        <input type="text" name="title" required class="w-full mt-1 border rounded px-3 py-2 text-sm" placeholder="e.g., STRG Application Form">
                    </div>
                    <div>
                        <label class="text-sm text-gray-600">File</label>
                        <input type="file" name="file" required class="w-full mt-1 border rounded px-3 py-2 text-sm">
                    </div>
                    <button class="bg-blue-600 text-white px-4 py-2 rounded text-sm font-semibold hover:bg-blue-700">Upload</button>
                </form>
            </div>

            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-3 text-left">Title</th>
                            <th class="px-4 py-3 text-left">Uploaded By</th>
                            <th class="px-4 py-3 text-left">Date</th>
                            <th class="px-4 py-3 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($templates as $template)
                            <tr class="border-b">
                                <td class="px-4 py-3 font-semibold">{{ $template->title }}</td>
                                <td class="px-4 py-3">{{ $template->uploader?->name ?? 'N/A' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ $template->created_at?->format('d M Y') }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <a href="{{ asset('storage/' . $template->file_path) }}" target="_blank" class="text-blue-600 hover:underline">View</a>
                                        <form action="{{ route('form-templates.destroy', $template->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')
                                            <button class="text-red-600 hover:underline">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">No templates uploaded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
