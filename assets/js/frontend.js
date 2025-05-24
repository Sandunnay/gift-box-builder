// assets/js/frontend.js
document.addEventListener('DOMContentLoaded', function () {
    const giftBoxBuilderContainer = document.querySelector('.gift-box-builder-container');
    
    // Ensure the container exists before proceeding
    if (!giftBoxBuilderContainer) {
        return;
    }

    // Bootstrap Modal Initialization
    const gbbProductModalElement = document.getElementById('gbbProductModal');
    let productModalInstance;
    if (gbbProductModalElement) {
        productModalInstance = new bootstrap.Modal(gbbProductModalElement);
    }

    const steps = giftBoxBuilderContainer.querySelectorAll('.gbb-step');
    const nextButton = giftBoxBuilderContainer.querySelector('#gbb-nav-next');
    const prevButton = giftBoxBuilderContainer.querySelector('#gbb-nav-prev');
    let currentStep = 0; // 0-indexed

    let currentGiftBox = {
        bundleId: null,
        step1_selectedBoxId: null,
        step2_items: [], // Will store { productId, quantity, productType, variationId (optional), attributes (optional) }
        step3_items: []  // Same structure
    };

    // Populate bundleId from data attribute
    if (giftBoxBuilderContainer.dataset.bundleId) {
        currentGiftBox.bundleId = parseInt(giftBoxBuilderContainer.dataset.bundleId, 10);
    }

    function showStep(stepIndex) {
        steps.forEach((step, index) => {
            step.classList.toggle('d-none', index !== stepIndex);
        });

        if (prevButton) {
            prevButton.classList.toggle('d-none', stepIndex === 0);
        }
        if (nextButton) {
            nextButton.classList.toggle('d-none', stepIndex === steps.length - 1);
            if (stepIndex === steps.length - 1) { 
                nextButton.textContent = 'Finalize'; 
            } else {
                nextButton.textContent = 'Next';
            }
        }
        currentStep = stepIndex;
    }

    if (nextButton) {
        nextButton.addEventListener('click', function () {
            if (currentStep === 0 && currentGiftBox.step1_selectedBoxId === null) {
                alert('Please select a box before proceeding.'); 
                return;
            }
            // Add validation for step 2 and 3 items if needed
            // if (currentStep === 1 && currentGiftBox.step2_items.length === 0) {
            //     alert('Please select at least one item for Step 2.');
            //     return;
            // }
            // if (currentStep === 2 && currentGiftBox.step3_items.length === 0) {
            //     alert('Please select at least one item for Step 3.');
            //     return;
            // }
            
            if (currentStep < steps.length - 1) {
                showStep(currentStep + 1);
            } else {
                console.log('Finalize clicked. Gift box details:', currentGiftBox);
                alert('Gift box finalized (see console for details). Further action (e.g., add to cart) not yet implemented.');
            }
        });
    }

    if (prevButton) {
        prevButton.addEventListener('click', function () {
            if (currentStep > 0) {
                showStep(currentStep - 1);
            }
        });
    }

    // Event delegation for selecting a box in Step 1
    const step1Content = giftBoxBuilderContainer.querySelector('#gbb-step-1 .gbb-step-content');
    if (step1Content) {
        step1Content.addEventListener('click', function(event) {
            const target = event.target;
            const selectableBox = target.closest('.gbb-selectable-box');
            
            if (selectableBox && (target.classList.contains('gbb-select-button') || target.closest('.gbb-select-button'))) {
                event.preventDefault(); 

                const selectedProductId = parseInt(selectableBox.dataset.productId, 10);
                currentGiftBox.step1_selectedBoxId = selectedProductId;
                console.log('Box selected:', currentGiftBox);

                step1Content.querySelectorAll('.gbb-selectable-box .card').forEach(card => card.classList.remove('border-primary', 'shadow'));
                step1Content.querySelectorAll('.gbb-selectable-box .card .gbb-select-button').forEach(btn => {
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-outline-primary');
                    btn.textContent = 'Select Box';
                });
                
                const cardElement = selectableBox.querySelector('.card') || selectableBox; 
                cardElement.classList.add('border-primary', 'shadow');
                const buttonElement = selectableBox.querySelector('.gbb-select-button');
                if (buttonElement) {
                    buttonElement.classList.remove('btn-outline-primary');
                    buttonElement.classList.add('btn-primary');
                    buttonElement.textContent = 'Selected';
                }
                
                if (nextButton && currentStep === 0) { 
                    setTimeout(() => {
                        nextButton.click();
                    }, 200);
                }
            }
        });
    }

    // Function to populate modal with product data
    function populateModal(product) {
        document.getElementById('gbbProductModalLabel').textContent = product.name; 
        document.getElementById('gbb-modal-product-name-display').textContent = product.name;
        
        const imageContainer = document.getElementById('gbb-modal-image-container');
        imageContainer.innerHTML = `<img src="${product.image_url || ''}" alt="${product.name}" class="img-fluid rounded mb-3" style="max-height: 300px; object-fit: contain;">`;
        
        document.getElementById('gbb-modal-short-description').innerHTML = product.short_description;
        document.getElementById('gbb-modal-price').innerHTML = product.price_html;
        
        const modalAddItemButton = document.getElementById('gbb-modal-add-item-button');
        modalAddItemButton.dataset.productId = product.product_id; 
        modalAddItemButton.dataset.productType = product.product_type;

        const quantityInput = document.getElementById('gbb-modal-quantity');
        quantityInput.value = 1; 
        quantityInput.dataset.maxStock = product.max_stock || ''; 
        
        const stockMessage = document.getElementById('gbb-modal-stock-message');
        if (product.is_in_stock) {
            stockMessage.textContent = product.manage_stock && product.stock_quantity !== null ? `In stock: ${product.stock_quantity}` : 'In stock';
            modalAddItemButton.disabled = false;
        } else {
            stockMessage.textContent = 'Out of stock';
            modalAddItemButton.disabled = true;
        }

        document.getElementById('gbb-modal-variations-container').innerHTML = ''; 
    }

    // Event delegation for ".gbb-view-details-button"
    giftBoxBuilderContainer.addEventListener('click', function(event) {
        const viewDetailsButton = event.target.closest('.gbb-view-details-button');
        if (viewDetailsButton) {
            event.preventDefault();
            const productId = viewDetailsButton.dataset.productId;
            const stepNumber = viewDetailsButton.dataset.stepNumber;

            const modalAddItemButton = document.getElementById('gbb-modal-add-item-button');
            if (modalAddItemButton) {
                modalAddItemButton.dataset.currentStep = stepNumber; 
            }
            
            const ajaxData = new URLSearchParams();
            ajaxData.append('action', 'gbb_get_product_details');
            ajaxData.append('nonce', gbb_ajax_object.nonce); 
            ajaxData.append('product_id', productId);

            fetch(gbb_ajax_object.ajax_url, { 
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: ajaxData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const product = data.data;
                    populateModal(product);
                    if (productModalInstance) {
                        productModalInstance.show();
                    }
                } else {
                    console.error('Error fetching product details:', data.data.message);
                    alert('Error: ' + data.data.message);
                }
            })
            .catch(error => {
                console.error('AJAX request failed:', error);
                alert('Request failed. Please try again.');
            });
        }
    });

    // Quantity Selector Logic within the modal
    if (gbbProductModalElement) {
        gbbProductModalElement.addEventListener('click', function(event) {
            if (event.target.classList.contains('gbb-qty-btn')) {
                const action = event.target.dataset.action;
                const quantityInput = document.getElementById('gbb-modal-quantity');
                let currentValue = parseInt(quantityInput.value, 10);
                const maxStock = quantityInput.dataset.maxStock ? parseInt(quantityInput.dataset.maxStock, 10) : Infinity;

                if (action === 'increase') {
                    if (currentValue < maxStock) {
                        quantityInput.value = currentValue + 1;
                    } else if (maxStock !== Infinity) {
                         alert('Cannot add more than available stock.');
                    }
                } else if (action === 'decrease') {
                    if (currentValue > 1) {
                        quantityInput.value = currentValue - 1;
                    }
                }
            }
        });
    }

    // "Add to Box" button logic in the modal
    const modalAddItemButton = document.getElementById('gbb-modal-add-item-button');
    if (modalAddItemButton) {
        modalAddItemButton.addEventListener('click', function() {
            const productId = this.dataset.productId;
            const productType = this.dataset.productType;
            const stepNumber = this.dataset.currentStep; 
            const quantityInput = document.getElementById('gbb-modal-quantity');
            const quantity = parseInt(quantityInput.value, 10);

            if (!productId || !stepNumber || isNaN(quantity) || quantity <= 0) {
                alert('Invalid item data. Please try again.');
                return;
            }

            const item = {
                productId: parseInt(productId, 10),
                quantity: quantity,
                productType: productType
                // variationId: null, // For future variable product implementation
                // attributes: {}     // For future variable product implementation
            };

            let targetArray;
            if (stepNumber === '2') {
                targetArray = currentGiftBox.step2_items;
            } else if (stepNumber === '3') {
                targetArray = currentGiftBox.step3_items;
            } else {
                console.error('Invalid step number for adding item:', stepNumber);
                alert('Invalid step number for adding item.');
                return;
            }

            const existingItemIndex = targetArray.findIndex(i => i.productId === item.productId /* && (productType !== 'variable' || i.variationId === item.variationId) */);

            if (existingItemIndex > -1) {
                targetArray[existingItemIndex].quantity = item.quantity; 
            } else {
                targetArray.push(item);
            }

            console.log('Updated currentGiftBox:', currentGiftBox);
            // alert('Item added/updated in your selection! (See console for details)'); // Optional feedback
            
            if (productModalInstance) {
                productModalInstance.hide();
            }
        });
    }

    if (steps.length > 0) {
        showStep(currentStep); // Initialize first step display
    } else {
        if (prevButton) prevButton.classList.add('d-none');
        if (nextButton) nextButton.classList.add('d-none');
    }
});
