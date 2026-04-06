<script>
    let signatureState = { isDrawing: false, hasSignature: false };
    let votItemCount = {{ count(old('vot_items', $grantRequest?->vot_items ?? [])) }} || 1;
    let pdfjsLoaded = false;
    let previewObjectUrl = null;

    const oldVotItems = @json(old('vot_items', $grantRequest?->vot_items ?? []));

    document.addEventListener('DOMContentLoaded', function() {
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
        const container = document.getElementById('vot-items-container');
        const template = document.getElementById('vot-item-template');
        const clone = template.content.cloneNode(true);
        const row = clone.querySelector('.vot-item-row');

        const select = row.querySelector('.vot-code-select');
        const codeInput = row.querySelector('.vot-code-input');
        const descInput = row.querySelector('.vot-description-input');
        const amountInput = row.querySelector('.vot-amount-input');

        // Set names with current index
        codeInput.name = `vot_items[${votItemCount}][vot_code]`;
        descInput.name = `vot_items[${votItemCount}][description]`;
        amountInput.name = `vot_items[${votItemCount}][amount]`;

        // Apply initial data if provided
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

        const previewVot = document.getElementById('preview-vot');
        if (previewVot) {
            previewVot.textContent = itemCount > 0
                ? `${itemCount} item${itemCount > 1 ? 's' : ''} will be included`
                : 'Items you add below';
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
        if (!canvas) return;

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
        if (!canvas) return;

        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        signatureState.hasSignature = false;
        document.getElementById('signature_data').value = '';
    }

    // Form Submission
    const requestForm = document.getElementById('request-form');
    let isSubmitting = false;
    
    if (requestForm) {
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
            if (signatureCanvas) {
                document.getElementById('signature_data').value = signatureCanvas.toDataURL('image/png');
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
        if (!previewSection || !iframe) return;

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
            const selectedOption = select?.options[select.selectedIndex];
            const previewRequestType = document.getElementById('preview-request-type');
            if (previewRequestType && selectedOption) {
                previewRequestType.textContent = selectedOption.text;
            }
        } catch (error) {
            console.error('Error loading template preview:', error);
            previewSection?.classList.add('hidden');
        }
    }
</script>
