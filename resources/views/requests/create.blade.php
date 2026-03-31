<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Submit New Grant Request (STRG)') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                <form action="{{ route('requests.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700">Your Email</label>
                        <input type="text" value="{{ auth()->user()->email }}" class="bg-gray-100 w-full rounded border-gray-300" readonly>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700">Request Type</label>
                        <select name="request_type_id" class="w-full rounded border-gray-300">
                            @foreach($requestTypes as $type)
                                <option value="{{ $type->id }}">{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700">Amount Requested (RM)</label>
                        <input type="number" name="amount" class="w-full rounded border-gray-300" placeholder="0.00" required>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700">Justification / Description</label>
                        <textarea name="description" rows="3" class="w-full rounded border-gray-300" placeholder="Describe the purpose of this grant..." required></textarea>
                    </div>

                    <div class="mb-6 p-4 border-2 border-dashed border-blue-200 rounded-lg bg-blue-50">
                        <label class="block text-sm font-bold text-blue-700 mb-2">Upload Document (PDF or Image)</label>
                        <input type="file" name="document" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-600 file:text-white hover:file:bg-blue-700" required>
                        <p class="text-xs text-gray-500 mt-2 italic">Accepted formats: PDF, JPG, PNG (Max 5MB)</p>
                    </div>
                    @error('document')
    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
@enderror

                    <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded hover:bg-blue-700 transition shadow-md">
                        Submit for Verification
                    </button>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>