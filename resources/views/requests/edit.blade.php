<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                Edit & Resubmit — {{ $grantRequest->ref_number }}
            </h2>
            <span class="px-3 py-1 bg-yellow-100 text-yellow-700 text-xs font-bold rounded-full">
                Revision #{{ $grantRequest->revision_count + 1 }}
            </span>
        </div>
    </x-slot>

    <div class="py-8">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

            {{-- Rejection Reason --}}
            @if($grantRequest->rejection_reason)
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <h3 class="font-bold text-red-700 mb-1">⚠ Reason for Return:</h3>
                <p class="text-red-600 text-sm">{{ $grantRequest->rejection_reason }}</p>
            </div>
            @endif

            {{-- Edit Form --}}
            <div class="bg-white shadow-sm rounded-lg p-6">
                <form action="{{ route('requests.update', $grantRequest->id) }}"
                      method="POST"
                      enctype="multipart/form-data">
                    @csrf
                    @method('PATCH')

                    {{-- Email (readonly) --}}
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-1">Your Email</label>
                        <input type="text"
                               value="{{ auth()->user()->email }}"
                               class="w-full rounded border-gray-300 bg-gray-100 text-sm"
                               readonly>
                    </div>

                    {{-- Request Type (readonly — can't change type on revision) --}}
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-1">Request Type</label>
                        <input type="text"
                               value="{{ $grantRequest->requestType->name }}"
                               class="w-full rounded border-gray-300 bg-gray-100 text-sm"
                               readonly>
                    </div>

                    {{-- Amount --}}
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-1">
                            Amount Requested (RM)
                        </label>
                        <input type="number"
                               name="amount"
                               step="0.01"
                               value="{{ $grantRequest->payload['amount'] ?? '' }}"
                               class="w-full rounded border-gray-300 text-sm"
                               placeholder="0.00">
                    </div>

                    {{-- Description --}}
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-1">
                            Justification / Description
                        </label>
                        <textarea name="description"
                                  rows="4"
                                  class="w-full rounded border-gray-300 text-sm"
                                  required>{{ $grantRequest->payload['description'] ?? '' }}</textarea>
                    </div>

                    {{-- Deadline --}}
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-1">
                            Deadline (optional)
                        </label>
                        <input type="date"
                               name="deadline"
                               value="{{ $grantRequest->deadline?->format('Y-m-d') }}"
                               class="w-full rounded border-gray-300 text-sm">
                    </div>

                    {{-- Current Document --}}
                    @if($grantRequest->file_path)
                    <div class="mb-4 p-3 bg-gray-50 rounded border text-sm">
                        <p class="text-gray-500 mb-1">Current document:</p>
                        <a href="{{ asset('storage/' . $grantRequest->file_path) }}"
                           target="_blank"
                           class="text-blue-600 hover:underline font-semibold">
                            View current file ↗
                        </a>
                    </div>
                    @endif

                    {{-- New Document Upload --}}
                    <div class="mb-6 p-4 border-2 border-dashed border-blue-200 rounded-lg bg-blue-50">
                        <label class="block text-sm font-bold text-blue-700 mb-2">
                            Replace Document (optional)
                        </label>
                        <input type="file"
                               name="document"
                               class="w-full text-sm text-gray-500
                                      file:mr-4 file:py-2 file:px-4
                                      file:rounded-full file:border-0
                                      file:text-sm file:font-semibold
                                      file:bg-blue-600 file:text-white
                                      hover:file:bg-blue-700">
                        <p class="text-xs text-gray-500 mt-2 italic">
                            Leave empty to keep current document. PDF, JPG, PNG (Max 5MB)
                        </p>
                    </div>

                    <div class="flex gap-3">
                        <button type="submit"
                                class="flex-1 bg-blue-600 text-white font-bold py-3 px-4 rounded hover:bg-blue-700 transition">
                            ↺ Resubmit for Verification
                        </button>
                        <a href="{{ route('dashboard') }}"
                           class="px-6 py-3 border border-gray-300 rounded text-gray-600 hover:bg-gray-50 text-sm font-semibold">
                            Cancel
                        </a>
                    </div>

                </form>
            </div>

        </div>
    </div>
</x-app-layout>