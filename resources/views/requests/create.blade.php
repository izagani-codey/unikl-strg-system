<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Submit New STRG Request') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                
                <form action="{{ route('requests.store') }}" method="POST" enctype="multipart/form-data" id="request-form">
                    @csrf
                    
                    {{-- Request Information --}}
                    <div class="mb-6 border-b border-gray-200 pb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Request Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700">Request Type *</label>
                                <select name="request_type_id" class="w-full rounded border-gray-300" required>
                                    <option value="">Select Request Type</option>
                                    @foreach($requestTypes as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-bold text-gray-700">Priority</label>
                                <div class="flex items-center mt-2">
                                    <input type="checkbox" name="priority" value="1" class="rounded border-gray-300">
                                    <label class="ml-2 text-sm text-gray-700">Mark as High Priority</label>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700">Deadline</label>
                                <input type="date" name="deadline" class="w-full rounded border-gray-300 mt-1">
                                <p class="text-xs text-gray-500 mt-1">Optional deadline for this request</p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-bold text-gray-700">Justification / Description *</label>
                            <textarea name="description" rows="4" class="w-full rounded border-gray-300 mt-1" placeholder="Describe the purpose and justification for this STRG request..." required></textarea>
                        </div>
                    </div>

                    {{-- VOT Line Items --}}
                    <div class="mb-6 border-b border-gray-200 pb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Budget Breakdown (VOT Items)</h3>
                        
                        <div id="vot-items-container" class="space-y-3 mb-4">
                            <!-- VOT items will be added here dynamically -->
                        </div>
                        
                        <div class="flex justify-between items-center">
                            <button type="button" onclick="addVotItem()" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors">
                                <svg class="w-4 h-4 mr-2 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" data-slot="icon" d="M12 4.5v15m7.5-7.5h-7.5m7.5 7.5l-3-3m0 0l-3 3"/>
                                </svg>
                                Add VOT Item
                            </button>
                            
                            <div class="text-sm text-gray-600">
                                Total: <span id="total-amount" class="font-bold text-lg">0.00</span> RM
                            </div>
                        </div>
                    </div>

                    {{-- Digital Signature --}}
                    <div class="mb-6 border-b border-gray-200 pb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Digital Signature</h3>
                        
                        <div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg p-4">
                            <canvas id="signature-canvas" width="400" height="200" class="border border-gray-400 bg-white rounded cursor-crosshair"></canvas>
                            
                            <div class="mt-4 flex justify-between">
                                <button type="button" onclick="clearSignature()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition-colors">
                                    Clear Signature
                                </button>
                                
                                <div class="text-xs text-gray-500">
                                    Sign above using mouse or touch device
                                </div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="signature_data" id="signature_data" required>
                    </div>

                    {{-- Document Upload --}}
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

                    <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 px-4 rounded hover:bg-blue-700 transition-colors shadow-md">
                        Submit Request for Verification
                    </button>
                </form>

            </div>
        </div>
    </div>

    <!-- Signature Pad JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
    <script>
        let signaturePad;
        let votItemCount = 0;

        // Initialize signature pad
        document.addEventListener('DOMContentLoaded', function() {
            const canvas = document.getElementById('signature-canvas');
            signaturePad = new SignaturePad(canvas, {
                backgroundColor: 'rgb(255, 255, 255)',
                penColor: 'rgb(0, 0, 0)',
                penWidth: 1.5
            });

            // Add first VOT item by default
            addVotItem();
        });

        // VOT Item Management
        function addVotItem() {
            votItemCount++;
            const container = document.getElementById('vot-items-container');
            
            const itemDiv = document.createElement('div');
            itemDiv.className = 'bg-gray-50 p-4 rounded-lg border border-gray-200';
            itemDiv.innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                    <div>
                        <label class="block text-sm font-bold text-gray-700">VOT Code *</label>
                        <input type="text" name="vot_items[${votItemCount}][vot_code]" 
                               class="w-full rounded border-gray-300 mt-1" 
                               placeholder="e.g. A12345" required>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Description *</label>
                        <input type="text" name="vot_items[${votItemCount}][description]" 
                               class="w-full rounded border-gray-300 mt-1" 
                               placeholder="Describe the item" required>
                    </div>
                    <div>
                        <label class="block text-sm font-bold text-gray-700">Amount (RM) *</label>
                        <input type="number" name="vot_items[${votItemCount}][amount]" 
                               class="w-full rounded border-gray-300 mt-1" 
                               placeholder="0.00" step="0.01" min="0.01" 
                               onchange="calculateTotal()" required>
                    </div>
                    <div class="flex items-center justify-between">
                        <button type="button" onclick="removeVotItem(this)" 
                                class="bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600 transition-colors">
                            Remove
                        </button>
                    </div>
                </div>
            `;
            
            container.appendChild(itemDiv);
            calculateTotal();
        }

        function removeVotItem(button) {
            button.closest('.bg-gray-50').remove();
            calculateTotal();
        }

        function calculateTotal() {
            const amountInputs = document.querySelectorAll('input[name*="[amount]"]');
            let total = 0;
            
            amountInputs.forEach(input => {
                const value = parseFloat(input.value) || 0;
                total += value;
            });
            
            document.getElementById('total-amount').textContent = total.toFixed(2);
        }

        // Signature Management
        function clearSignature() {
            signaturePad.clear();
        }

        // Save signature data before form submission
        document.getElementById('request-form').addEventListener('submit', function(e) {
            if (signaturePad.isEmpty()) {
                e.preventDefault();
                alert('Please provide your digital signature before submitting.');
                return false;
            }
            
            // Save signature as base64
            const signatureData = signaturePad.toDataURL();
            document.getElementById('signature_data').value = signatureData;
            
            return true;
        });

        // Prevent form double submission
        let isSubmitting = false;
        document.getElementById('request-form').addEventListener('submit', function(e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            isSubmitting = true;
            
            // Show loading state
            const submitBtn = e.target.querySelector('button[type="submit"]');
            const originalText = submitBtn.textContent;
            submitBtn.textContent = 'Submitting...';
            submitBtn.disabled = true;
            
            // Reset after 5 seconds (in case of errors)
            setTimeout(() => {
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
                isSubmitting = false;
            }, 5000);
        });
    </script>
</x-app-layout>