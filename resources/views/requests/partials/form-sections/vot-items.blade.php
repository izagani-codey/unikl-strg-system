@props(['votCodes', 'grantRequest' => null])

@php
$existingVotItems = old('vot_items', $grantRequest?->vot_items ?? []);
@endphp

<div class="mb-6 border-b border-gray-200 pb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Budget Breakdown (VOT Items)</h3>
    <p class="text-sm text-gray-600 mb-4">Choose VOT code from dropdown and enter any amount (no maximum cap enforced in system).</p>

    <div id="vot-items-container" class="space-y-3">
        @if(count($existingVotItems) > 0)
            @foreach($existingVotItems as $index => $item)
                @include('requests.partials.form-sections.vot-item-row', [
                    'index' => $index,
                    'votCodes' => $votCodes,
                    'item' => $item
                ])
            @endforeach
        @else
            @include('requests.partials.form-sections.vot-item-row', [
                'index' => 0,
                'votCodes' => $votCodes,
                'item' => null
            ])
        @endif
    </div>
    
    <button type="button" onclick="addVotItemRow()" class="mt-3 px-4 py-2 rounded bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 border-2 border-blue-800 transition-colors">
        + Add VOT Item
    </button>

    <div class="mt-4 flex justify-between items-center bg-gray-100 p-4 rounded-lg">
        <div class="text-sm font-medium text-gray-700">
            Total Amount:
        </div>
        <div class="text-lg font-bold text-blue-600">
            RM <span id="total-amount">0.00</span>
        </div>
    </div>
</div>

{{-- Hidden template for JavaScript cloning --}}
<template id="vot-item-template">
    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 vot-item-row">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
            <div class="md:col-span-2">
                <label class="block text-sm font-bold text-gray-700">VOT Code</label>
                <select class="w-full rounded border-gray-300 mt-1 vot-code-select" onchange="handleVotSelection(this)" required>
                    <option value="">Select VOT code</option>
                    @foreach($votCodes as $votCode)
                        <option value="{{ $votCode->code }}" data-description="{{ $votCode->description }}">
                            {{ $votCode->code }} - {{ $votCode->description }}
                        </option>
                    @endforeach
                </select>
                <input type="hidden" class="vot-code-input">
                <input type="hidden" class="vot-description-input">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700">Amount (RM)</label>
                <input type="number" class="w-full rounded border-gray-300 mt-1 vot-amount-input"
                       placeholder="0.00" step="0.01" min="0"
                       oninput="updateVOTPreview()" onchange="calculateTotal(); updateVOTPreview();" required>
            </div>
            <div class="flex items-end">
                <button type="button" onclick="removeVotItemRow(this)" class="px-3 py-2 rounded bg-red-100 text-red-700 text-sm font-semibold hover:bg-red-200 w-full">
                    Remove
                </button>
            </div>
        </div>
        <p class="text-xs text-gray-500 mt-2 vot-desc-preview"></p>
    </div>
</template>
