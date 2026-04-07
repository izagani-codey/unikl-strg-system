@props(['grantRequest' => null])

<div class="mb-6 border-b border-gray-200 pb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Digital Signature</h3>
    
    <div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg p-4">
        <canvas id="signature-canvas" width="400" height="200" class="border border-gray-400 bg-white rounded cursor-crosshair touch-none select-none" style="width: 100%; max-width: 400px; height: 200px;"></canvas>
        
        <div class="mt-4 flex justify-between">
            <button type="button" onclick="clearSignature()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition-colors">
                Clear Signature
            </button>
            
            <div class="text-xs text-gray-500">
                Sign above using mouse or touch device
            </div>
        </div>
    </div>
    
    <input type="hidden" name="signature_data" id="signature_data" value="{{ old('signature_data', $grantRequest?->signature_data) }}" required>
</div>
