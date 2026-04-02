<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Fill PDF Form Template
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form action="{{ route('requests.fill-pdf-form', $request->id) }}" method="POST">
                    @csrf
                    
                    <div class="mb-6">
                        <label class="block text-sm font-bold text-gray-700">Select Template</label>
                        <select name="template_id" class="w-full rounded border-gray-300 mt-1" required>
                            <option value="">Choose a template...</option>
                            @foreach($templates as $template)
                                <option value="{{ $template->id }}">{{ $template->name }} ({{ $template->getFieldTypeLabel() }})</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="px-6 py-2 rounded bg-blue-600 text-white font-semibold hover:bg-blue-700">
                            Generate Filled PDF
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
