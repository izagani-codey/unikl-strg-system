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
                    <input type="hidden" name="request_type_id" value="{{ $grantRequest->request_type_id }}">

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

                    {{-- VOT Items --}}
                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-1">
                            VOT Breakdown
                            @if($grantRequest->shouldLockVotItems())
                                <span class="text-xs text-orange-600 ml-2">Locked - VOT items cannot be modified after verification</span>
                            @endif
                        </label>
                        @php
                            $votCodes = \App\Models\VotCode::active()->ordered()->get();
                            $existingItems = collect($grantRequest->vot_items ?? [])->values();
                            if ($existingItems->isEmpty()) {
                                $existingItems = collect([['vot_code' => '', 'description' => '', 'amount' => 0]]);
                            }
                            $lockVotItems = $grantRequest->shouldLockVotItems();
                        @endphp
                        <div id="edit-vot-items" class="space-y-3">
                            @foreach($existingItems as $i => $item)
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-3 p-3 {{ $lockVotItems ? 'bg-orange-50 border-orange-200' : 'bg-gray-50' }} rounded border">
                                    @if($lockVotItems)
                                        <div class="text-sm font-medium text-gray-700">
                                            {{ $item['vot_code'] ?? 'N/A' }}
                                        </div>
                                        <div class="text-sm text-gray-600">
                                            {{ $item['description'] ?? 'N/A' }}
                                        </div>
                                        <div class="text-sm font-medium text-gray-900">
                                            RM {{ number_format($item['amount'] ?? 0, 2) }}
                                        </div>
                                        @if(!empty($item['vot_code']))
                                            <input type="hidden" name="vot_items[{{ $i }}][vot_code]" value="{{ $item['vot_code'] }}">
                                            <input type="hidden" name="vot_items[{{ $i }}][description]" value="{{ $item['description'] }}">
                                            <input type="hidden" name="vot_items[{{ $i }}][amount]" value="{{ $item['amount'] }}">
                                        @endif
                                    @else
                                        <select name="vot_items[{{ $i }}][vot_code]" class="rounded border-gray-300" required>
                                            <option value="">Select VOT code</option>
                                            @foreach($votCodes as $votCode)
                                                <option value="{{ $votCode->code }}" @selected(($item['vot_code'] ?? '') === $votCode->code)>
                                                    {{ $votCode->code }} - {{ $votCode->description }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <input type="text" name="vot_items[{{ $i }}][description]" value="{{ $item['description'] ?? '' }}" class="rounded border-gray-300" required>
                                        <input type="number" step="0.01" min="0" name="vot_items[{{ $i }}][amount]" value="{{ $item['amount'] ?? 0 }}" class="rounded border-gray-300" required>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        @if($lockVotItems)
                            <p class="text-xs text-orange-600 mt-2">
                                <strong>Note:</strong> VOT items are locked because this request has already been verified. You can only modify other fields like description, deadline, and additional documents.
                            </p>
                        @endif
                    </div>

                    {{-- Dynamic Fields --}}
                    @if($grantRequest->requestType && $grantRequest->requestType->field_schema)
                        @php
                            $dynamicFields = $grantRequest->requestType->field_schema;
                            $dynamicValues = $grantRequest->payload['dynamic_fields'] ?? [];
                        @endphp
                        <div class="mb-4">
                            <label class="block text-sm font-bold text-gray-700 mb-2">
                                Additional Information
                            </label>
                            <div class="space-y-3">
                                @foreach($dynamicFields as $index => $field)
                                    @php
                                        $fieldKey = $field['name'] ?? $field['id'] ?? 'field_' . $index;
                                        $fieldValue = $dynamicValues[$fieldKey] ?? ($field['default'] ?? '');
                                    @endphp
                                    <div>
                                        <label class="block text-sm font-medium text-gray-600 mb-1">
                                            {{ $field['label'] ?? $field['name'] }}
                                            @if($field['required'] ?? false) <span class="text-red-500">*</span> @endif
                                        </label>
                                        @if($field['type'] === 'textarea')
                                            <textarea name="dynamic_fields[{{ $fieldKey }}]"
                                                      rows="3"
                                                      class="w-full rounded border-gray-300 text-sm"
                                                      @if($field['required'] ?? false) required @endif>{{ $fieldValue }}</textarea>
                                        @elseif($field['type'] === 'select')
                                            <select name="dynamic_fields[{{ $fieldKey }}]"
                                                    class="w-full rounded border-gray-300 text-sm"
                                                    @if($field['required'] ?? false) required @endif>
                                                <option value="">Select...</option>
                                                @foreach($field['options'] ?? [] as $option)
                                                    <option value="{{ $option }}" @selected($fieldValue === $option)>{{ $option }}</option>
                                                @endforeach
                                            </select>
                                        @else
                                            <input type="text"
                                                   name="dynamic_fields[{{ $fieldKey }}]"
                                                   value="{{ $fieldValue }}"
                                                   class="w-full rounded border-gray-300 text-sm"
                                                   @if($field['required'] ?? false) required @endif>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

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

                    {{-- Main Document Upload --}}
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

                    {{-- Additional Supporting Documents --}}
                    @php
                        $additionalDocuments = collect($grantRequest->payload['additional_documents'] ?? [])
                            ->filter(fn ($path) => is_string($path) && $path !== '')
                            ->values();
                    @endphp
                    <div class="mb-6 p-4 border-2 border-dashed border-emerald-200 rounded-lg bg-emerald-50">
                        <h3 class="text-sm font-bold text-emerald-700 mb-2">Additional Supporting Documents</h3>
                        <p class="text-xs text-emerald-700 mb-3">
                            You can add new supporting documents here. Existing uploaded documents are kept and cannot be removed in Edit &amp; Resubmit.
                        </p>

                        @if($additionalDocuments->isNotEmpty())
                            <div class="mb-4 rounded border border-emerald-100 bg-white p-3">
                                <p class="text-xs font-semibold text-gray-600 mb-2">Previously uploaded supporting documents</p>
                                <ul class="space-y-1 text-sm">
                                    @foreach($additionalDocuments as $documentPath)
                                        <li>
                                            <a href="{{ asset('storage/' . $documentPath) }}"
                                               target="_blank"
                                               class="text-emerald-700 hover:underline">
                                                ↗ {{ basename($documentPath) }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <label class="block text-sm font-bold text-emerald-700 mb-2">Upload additional files (optional)</label>
                        <input type="file"
                               name="additional_documents[]"
                               multiple
                               class="w-full text-sm text-gray-500
                                      file:mr-4 file:py-2 file:px-4
                                      file:rounded-full file:border-0
                                      file:text-sm file:font-semibold
                                      file:bg-emerald-600 file:text-white
                                      hover:file:bg-emerald-700">
                        <p class="text-xs text-gray-500 mt-2 italic">
                            PDF, JPG, PNG (Max 5MB each)
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
