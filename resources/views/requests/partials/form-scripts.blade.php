<script>
    let signatureState = { isDrawing: false, hasSignature: false };
    let votItemCount = {{ count(old('vot_items', $grantRequest?->vot_items ?? [])) }} || 1;
    let pdfjsLoaded = false;
    let previewObjectUrl = null;

    const oldVotItems = @json(old('vot_items', $grantRequest?->vot_items ?? []));

    // Add loading animation CSS
    const style = document.createElement('style');
    style.textContent = `
        @keyframes loading {
            0% { background-position: 100% 0; }
            100% { background-position: 0 0; }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .loading-overlay.active {
            opacity: 1;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #3490dc;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        .loading-content {
            background: white;
            padding: 20px 30px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .btn-loading {
            pointer-events: none;
            opacity: 0.7;
            position: relative;
        }
        
        .btn-loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            margin-left: 8px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
    `;
    document.head.appendChild(style);

    document.addEventListener('DOMContentLoaded', function() {
        // Create loading overlay
        const loadingOverlay = document.createElement('div');
        loadingOverlay.className = 'loading-overlay';
        loadingOverlay.innerHTML = `
            <div class="loading-spinner"></div>
            <div class="loading-content">
                <h3 class="text-lg font-semibold mb-2">Processing...</h3>
                <p class="text-gray-600">Please wait while we process your request.</p>
            </div>
        `;
        document.body.appendChild(loadingOverlay);

        initializeSignaturePad();
        calculateTotal();
        updateVOTPreview();

        const requestTypeSelect = document.getElementById('request-type-select');
        if (requestTypeSelect && requestTypeSelect.value) {
            loadTemplatePreview(requestTypeSelect.value);
        }

        const votContainer = document.getElementById('vot-items-container');
        if (votContainer) {
            votContainer.addEventListener('input', updateVOTPreview);
            votContainer.addEventListener('change', updateVOTPreview);
        }
    });

    // VOT Items Functions
    function addVotItemRow(initialData = {}) {
        console.log('Adding VOT item with data:', initialData);
        const container = document.getElementById('vot-items-container');
        const template = document.getElementById('vot-item-template');
        const clone = template.content.cloneNode(true);
        
        // Update index for new row
        const rows = container.querySelectorAll('.vot-item-row');
        const newIndex = rows.length;
        
        // Update all name attributes with new index
        clone.querySelectorAll('select, input, button').forEach(element => {
            if (element.name) {
                element.name = element.name.replace(/index/g, newIndex);
            }
        });
        
        // Update onclick handlers with new index
        const removeBtn = clone.querySelector('button');
        if (removeBtn) {
            removeBtn.setAttribute('onclick', `removeVotItemRow(this)`);
        }
        
        // Apply initial data if provided
        const select = clone.querySelector('.vot-code-select');
        const amountInput = clone.querySelector('.vot-amount-input');
        if (initialData.vot_code) {
            select.value = initialData.vot_code;
            handleVotSelection(select);
        }
        if (initialData.amount !== undefined && initialData.amount !== null) {
            amountInput.value = initialData.amount;
        }
        
        // Add event listeners
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
        console.log('VOT item added, new count:', votItemCount);
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
        
        console.log('Calculating total from', votInputs.length, 'VOT inputs');
        
        votInputs.forEach((input, index) => {
            const value = parseFloat(input.value) || 0;
            console.log(`VOT item ${index}:`, value);
            total += value;
        });
        
        const totalElement = document.getElementById('total-amount');
        const previewTotal = document.getElementById('preview-total');
        
        if (totalElement) {
            totalElement.textContent = total.toFixed(2);
            console.log('Total calculated:', total.toFixed(2));
        }
        
        if (previewTotal) {
            previewTotal.textContent = `RM ${total.toFixed(2)}`;
        }
    }

    function updateVOTPreview() {
        const votItems = document.querySelectorAll('.vot-item-row');
        let itemCount = 0;
        
        console.log('Updating VOT preview from', votItems.length, 'VOT rows');

        votItems.forEach((row, index) => {
            const codeSelect = row.querySelector('.vot-code-select');
            const amountInput = row.querySelector('.vot-amount-input');
            if (codeSelect?.value && amountInput?.value) {
                itemCount++;
                console.log(`VOT item ${index}: code=${codeSelect.value}, amount=${amountInput.value}`);
            }
        });

        const previewVot = document.getElementById('preview-vot');
        if (previewVot) {
            const text = itemCount > 0
                ? `${itemCount} item${itemCount > 1 ? 's' : ''} will be included`
                : 'Items you add below';
            previewVot.textContent = text;
            console.log('VOT preview updated:', text);
        }

        const totalEl = document.getElementById('total-amount');
        const previewTotal = document.getElementById('preview-total');
        if (previewTotal && totalEl) {
            previewTotal.textContent = `RM ${totalEl.textContent || '0.00'}`;
        }
    }

    // Signature Functions
    function initializeSignaturePad() {
        const canvas = document.getElementById('signature-canvas');
        if (!canvas) {
            console.error('Signature canvas not found');
            return;
        }

        const ctx = canvas.getContext('2d');
        ctx.lineJoin = 'round';
        ctx.lineCap = 'round';
        ctx.lineWidth = 2;
        ctx.strokeStyle = '#111827';

        // Set canvas size properly - use display size
        const rect = canvas.getBoundingClientRect();
        const dpr = window.devicePixelRatio || 1;
        
        canvas.width = rect.width * dpr;
        canvas.height = rect.height * dpr;
        
        // Scale the drawing context to account for device pixel ratio
        ctx.scale(dpr, dpr);
        
        // Reset canvas to white background
        ctx.fillStyle = '#FFFFFF';
        ctx.fillRect(0, 0, canvas.width, canvas.height);

        const getPos = (event) => {
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            const source = event.touches ? event.touches[0] : event;
            return {
                x: (source.clientX - rect.left) * scaleX,
                y: (source.clientY - rect.top) * scaleY,
            };
        };

        const startDraw = (event) => {
            event.preventDefault();
            signatureState.isDrawing = true;
            const pos = getPos(event);
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
            console.log('Started drawing at:', pos);
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
            console.log('Stopped drawing, has signature:', signatureState.hasSignature);
        };

        // Mouse events
        canvas.addEventListener('mousedown', startDraw);
        canvas.addEventListener('mousemove', draw);
        canvas.addEventListener('mouseup', stopDraw);
        canvas.addEventListener('mouseout', stopDraw);

        // Touch events
        canvas.addEventListener('touchstart', startDraw, { passive: false });
        canvas.addEventListener('touchmove', draw, { passive: false });
        canvas.addEventListener('touchend', stopDraw, { passive: false });

        console.log('Signature pad initialized with size:', canvas.width, 'x', canvas.height);
    }

    function clearSignature() {
        const canvas = document.getElementById('signature-canvas');
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Reset white background
        ctx.fillStyle = '#FFFFFF';
        ctx.fillRect(0, 0, canvas.width, canvas.height);
        
        signatureState.hasSignature = false;
        const signatureInput = document.getElementById('signature_data');
        if (signatureInput) {
            signatureInput.value = '';
        }
        console.log('Signature cleared');
    }

    // Form Submission
    const requestForm = document.getElementById('request-form');
    let isSubmitting = false;
    
    if (requestForm) {
        requestForm.addEventListener('submit', function(e) {
            console.log('Form submission triggered, has signature:', signatureState.hasSignature);
            
            if (!signatureState.hasSignature) {
                e.preventDefault();
                alert('Please provide your digital signature before submitting. Draw your signature in the signature box above.');
                return false;
            }

            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            isSubmitting = true;

            const signatureCanvas = document.getElementById('signature-canvas');
            if (signatureCanvas) {
                const signatureData = signatureCanvas.toDataURL('image/png');
                document.getElementById('signature_data').value = signatureData;
                console.log('Signature data saved, length:', signatureData.length);
            }

            return true;
        });
    }

    // Template Preview Functions
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

        console.log('Loading template preview for request type:', requestTypeId);

        if (!requestTypeId) {
            previewSection?.classList.add('hidden');
            dynamicSection?.classList.add('hidden');
            return;
        }

        // Load dynamic fields via AJAX
        if (dynamicContainer && dynamicSection) {
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
                dynamicSection?.classList.add('hidden');
            }
        }

        // Continue with template preview loading
        if (!previewSection || !iframe) {
            console.error('Template preview elements not found');
            return;
        }

        return true;
    });
}

// Template Preview Functions
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

    console.log('Loading template preview for request type:', requestTypeId);

    if (!requestTypeId) {
        previewSection?.classList.add('hidden');
        dynamicSection?.classList.add('hidden');
        return;
    }

    // Load dynamic fields via AJAX
    if (dynamicContainer && dynamicSection) {
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
            dynamicSection?.classList.add('hidden');
        }
    }

    // Form submission with loading states
    function submitRequestForm() {
        const form = document.querySelector('form[method="POST"]');
        if (!form) return;

        const submitButton = form.querySelector('button[type="submit"]');
        if (!submitButton) return;

        // Prevent double submission
        if (form.dataset.submitting === 'true') {
            console.log('Form already submitting...');
            return;
        }

        form.dataset.submitting = 'true';
        submitButton.classList.add('btn-loading');
        submitButton.disabled = true;

        // Show loading overlay
        const loadingOverlay = document.querySelector('.loading-overlay');
        if (loadingOverlay) {
            loadingOverlay.classList.add('active');
        }

        // Allow form to submit normally
        return true;
    }

    // Hide loading overlay
    function hideLoadingOverlay() {
        const loadingOverlay = document.querySelector('.loading-overlay');
        if (loadingOverlay) {
            loadingOverlay.classList.remove('active');
        }

        const submitButton = document.querySelector('button[type="submit"]');
        if (submitButton) {
            submitButton.classList.remove('btn-loading');
            submitButton.disabled = false;
        }

        const form = document.querySelector('form[method="POST"]');
        if (form) {
            delete form.dataset.submitting;
        }
    }

    // Add form submission listener
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.querySelector('form[method="POST"]');
        if (form) {
            form.addEventListener('submit', function(e) {
                if (!submitRequestForm()) {
                    e.preventDefault();
                }
            });
        }

        // Hide loading overlay on page load (in case of refresh)
        hideLoadingOverlay();
    });

    // Auto-hide loading overlay after 10 seconds (fallback)
    setTimeout(function() {
        hideLoadingOverlay();
    }, 10000);
        
        // Hide error section if visible
        if (errorSection) {
            errorSection.classList.add('hidden');
        }
        
        // For now, just load PDF directly in iframe
        if (iframe) {
            iframe.src = `/request-types/${requestTypeId}/template`;
            
            // Add load event listener to iframe
            iframe.onload = function() {
                console.log('Template loaded successfully');
                iframe.style.background = 'none';
                iframe.style.animation = 'none';
                if (previewSection) {
                    previewSection.classList.remove('hidden');
                }
            };
            
            iframe.onerror = function() {
                console.error('Failed to load template');
                if (iframe) {
                    iframe.style.background = 'none';
                    iframe.style.animation = 'none';
                }
                if (previewSection) {
                    previewSection.classList.add('hidden');
                }
                if (errorSection) {
                    errorSection.classList.remove('hidden');
                }
            };
        }

        const select = document.getElementById('request-type-select');
        const selectedOption = select?.options[select.selectedIndex];
        const previewRequestType = document.getElementById('preview-request-type');
        if (previewRequestType && selectedOption) {
            previewRequestType.textContent = selectedOption.text;
        }
    } catch (error) {
        console.error('Error loading template preview:', error);
        const previewSection = document.getElementById('template-preview-section');
        const errorSection = document.getElementById('template-error');
        
        if (previewSection) {
            previewSection.classList.add('hidden');
        }
        if (errorSection) {
            errorSection.classList.remove('hidden');
        }
    }
}
</script>
