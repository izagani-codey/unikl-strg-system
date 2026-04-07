@props(['index', 'votCodes', 'item' => null])

<div class="bg-gray-50 p-4 rounded-lg border border-gray-200 vot-item-row">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="md:col-span-2">
            <label class="block text-sm font-bold text-gray-700">VOT Code</label>
            <select class="w-full rounded border-gray-300 mt-1 vot-code-select" onchange="handleVotSelection(this)" required>
                <option value="">Select VOT code</option>
                @foreach($votCodes as $votCode)
                    <option value="{{ $votCode->code }}" data-description="{{ $votCode->description }}" @selected($item['vot_code'] ?? '' === $votCode->code)>
                        {{ $votCode->code }} - {{ $votCode->description }}
                    </option>
                @endforeach
            </select>
            <input type="hidden" class="vot-code-input" name="vot_items[{{ $index }}][vot_code]" value="{{ $item['vot_code'] ?? '' }}">
            <input type="hidden" class="vot-description-input" name="vot_items[{{ $index }}][description]" value="{{ $item['description'] ?? '' }}">
        </div>
        <div>
            <label class="block text-sm font-bold text-gray-700">Amount (RM)</label>
            <input type="number" class="w-full rounded border-gray-300 mt-1 vot-amount-input"
                   name="vot_items[{{ $index }}][amount]"
                   value="{{ $item['amount'] ?? '' }}"
                   placeholder="0.00" step="0.01" min="0"
                   oninput="updateVOTPreview()" onchange="calculateTotal(); updateVOTPreview();" required>
        </div>
        <div class="flex items-end">
            <button type="button" onclick="removeVotItemRow(this)" class="px-3 py-2 rounded bg-red-100 text-red-700 text-sm font-semibold hover:bg-red-200 w-full">
                Remove
            </button>
        </div>
    </div>
    <p class="text-xs text-gray-500 mt-2 vot-desc-preview">{{ $item['description'] ?? '' }}</p>
</div>
