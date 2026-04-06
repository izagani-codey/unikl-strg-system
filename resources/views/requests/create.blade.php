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

                    @if ($errors->any())
                        <div class="mb-6 rounded-md border border-red-200 bg-red-50 p-4">
                            <h3 class="text-sm font-semibold text-red-800">Please fix the following before submitting:</h3>
                            <ul class="mt-2 list-disc pl-5 text-sm text-red-700">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    {{-- Request Information --}}
                    <div class="mb-6 border-b border-gray-200 pb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Request Information</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700">Request Type *</label>
                                <select name="request_type_id" id="request-type-select" class="w-full rounded border-gray-300" required onchange="loadTemplatePreview(this.value)">
                                    <option value="">Select Request Type</option>
                                    @foreach($requestTypes as $type)
                                        <option value="{{ $type->id }}" @selected((string) old('request_type_id') === (string) $type->id)>{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-bold text-gray-700">Priority</label>
                                <div class="flex items-center mt-2">
                                    <input type="checkbox" name="priority" value="1" class="rounded border-gray-300" @checked(old('priority'))>
                                    <label class="ml-2 text-sm text-gray-700">Mark as High Priority</label>
                                </div>
                            </div>
                        </div>

                        <!-- Template Preview Section -->
                        <div id="template-preview-section" class="hidden mb-6 border-b border-gray-200 pb-6">
                            <h3 class="text-lg font-semibold text-gray-900 mb-4">Template Preview</h3>
                            
                            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                                <!-- PDF Preview -->
                                <div>
                                    <h4 class="text-md font-medium text-gray-800 mb-2">Blank Form Preview</h4>
                                    <div class="border border-gray-300 rounded-lg bg-gray-50" style="height: 400px;">
                                        <iframe id="template-preview-iframe" src="" class="w-full h-full rounded" style="border: none;"></iframe>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2">This is the blank form that will be filled with your information</p>
                                </div>
                                
                                <!-- Auto-fill Preview -->
                                <div>
                                    <h4 class="text-md font-medium text-gray-800 mb-2">Auto-fill Information</h4>
                                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                        <h5 class="font-semibold text-blue-800 mb-3">Information that will be auto-filled:</h5>
                                        <ul class="space-y-2 text-sm">
                                            <li class="flex items-start">
                                                <span class="text-blue-600 mr-2">✓</span>
                                                <span><strong>Name:</strong> <span id="preview-name">{{ auth()->user()->name }}</span></span>
                                            </li>
                                            <li class="flex items-start">
                                                <span class="text-blue-600 mr-2">✓</span>
                                                <span><strong>Staff ID:</strong> <span id="preview-staff-id">{{ auth()->user()->staff_id ?? 'Not set' }}</span></span>
                                            </li>
                                            <li class="flex items-start">
                                                <span class="text-blue-600 mr-2">✓</span>
                                                <span><strong>Designation:</strong> <span id="preview-designation">{{ auth()->user()->designation ?? 'Not set' }}</span></span>
                                            </li>
                                            <li class="flex items-start">
                                                <span class="text-blue-600 mr-2">✓</span>
                                                <span><strong>Department:</strong> <span id="preview-department">{{ auth()->user()->department ?? 'Not set' }}</span></span>
                                            </li>
                                            <li class="flex items-start">
                                                <span class="text-blue-600 mr-2">✓</span>
                                                <span><strong>Phone:</strong> <span id="preview-phone">{{ auth()->user()->phone ?? 'Not set' }}</span></span>
                                            </li>
                                            <li class="flex items-start">
                                                <span class="text-blue-600 mr-2">✓</span>
                                                <span><strong>Employee Level:</strong> <span id="preview-employee-level">{{ auth()->user()->employee_level ?? 'Not set' }}</span></span>
                                            </li>
                                            <li class="flex items-start">
                                                <span class="text-blue-600 mr-2">✓</span>
                                                <span><strong>Email:</strong> <span id="preview-email">{{ auth()->user()->email }}</span></span>
                                            </li>
                                            <li class="flex items-start">
                                                <span class="text-blue-600 mr-2">✓</span>
                                                <span><strong>Request Type:</strong> <span id="preview-request-type">Will be set based on selection</span></span>
                                            </li>
                                            <li class="flex items-start">
                                                <span class="text-blue-600 mr-2">✓</span>
                                                <span><strong>VOT Items:</strong> <span id="preview-vot">Items you add below</span></span>
                                            </li>
                                            <li class="flex items-start">
                                                <span class="text-blue-600 mr-2">✓</span>
                                                <span><strong>Total Amount:</strong> <span id="preview-total">Calculated from VOT items</span></span>
                                            </li>
                                        </ul>
                                    </div>
                                    <p class="text-xs text-gray-500 mt-2">Your digital signature will also be included</p>
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-bold text-gray-700">Deadline</label>
                                <input type="date" name="deadline" value="{{ old('deadline') }}" class="w-full rounded border-gray-300 mt-1">
                                <p class="text-xs text-gray-500 mt-1">Optional deadline for this request</p>
                            </div>
                        </div>

                        <div class="mt-4">
                            <label class="block text-sm font-bold text-gray-700">Justification / Description *</label>
                            <textarea name="description" rows="4" class="w-full rounded border-gray-300 mt-1" placeholder="General description for this STRG request..." required>{{ old('description') }}</textarea>
                        </div>
                    </div>

                    {{-- Dynamic Form Fields Section --}}
                    <div id="dynamic-fields-section" class="mb-6 border-b border-gray-200 pb-6 {{ old('request_type_id') ? '' : 'hidden' }}">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Request Details</h3>
                        <p class="text-sm text-gray-600 mb-4">Please provide the specific information required for this request type.</p>
                        
                        <div id="dynamic-fields-container">
                            @if(old('request_type_id'))
                                @php
                                    $selectedType = $requestTypes->firstWhere('id', old('request_type_id'));
                                @endphp
                                @if($selectedType && $selectedType->field_schema)
                                    <x-dynamic-form-fields 
                                        :fields="$selectedType->field_schema" 
                                        prefix="dynamic_fields" 
                                        :values="old('dynamic_fields', [])" 
                                    />
                                @endif
                            @endif
                        </div>
                    </div>

                    {{-- VOT Line Items --}}
                    <div class="mb-6 border-b border-gray-200 pb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Budget Breakdown (VOT Items)</h3>
                        <p class="text-sm text-gray-600 mb-4">Choose VOT code from dropdown and enter any amount (no maximum cap enforced in system).</p>

                        <div id="vot-items-container" class="space-y-3"></div>
                        <button type="button" onclick="addVotItemRow()" class="mt-3 px-4 py-2 rounded bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 border-2 border-blue-800 transition-colors">
                            + Add VOT Item
                        </button>

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
                                               placeholder="0.00" step="0.01" min="0" onchange="calculateTotal()" required>
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
                        
                        <div class="mt-4 flex justify-between items-center bg-gray-100 p-4 rounded-lg">
                            <div class="text-sm font-medium text-gray-700">
                                Total Amount:
                            </div>
                            <div class="text-lg font-bold text-blue-600">
                                RM <span id="total-amount">0.00</span>
                            </div>
                        </div>
                    </div>

                    {{-- Digital Signature --}}
                    <div class="mb-6 border-b border-gray-200 pb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Digital Signature</h3>
                        
                        <div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg p-4">
                            <canvas id="signature-canvas" width="400" height="200" class="border border-gray-400 bg-white rounded cursor-crosshair touch-none select-none"></canvas>
                            
                            <div class="mt-4 flex justify-between">
                                <button type="button" onclick="clearSignature()" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition-colors">
                                    Clear Signature
                                </button>
                                
                                <div class="text-xs text-gray-500">
                                    Sign above using mouse or touch device
                                </div>
                            </div>
                        </div>
                        
                        <input type="hidden" name="signature_data" id="signature_data" value="{{ old('signature_data') }}" required>
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

                    <x-loading-button type="primary" class="w-full">
                        Submit Request for Verification
                    </x-loading-button>
                </form>

            </div>
        </div>
    </div>

    <script>
        let signatureState = { isDrawing: false, hasSignature: false };
        let votItemCount = 0;
        let pdfjsLoaded = false;
        let previewObjectUrl = null;

        const oldVotItems = @json(old('vot_items', []));

        document.addEventListener('DOMContentLoaded', function() {
            initializeSignaturePad();
            initializeVotItems();
            calculateTotal();
            updateVOTPreview();

            const requestTypeSelect = document.getElementById('request-type-select');
            if (requestTypeSelect && requestTypeSelect.value) {
                loadTemplatePreview(requestTypeSelect.value);
                loadDynamicFields(requestTypeSelect.value);
            }

            const votContainer = document.getElementById('vot-items-container');
            if (votContainer) {
                votContainer.addEventListener('input', updateVOTPreview);
                votContainer.addEventListener('change', updateVOTPreview);
            }
        });

        async function loadDynamicFields(requestTypeId) {
            const container = document.getElementById('dynamic-fields-container');
            const section = document.getElementById('dynamic-fields-section');
            
            if (!requestTypeId) {
                section.classList.add('hidden');
                container.innerHTML = '';
                return;
            }

            try {
                const response = await fetch(`/api/request-types/${requestTypeId}/fields`);
                if (!response.ok) {
                    section.classList.add('hidden');
                    return;
                }

                const data = await response.json();
                
                if (data.fields && data.fields.length > 0) {
                    // Render fields using the server-rendered component approach
                    // For now, we'll reload the page section or use a simpler approach
                    section.classList.remove('hidden');
                    
                    // Store the selected type ID for form submission
                    container.dataset.typeId = requestTypeId;
                } else {
                    section.classList.add('hidden');
                }
            } catch (error) {
                console.error('Error loading dynamic fields:', error);
                section.classList.add('hidden');
            }
        }

        function initializeVotItems() {
            if (Array.isArray(oldVotItems) && oldVotItems.length > 0) {
                oldVotItems.forEach((item) => addVotItemRow(item));
                return;
            }
            addVotItemRow();
        }

        function addVotItemRow(initialData = {}) {
            const container = document.getElementById('vot-items-container');
            const template = document.getElementById('vot-item-template');
            const clone = template.content.cloneNode(true);
            const row = clone.querySelector('.vot-item-row');

            const select = row.querySelector('.vot-code-select');
            const codeInput = row.querySelector('.vot-code-input');
            const descInput = row.querySelector('.vot-description-input');
            const amountInput = row.querySelector('.vot-amount-input');

            select.name = '';
            codeInput.name = `vot_items[${votItemCount}][vot_code]`;
            descInput.name = `vot_items[${votItemCount}][description]`;
            amountInput.name = `vot_items[${votItemCount}][amount]`;
            codeInput.required = true;
            descInput.required = true;

            if (initialData.vot_code) {
                select.value = initialData.vot_code;
                handleVotSelection(select);
            }

            if (initialData.amount !== undefined && initialData.amount !== null) {
                amountInput.value = initialData.amount;
            }

            select.addEventListener('change', function () {
                handleVotSelection(this);
                updateVOTPreview();
            });
            amountInput.addEventListener('input', () => {
                calculateTotal();
                updateVOTPreview();
            });

            container.appendChild(clone);
            votItemCount++;
            calculateTotal();
            updateVOTPreview();
        }

        function removeVotItemRow(button) {
            const totalRows = document.querySelectorAll('.vot-item-row').length;
            if (totalRows <= 1) {
                alert('At least one VOT item is required.');
                return;
            }
            const row = button.closest('.vot-item-row');
            row.remove();
            calculateTotal();
            updateVOTPreview();
        }

        function handleVotSelection(selectElement) {
            const row = selectElement.closest('.vot-item-row');
            const selectedOption = selectElement.options[selectElement.selectedIndex];
            const code = selectedOption.value || '';
            const description = selectedOption.dataset.description || '';

            row.querySelector('.vot-code-input').value = code;
            row.querySelector('.vot-description-input').value = description;
            row.querySelector('.vot-desc-preview').textContent = description ? `Description: ${description}` : '';
        }

        function calculateTotal() {
            const votInputs = document.querySelectorAll('.vot-amount-input');
            let total = 0;

            votInputs.forEach((input) => {
                total += parseFloat(input.value) || 0;
            });

            document.getElementById('total-amount').textContent = total.toFixed(2);
        }

        function initializeSignaturePad() {
            const canvas = document.getElementById('signature-canvas');
            const ctx = canvas.getContext('2d');
            ctx.lineJoin = 'round';
            ctx.lineCap = 'round';
            ctx.lineWidth = 2;
            ctx.strokeStyle = '#111827';

            const getPos = (event) => {
                const rect = canvas.getBoundingClientRect();
                const source = event.touches ? event.touches[0] : event;
                return {
                    x: source.clientX - rect.left,
                    y: source.clientY - rect.top,
                };
            };

            const startDraw = (event) => {
                event.preventDefault();
                signatureState.isDrawing = true;
                const pos = getPos(event);
                ctx.beginPath();
                ctx.moveTo(pos.x, pos.y);
            };

            const draw = (event) => {
                if (!signatureState.isDrawing) return;
                event.preventDefault();
                const pos = getPos(event);
                ctx.lineTo(pos.x, pos.y);
                ctx.stroke();
                signatureState.hasSignature = true;
            };

            const stopDraw = (event) => {
                if (event) event.preventDefault();
                signatureState.isDrawing = false;
            };

            canvas.addEventListener('mousedown', startDraw);
            canvas.addEventListener('mousemove', draw);
            window.addEventListener('mouseup', stopDraw);

            canvas.addEventListener('touchstart', startDraw, { passive: false });
            canvas.addEventListener('touchmove', draw, { passive: false });
            window.addEventListener('touchend', stopDraw, { passive: false });
        }

        function clearSignature() {
            const canvas = document.getElementById('signature-canvas');
            const ctx = canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            signatureState.hasSignature = false;
            document.getElementById('signature_data').value = '';
        }

        const requestForm = document.getElementById('request-form');
        let isSubmitting = false;
        requestForm.addEventListener('submit', function(e) {
            if (!signatureState.hasSignature) {
                e.preventDefault();
                alert('Please provide your digital signature before submitting.');
                return false;
            }

            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            isSubmitting = true;

            const signatureCanvas = document.getElementById('signature-canvas');
            document.getElementById('signature_data').value = signatureCanvas.toDataURL('image/png');

            return true;
        });

        function loadPDFJS() {
            if (pdfjsLoaded) return Promise.resolve();

            return new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js';
                script.onload = () => {
                    pdfjsLoaded = true;
                    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
                    resolve();
                };
                script.onerror = reject;
                document.head.appendChild(script);
            });
        }

        async function loadTemplatePreview(requestTypeId) {
            const previewSection = document.getElementById('template-preview-section');
            const iframe = document.getElementById('template-preview-iframe');
            const dynamicSection = document.getElementById('dynamic-fields-section');
            const dynamicContainer = document.getElementById('dynamic-fields-container');

            if (!requestTypeId) {
                previewSection.classList.add('hidden');
                dynamicSection.classList.add('hidden');
                return;
            }

            // Load dynamic fields via AJAX
            try {
                const fieldsResponse = await fetch(`/api/request-types/${requestTypeId}/fields`);
                if (fieldsResponse.ok) {
                    const fieldsData = await fieldsResponse.json();
                    if (fieldsData.html) {
                        dynamicContainer.innerHTML = fieldsData.html;
                        dynamicSection.classList.remove('hidden');
                    } else {
                        dynamicSection.classList.add('hidden');
                    }
                }
            } catch (error) {
                console.error('Error loading dynamic fields:', error);
                dynamicSection.classList.add('hidden');
            }

            // Continue with template preview loading
            try {
                const response = await fetch(`/request-types/${requestTypeId}/template`);
                if (!response.ok) {
                    previewSection.classList.add('hidden');
                    return;
                }

                const blob = await response.blob();
                const fileType = blob.type;

                if (previewObjectUrl) {
                    URL.revokeObjectURL(previewObjectUrl);
                    previewObjectUrl = null;
                }

                if (fileType === 'application/pdf') {
                    await loadPDFJS();
                    const arrayBuffer = await blob.arrayBuffer();
                    const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
                    const page = await pdf.getPage(1);
                    const viewport = page.getViewport({ scale: 1.2 });

                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;
                    await page.render({ canvasContext: context, viewport }).promise;

                    iframe.src = canvas.toDataURL('image/png');
                } else {
                    previewObjectUrl = URL.createObjectURL(blob);
                    iframe.src = previewObjectUrl;
                }

                previewSection.classList.remove('hidden');

                const select = document.getElementById('request-type-select');
                const selectedOption = select.options[select.selectedIndex];
                document.getElementById('preview-request-type').textContent = selectedOption.text;
            } catch (error) {
                console.error('Error loading template preview:', error);
                previewSection.classList.add('hidden');
            }
        }

        function updateVOTPreview() {
            const votItems = document.querySelectorAll('.vot-item-row');
            let itemCount = 0;

            votItems.forEach((row) => {
                const codeSelect = row.querySelector('.vot-code-select');
                const amountInput = row.querySelector('.vot-amount-input');
                if (codeSelect?.value && amountInput?.value) {
                    itemCount++;
                }
            });

            document.getElementById('preview-vot').textContent = itemCount > 0
                ? `${itemCount} item${itemCount > 1 ? 's' : ''} will be included`
                : 'Items you add below';

            const total = document.getElementById('total-amount').textContent || '0.00';
            document.getElementById('preview-total').textContent = `RM ${total}`;
        }
    </script>
</x-app-layout>
