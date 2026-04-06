<div class="mb-6">
    <h3 class="text-lg font-semibold text-gray-900 mb-4">Supporting Document (Optional)</h3>
    
    <div class="p-4 border-2 border-dashed border-blue-200 rounded-lg bg-blue-50">
        <label class="block text-sm font-bold text-blue-700 mb-2">Upload Supporting Document</label>
        <input type="file" name="document" class="w-full text-sm text-gray-500 File:mr-4 File:py-2 File:px-4 File:rounded-full File:border-0 File:text-sm File:font-semibold File:bg-blue-600 File:text-white hover:File:bg-blue-700">
        <p class="text-xs text-gray-500 mt-2 italic">Accepted formats: PDF, JPG, PNG (Max 5MB) - Optional, PDF will be generated automatically</p>
    </div>
    @error('document')
        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>
