/**
 * WooCommerce Gift Box Builder Frontend JavaScript
 *
 * Handles step navigation, AJAX content loading, product selection,
 * modal interactions, summary updates, and adding the gift box to the cart.
 *
 * @package WooCommerceGiftBoxBuilder/Assets/JS
 * @version 1.0.1
 */
jQuery(document).ready(function($) {
    // Ensure wgbb_data is defined; this object is localized from PHP and contains
    // essential data like AJAX URLs, nonces, and translated strings.
    if (typeof wgbb_data === 'undefined') {
        console.error('wgbb_data is not defined. Make sure it is localized properly in PHP.');
        return; // Stop execution if essential data is missing.
    }

    // --- Global State and Variables ---

    var currentStep = 1; // Tracks the current active step in the builder.
    var totalSteps = 4;  // Total number of steps (1: Box, 2: Chocolates, 3: Additions, 4: Summary/Finalize).
    
    /**
     * giftBoxState: Client-side object holding the user's selections.
     * - selectedBox: Object representing the chosen box.
     *   { productId: 'id', name: 'Product Name', price_html: '...' (optional) }
     * - items: Array of objects representing chosen items for inside the box.
     *   Each item: { productId: '123', quantity: 2, variationId: '456' (if variable), 
     *                variationData: {...} (selected attributes), name: 'Product Name', 
     *                price_html: '...' (optional), type: 'chocolate'/'addition' }
     */
    let giftBoxState = {
        selectedBox: null,
        items: []
    };

    var bundleId = wgbb_data.bundle_id; // The ID of the current "Gift Box Bundle" CPT being used.

    // --- jQuery DOM Element Caching ---
    // Cache frequently accessed DOM elements to improve performance.
    var $builderWrapper = $('#wgbb-gift-box-builder'); // Main container for the gift box builder interface.
    if (!$builderWrapper.length) {
        console.error('#wgbb-gift-box-builder element not found in the DOM.');
        return; // Stop if the main container isn't present.
    }

    var $steps = $builderWrapper.find('.wgbb-step'); // Collection of all step divs.
    var $nextButton = $builderWrapper.find('#wgbb-next-button'); // Main "Next" navigation button.
    var $prevButton = $builderWrapper.find('#wgbb-prev-button'); // Main "Previous" navigation button.
    var $summaryContainer = $('#wgbb-summary'); // The div where the summary is rendered (inside #wgbb-summary-container).

    // Elements for Step 4 (Finalize)
    var $addToCartButton = $('#wgbb-add-to-cart-button');
    var $viewCartButton = $('#wgbb-view-cart-button');
    var $buildAnotherBoxButton = $('#wgbb-build-another-box-button');
    var $addToCartMessages = $('#wgbb-add-to-cart-messages'); // For displaying success/error messages on add to cart.

    // Elements for the Product Details Modal
    var $productModal = $('#wgbbProductModal');
    var $modalLoading = $productModal.find('.wgbb-modal-loading'); // Loading indicator within the modal.
    var $modalContentLoaded = $productModal.find('.wgbb-modal-content-loaded'); // Container for modal content once loaded.
    var currentPopupProductId = null;     // Stores the ID of the product currently shown in the modal.
    var currentPopupProductMaxStock = null; // Stores max stock for the product in the modal, if stock is managed.

    // --- Core Functions ---
    
    /**
     * Extended button visibility logic, specifically for handling the unique buttons on Step 4.
     * It also manages the main navigation buttons for other steps.
     * This function centralizes button state management based on the current step.
     */
    function updateButtonVisibilityExtended() {
        // Main navigation buttons
        $prevButton.toggle(currentStep > 1 && currentStep < totalSteps); // Show "Previous" if not on Step 1 and not on Step 4.
        
        // "Next" button logic:
        // Hide on Step 3 (as its text changes to "Review Your Box" and acts as the final "next" before Step 4 buttons)
        // and hide on Step 4 (where specific action buttons are shown).
        $nextButton.toggle(currentStep < totalSteps - 1); 

        if (currentStep === totalSteps - 1) { // On Step 3, leading to Step 4 (Review)
            // Show the "Next" button, change its text to "Review Your Box", and ensure it's enabled.
            $nextButton.text(wgbb_data.l10n.review_button_text).show().prop('disabled', false);
        } else if (currentStep < totalSteps - 1 ) { // For steps 1 and 2
             $nextButton.text(wgbb_data.l10n.next_button_text); // Standard "Next" text.
        }

        // Handle buttons specific to Step 4 (Finalize).
        if (currentStep === totalSteps) { // On Step 4
            $addToCartButton.show().prop('disabled', false); // Show and enable "Add to Cart".
            $viewCartButton.hide();                         // Hide "View Cart" initially.
            $buildAnotherBoxButton.hide();                  // Hide "Build Another Box" initially.
            $addToCartMessages.empty().hide();              // Clear any previous messages.
        } else {
            // Hide Step 4 buttons if not on Step 4.
            $addToCartButton.hide();
            $viewCartButton.hide();
            $buildAnotherBoxButton.hide();
            $addToCartMessages.empty().hide();
        }

        // Ensure "Next" button on Step 1 is disabled if no box is selected.
        if (currentStep === 1 && !giftBoxState.selectedBox) {
            $nextButton.prop('disabled', true);
        } else if (currentStep === 1 && giftBoxState.selectedBox) { // Enable if box is selected on step 1
             $nextButton.prop('disabled', false);
        }
    }

    /**
     * Displays the specified step and hides all others.
     * Updates the currentStep global variable and button visibility.
     * @param {number} stepNumber The step number to display.
     */
    function showStep(stepNumber) {
        $steps.hide(); // Hide all step divs.
        $('#wgbb-step-' + stepNumber).show(); // Show the target step div.
        currentStep = stepNumber; // Update the global current step.
        updateButtonVisibilityExtended(); // Update navigation buttons based on the new step.
    }

    /**
     * Renders the gift box summary based on the current `giftBoxState`.
     * Displays the selected box and items, with quantities and variation details.
     * Includes "Remove" buttons for each item.
     * The summary is displayed in the `#wgbb-summary` div, typically within a fixed container.
     */
    function renderSummary() {
        // Ensure the summary container exists in the DOM.
        if (!$summaryContainer.length) {
            console.warn('#wgbb-summary container not found, summary will not be rendered.');
            return;
        }

        $summaryContainer.empty(); // Clear previous summary content.
        // Use localized title or a default.
        var summaryHtml = '<h4 class="mb-3">' + (wgbb_data.l10n.summary_title || 'Your Gift Box') + '</h4>';
        var hasContent = false; // Flag to check if there's anything to display.

        // Display the selected box, if any.
        if (giftBoxState.selectedBox) {
            hasContent = true;
            summaryHtml += '<div class="summary-item summary-box mb-2 d-flex justify-content-between align-items-center">';
            summaryHtml += '<span><strong>' + (wgbb_data.l10n.box_label || 'Box') + ':</strong> ' + giftBoxState.selectedBox.name + '</span>';
            summaryHtml += ' <button class="btn btn-xs btn-danger wgbb-remove-item py-0 px-1" data-item-type="box" data-item-id="' + giftBoxState.selectedBox.productId + '"><small>' + (wgbb_data.l10n.remove_text || 'Remove') + '</small></button>';
            summaryHtml += '</div>';
        }

        // Display selected items, if any.
        if (giftBoxState.items.length > 0) {
            hasContent = true;
            summaryHtml += '<ul class="list-unstyled mb-0">';
            giftBoxState.items.forEach(function(item, index) {
                summaryHtml += '<li class="summary-item mb-1 d-flex justify-content-between align-items-center" data-index="' + index + '">';
                let itemDisplayText = item.name + ' (Qty: ' + item.quantity + ')';
                // Display variation details if present.
                if (item.variationData && Object.keys(item.variationData).length > 0) {
                    var varDetails = [];
                    $.each(item.variationData, function(key, value) {
                        // Don't display variation_id itself, or empty attribute values.
                        if (key !== 'variation_id' && value !== '') {
                             // Format attribute slugs (e.g., 'pa_color' or 'color') to be more readable.
                             let formattedKey = key.replace(/^attribute_pa_|^attribute_/, '').replace(/-/g, ' ');
                             formattedKey = formattedKey.charAt(0).toUpperCase() + formattedKey.slice(1); // Capitalize first letter.
                             let formattedValue = value.replace(/-/g, ' ').replace(/\b\w/g, l => l.toUpperCase()); // Capitalize each word of value.
                             varDetails.push(formattedKey + ': ' + formattedValue);
                        }
                    });
                    if(varDetails.length > 0) itemDisplayText += ' <small class="text-muted d-block"><em>' + varDetails.join(', ') + '</em></small>';
                }
                summaryHtml += '<span>' + itemDisplayText + '</span>';
                summaryHtml += ' <button class="btn btn-xs btn-danger wgbb-remove-item py-0 px-1" data-item-type="item" data-item-index="' + index + '"><small>' + (wgbb_data.l10n.remove_text || 'Remove') + '</small></button>';
                summaryHtml += '</li>';
            });
            summaryHtml += '</ul>';
        }

        // If no content (no box, no items), display an "empty" message.
        if (!hasContent) {
            summaryHtml += '<p class="text-muted mb-0">' + (wgbb_data.l10n.empty_summary_text || 'Your gift box is currently empty.') + '</p>';
        }

        $summaryContainer.html(summaryHtml); // Update the summary display.
    }

    /**
     * Loads content for a given step (typically Steps 2 or 3) via AJAX.
     * Displays a loading message, makes the AJAX call, and injects the returned HTML
     * into the step's `.card-body` for consistent Bootstrap card styling.
     * @param {number} stepNumber The step number for which to load content.
     */
    function loadStepContent(stepNumber) {
        var $stepContentContainer = $('#wgbb-step-' + stepNumber).find('.card-body'); 
        // Show a Bootstrap spinner as a loading indicator.
        $stepContentContainer.html('<div class="text-center py-5"><div class="spinner-border text-primary" role="status"><span class="sr-only">' + (wgbb_data.l10n.loading_text || 'Loading...') + '</span></div></div>'); 
        showStep(stepNumber); // Ensure the current step div is visible.

        $.ajax({
            url: wgbb_data.ajax_url,
            type: 'POST',
            data: {
                action: 'wgbb_load_step_content',    // WordPress AJAX action hook.
                nonce: wgbb_data.nonce,             // Security nonce for loading steps.
                bundle_id: bundleId,                // Current bundle ID.
                target_step: stepNumber             // The step number to load.
            },
            success: function(response) {
                if (response.success) {
                    $stepContentContainer.html(response.data.html); // Inject returned HTML (product grid).
                    // Prepend step title if not already part of the HTML from AJAX.
                    var stepTitle = '';
                    if (stepNumber === 2) stepTitle = wgbb_data.l10n.step2_title;
                    if (stepNumber === 3) stepTitle = wgbb_data.l10n.step3_title;
                    // Ensure title is only added if not present to avoid duplicates on potential re-loads.
                    if(stepTitle && $stepContentContainer.find('h2.card-title').length === 0) { // Check for specific class if titles are part of AJAX
                         $stepContentContainer.prepend('<h2 class="card-title text-center mb-4">' + stepTitle + '</h2>');
                    }
                } else {
                    // Display error message if AJAX call was not successful.
                    $stepContentContainer.html('<p class="alert alert-danger">' + (response.data.message || wgbb_data.l10n.error_loading_step) + '</p>');
                }
            },
            error: function(xhr, status, error) {
                // Handle AJAX transport errors.
                console.error('Error loading step ' + stepNumber + ':', status, error);
                $stepContentContainer.html('<p class="alert alert-danger">' + wgbb_data.l10n.error_loading_step + '</p>');
            }
            // No 'complete' callback needed here as button visibility is handled by showStep.
        });
    }

    // --- Event Handlers ---

    // Step 1: Box Selection
    // Handles click on "Select This Box" buttons using event delegation on the builder wrapper.
    $builderWrapper.on('click', '.wgbb-select-product-button', function() {
        var $card = $(this).closest('.wgbb-product-card');
        var productId = $card.data('product-id');
        var productName = $card.find('.wgbb-product-name').text(); // Get name from the card.
        
        // Update state with the selected box.
        giftBoxState.selectedBox = { 
            productId: productId, 
            name: productName
            // Price and other details could be fetched/stored if needed for summary calculations later.
        };

        // Visual feedback: highlight selected card, unhighlight others within Step 1.
        $builderWrapper.find('#wgbb-step-1 .wgbb-product-card.selected').removeClass('selected');
        $card.addClass('selected');
        
        $nextButton.prop('disabled', false); // Enable the "Next" button.
        renderSummary(); // Update the summary display.
    });

    // Main Navigation: "Next" Button
    $nextButton.on('click', function() {
        // On Step 1, ensure a box is selected before proceeding.
        if (currentStep === 1 && !giftBoxState.selectedBox) {
            alert(wgbb_data.l10n.select_box_alert); // Use localized alert.
            return;
        }

        var nextStepNumber = currentStep + 1;
        // Prevent going beyond the total number of steps (safety check).
        if (nextStepNumber > totalSteps) return; 

        // Load content via AJAX for Steps 2 and 3.
        if (nextStepNumber === 2 || nextStepNumber === 3) {
            loadStepContent(nextStepNumber);
        } else {
            // For Step 4 (Finalize/Review) or other steps not requiring AJAX load, just show the step.
            showStep(nextStepNumber); 
        }
    });

    // Main Navigation: "Previous" Button
    $prevButton.on('click', function() {
        var prevStepNumber = currentStep - 1;
        if (prevStepNumber < 1) return; // Prevent going before Step 1.
        showStep(prevStepNumber); // Show the previous step.
    });

    // Modal: Triggered by ".wgbb-add-to-box-button" on product cards in AJAX-loaded Steps 2 & 3.
    // Fetches product details and populates the modal for configuration (quantity, variations).
    $builderWrapper.on('click', '.wgbb-add-to-box-button', function() {
        var productId = $(this).data('product-id');
        if (!productId) {
            console.error('Product ID not found on "Configure Item" button.');
            return;
        }
        currentPopupProductId = productId; // Store ID for use when adding to box from modal.

        // Reset modal state and show loading indicator (Bootstrap spinner).
        $modalLoading.show();
        $modalContentLoaded.hide();
        $productModal.modal('show'); // Trigger Bootstrap modal.

        // Clear previous product details from modal elements to prevent stale data.
        $productModal.find('.wgbb-popup-product-name').text(wgbb_data.l10n.loading_text);
        $productModal.find('.wgbb-popup-product-image').attr('src', wgbb_data.placeholder_image_url);
        $productModal.find('.wgbb-popup-product-short-desc').html('');
        $productModal.find('.wgbb-popup-product-price').html('');
        $productModal.find('.wgbb-popup-product-variations').html('');
        $productModal.find('.wgbb-popup-qty').val(1).removeAttr('max'); // Reset quantity and max attribute.
        $productModal.find('.wgbb-popup-stock-warning').hide().text('');
        $productModal.find('.wgbb-popup-add-to-box-button').prop('disabled', false); // Re-enable button.

        // AJAX call to fetch product details for the modal.
        $.ajax({
            url: wgbb_data.ajax_url,
            type: 'POST',
            data: {
                action: 'wgbb_get_product_details_for_popup',
                nonce: wgbb_data.nonce_get_product_details, 
                product_id: productId
            },
            success: function(response) {
                $modalLoading.hide(); // Hide loading indicator.
                if (response.success) {
                    var product = response.data; // Product data from server.
                    // Populate modal with fetched product details.
                    $productModal.find('.wgbb-popup-product-name').text(product.name);
                    $productModal.find('.wgbb-popup-product-image').attr('src', product.image_url || wgbb_data.placeholder_image_url);
                    $productModal.find('.wgbb-popup-product-short-desc').html(product.short_description);
                    $productModal.find('.wgbb-popup-product-price').html(product.price_html);

                    // Handle stock management for the quantity input.
                    currentPopupProductMaxStock = product.stock_quantity; 
                    var $qtyInput = $productModal.find('.wgbb-popup-qty');
                    if (product.managing_stock) {
                        $qtyInput.attr('max', product.stock_quantity);
                        // If out of stock and backorders not allowed, disable adding.
                        if (product.stock_quantity <= 0 && !product.backorders_allowed) {
                             $productModal.find('.wgbb-popup-stock-warning').text(wgbb_data.l10n.out_of_stock).show();
                             $productModal.find('.wgbb-popup-add-to-box-button').prop('disabled', true);
                        }
                    } else {
                        $qtyInput.removeAttr('max'); // No stock management, remove max attribute.
                    }

                    // Handle Product Variations: Generate select dropdowns for attributes.
                    if (product.variations && product.variations.length > 0 && product.variation_attributes && Object.keys(product.variation_attributes).length > 0) {
                        var variationsHtml = '<div class="variations_form" data-product_id="' + productId + '">';
                        variationsHtml += '<table class="variations table table-sm table-borderless mb-0" cellspacing="0"><tbody>'; // Added Bootstrap table classes
                        $.each(product.variation_attributes, function(attributeSlug, attributeOptions) { // attributeSlug is like 'pa_color', attributeOptions is an array of option terms
                            var attributeLabel = attributeSlug; // Fallback label
                            // Attempt to get a nicer label (e.g. "Color" from "pa_color")
                            // This requires passing attribute labels from PHP or having a mapping.
                            // For simplicity, using the slug for now or assuming `attributeName` was meant to be attributeLabel from PHP.
                            // The PHP side `wgbb_ajax_get_product_details_for_popup` returns `variation_attributes` which is ` $variable_product->get_variation_attributes();`
                            // This is an array like: array( 'pa_color' => array( 'Red', 'Blue' ), 'pa_size' => array( 'Small', 'Large' ) )
                            // The `options` in the original JS referred to this inner array.
                            // The `attributeName` in original JS was the key (e.g. 'pa_color').
                            // The current `product.variation_attributes` from PHP is ` $variable_product->get_variation_attributes();`
                            // which provides attribute taxonomies and their option terms (names or slugs).
                            // Let's refine based on typical structure from `get_variation_attributes()`
                            // The keys of `product.variation_attributes` are the attribute labels (e.g., "Color", "Size")
                            // and values are arrays of option strings.

                            variationsHtml += '<tr>';
                            variationsHtml += '<td class="label pr-2 align-middle"><label for="' + attributeSlug.toLowerCase().replace(/ /g, '_') + '">' + attributeSlug + '</label></td>';
                            variationsHtml += '<td class="value">';
                            // Use attributeSlug for select name/id, ensuring it's unique and valid.
                            variationsHtml += '<select id="' + attributeSlug.toLowerCase().replace(/ /g, '_') + '" name="attribute_' + attributeSlug.toLowerCase().replace(/ /g, '_') + '" data-attribute_name="attribute_' + attributeSlug.toLowerCase().replace(/ /g, '_') + '" class="form-control form-control-sm wgbb-variation-select">';
                            variationsHtml += '<option value="">' + wgbb_data.l10n.choose_option + '</option>';
                            $.each(attributeOptions, function(index, optionValue) { 
                                 // Assuming attributeOptions is an array of option strings (names or slugs)
                                 // If it's an object per option, adjust accordingly.
                                 // WC often provides slugs. If names are needed and only slugs are here, mapping might be required.
                                 // The PHP side sends variation_attributes (from `get_variation_attributes`) which are labels -> array of option terms.
                                 // And `product.variations[...].attributes` which is attribute_slug -> option_slug.
                                 // For select options, we need the actual option values (slugs usually) and display names.
                                 // The `available_variations` structure usually has `attributes: { "attribute_pa_color": "blue" }`
                                 // The `variation_attributes` from PHP is `array( 'Color' => array('Blue', 'Red') )`
                                 // So, `attributeSlug` here is "Color", `attributeOptions` is `['Blue', 'Red']`.
                                 // We need to ensure the value sent is the slug, not the name, if attributes are taxonomy-based.
                                 // For now, assuming `optionValue` is what's needed for both value and display.
                                 // This might need refinement if options are complex objects or require slug/name distinction not directly available here.
                                 // Let's assume `optionValue` is the term name. Slugs would be better for values.
                                 // The `product.variations[n].attributes` provides the slug.
                                 // A more robust way is to iterate `product.variations` to find all unique slugs for an attribute.
                                 // For simplicity, if `optionValue` is just the name:
                                 let optionSlug = optionValue.toLowerCase().replace(/ /g, '-'); // Simple slugify
                                variationsHtml += '<option value="' + optionSlug + '">' + optionValue + '</option>';
                            });
                            variationsHtml += '</select>';
                            variationsHtml += '</td></tr>';
                        });
                        variationsHtml += '</tbody></table>';
                        variationsHtml += '<input type="hidden" name="variation_id" class="variation_id" value="0" />'; // For WC compatibility
                        variationsHtml += '<div class="single_variation_wrap mt-2" style="display:none;"><div class="woocommerce-variation single_variation"></div></div>';
                        variationsHtml += '</div>';
                        $productModal.find('.wgbb-popup-product-variations').html(variationsHtml);
                        // Trigger WooCommerce's variation form handling if present on the page.
                        // This helps in finding matching variations and updating price/stock display.
                        $productModal.find('.variations_form').trigger('check_variations');
                        $productModal.find('.variations_form').trigger('wc_variation_form');
                    } else {
                         $productModal.find('.wgbb-popup-product-variations').html(''); // Clear if no variations.
                    }

                    $modalContentLoaded.show(); // Show the populated content area.
                } else {
                    // Handle error in fetching product details.
                    $productModal.find('.wgbb-popup-product-name').text(wgbb_data.l10n.error_text);
                    $productModal.find('.wgbb-popup-product-short-desc').html('<p class="alert alert-danger">' + (response.data.message || wgbb_data.l10n.error_loading_details) + '</p>');
                    $modalContentLoaded.show(); // Show content area to display the error.
                }
            },
            error: function(xhr, status, error) {
                // Handle AJAX transport error.
                console.error('Error fetching product details:', status, error);
                $modalLoading.hide();
                $modalContentLoaded.show();
                $productModal.find('.wgbb-popup-product-name').text(wgbb_data.l10n.error_text);
                $productModal.find('.wgbb-popup-product-short-desc').html('<p class="alert alert-danger">' + wgbb_data.l10n.error_loading_details + '</p>');
            }
        });
    });

    // Modal: Quantity Increment/Decrement Buttons & Direct Input Handler
    // Handles clicks on '+' and '-' buttons for quantity.
    $productModal.on('click', '.wgbb-popup-qty-plus, .wgbb-popup-qty-minus', function() {
        var $qtyInput = $(this).closest('.input-group').find('.wgbb-popup-qty');
        var currentVal = parseInt($qtyInput.val()) || 0; // Ensure valid number, default to 0 if NaN.
        var max = $qtyInput.attr('max') ? parseInt($qtyInput.attr('max')) : null;
        var min = parseInt($qtyInput.attr('min')) || 1;

        if ($(this).hasClass('wgbb-popup-qty-plus')) {
            if (max !== null && currentVal >= max) {
                // Show stock warning if trying to exceed max stock.
                $productModal.find('.wgbb-popup-stock-warning').text(wgbb_data.l10n.max_stock_reached.replace('%s', max)).show();
                return; // Don't increase further.
            }
            $qtyInput.val(currentVal + 1);
        } else if ($(this).hasClass('wgbb-popup-qty-minus')) {
            if (currentVal > min) { // Ensure quantity doesn't go below min (usually 1).
                $qtyInput.val(currentVal - 1);
            }
        }
        $qtyInput.trigger('change'); // Trigger change event to validate and hide warning if applicable.
    });

    // Handles direct input or programmatic changes to the quantity field.
    $productModal.on('change input', '.wgbb-popup-qty', function() {
        var $qtyInput = $(this);
        var currentVal = parseInt($qtyInput.val()) || 0; // Ensure valid number.
        var min = parseInt($qtyInput.attr('min')) || 1;
        var max = $qtyInput.attr('max') ? parseInt($qtyInput.attr('max')) : null;

        if (currentVal < min) {
            $qtyInput.val(min); // Reset to min if below.
        }

        if (max !== null && currentVal > max) {
            $qtyInput.val(max); // Cap at max if exceeded.
            $productModal.find('.wgbb-popup-stock-warning').text(wgbb_data.l10n.max_stock_reached.replace('%s', max)).show();
        } else {
             $productModal.find('.wgbb-popup-stock-warning').hide(); // Hide warning if within limits.
        }
    });

    // Modal: "Add to Box" Button (inside the modal)
    // Adds the configured product from the modal to the `giftBoxState`.
    $productModal.on('click', '.wgbb-popup-add-to-box-button', function() {
        var productId = currentPopupProductId;
        var quantity = parseInt($productModal.find('.wgbb-popup-qty').val());
        var productName = $productModal.find('.wgbb-popup-product-name').text();
        // Get price HTML from the modal; this might be the simple product price or variation price if updated by WC JS.
        var productPriceHtml = $productModal.find('.single_variation_wrap .woocommerce-variation-price').html() || $productModal.find('.wgbb-popup-product-price').html();
        var variationData = {}; // To store selected attribute_name: value pairs.
        var variationId = null;   // To store the actual WooCommerce variation ID.

        // Collect selected variation data.
        var $variationForm = $productModal.find('.variations_form');
        if ($variationForm.length) {
            $variationForm.find('select[name^="attribute_"]').each(function() {
                var attributeName = $(this).data('attribute_name'); // e.g., "attribute_pa_color"
                variationData[attributeName] = $(this).val(); // e.g., "red"
            });
            // Attempt to get variation_id if WooCommerce's variation scripts have populated it.
            variationId = $variationForm.find('input.variation_id').val(); 
            if (variationId === "0" || !variationId) variationId = null; // Treat "0" as no specific variation found.
        }
        
        // Basic validation before adding to state.
        if (!productId) {
            console.error("Product ID is missing in modal context."); // Should not happen.
            return;
        }
        if (quantity <= 0) {
            alert(wgbb_data.l10n.quantity_greater_than_zero_alert || "Quantity must be greater than 0.");
            return;
        }
        
        // For variable products, ensure all attribute options are selected if a variation ID hasn't been resolved.
        var isVariable = $variationForm.length > 0;
        if (isVariable && !variationId) { // If it's variable but WC JS didn't find a variation_id
            var allOptionsSelected = true;
            $.each(variationData, function(key, value) {
                if (value === "" && key.startsWith("attribute_")) { 
                    allOptionsSelected = false;
                    return false; 
                }
            });
            if (!allOptionsSelected) { 
                 alert(wgbb_data.l10n.select_variations_alert);
                 return;
            }
            // If all options are selected but still no variationId, it might be an invalid combination
            // or WC's JS isn't fully engaged. Proceeding might add the parent variable product.
            // For now, we allow this if options are selected, but ideally variationId is found.
        }

        // Determine item type ('chocolate' or 'addition') based on the current main step.
        var itemType = (currentStep === 2) ? 'chocolate' : 'addition'; 

        // Check if this exact item (product ID + variation ID) already exists in the state to update quantity.
        var existingItemIndex = giftBoxState.items.findIndex(function(item) {
            var idMatch = item.productId.toString() === productId.toString();
            var variationMatch = (item.variationId || null) === (variationId || null); // Compare variation IDs (or null if simple).
            return idMatch && variationMatch;
        });

        if (existingItemIndex > -1) {
            // Item already exists, update its quantity.
            giftBoxState.items[existingItemIndex].quantity += quantity;
        } else {
            // Add as a new item to the state.
            giftBoxState.items.push({
                productId: productId,
                quantity: quantity,
                variationId: variationId, 
                variationData: variationData,    
                name: productName,
                price_html: productPriceHtml,    
                type: itemType                   
            });
        }
        renderSummary(); // Update the summary display.
        $productModal.modal('hide'); // Close the modal.
    });

    // Summary: "Remove" Item/Box Button
    // Handles clicks on "Remove" buttons in the summary area using event delegation.
    $(document).on('click', '.wgbb-remove-item', function() { 
        var itemType = $(this).data('item-type'); // 'box' or 'item'.
        var itemId = $(this).data('item-id');       // Product ID for the box.
        var itemIndex = $(this).data('item-index'); // Array index for items.

        if (itemType === 'box' && giftBoxState.selectedBox && giftBoxState.selectedBox.productId.toString() === itemId.toString()) {
            // Remove the selected box.
            giftBoxState.selectedBox = null;
            // Also remove .selected class from the card in Step 1 and disable "Next" button if on Step 1.
            $('#wgbb-step-1 .wgbb-product-card.selected').removeClass('selected');
            if(currentStep === 1) $nextButton.prop('disabled', true); 
        } else if (itemType === 'item' && typeof itemIndex !== 'undefined' && giftBoxState.items[itemIndex]) {
            // Remove an item from the `items` array.
            giftBoxState.items.splice(itemIndex, 1);
        }
        renderSummary(); // Update the summary display.
        // If on Step 1 and box was removed, the next button state is handled above.
        // Other button visibility updates are managed by updateButtonVisibilityExtended when steps change.
    });

    // Step 4: "Add to Cart" Button
    $addToCartButton.on('click', function() {
        // Basic validation: ensure a box is selected.
        if (!giftBoxState.selectedBox) {
            alert(wgbb_data.l10n.select_box_alert || 'Please select a box first.');
            return;
        }
        // Optional: Add further validation, e.g., requiring at least one item in the box.
        // if (giftBoxState.items.length === 0) { 
        //      alert('Please add items to your gift box.');
        //      return;
        // }

        // Update button state to indicate processing.
        $addToCartButton.prop('disabled', true).text(wgbb_data.l10n.adding_to_cart || 'Adding...');
        $addToCartMessages.empty().hide(); // Clear previous messages.

        // AJAX call to add the configured gift box to the WooCommerce cart.
        $.ajax({
            url: wgbb_data.ajax_url,
            type: 'POST',
            data: {
                action: 'wgbb_add_gift_box_to_cart',
                nonce: wgbb_data.nonce_add_to_cart,       // Security nonce for this action.
                gift_box_data: JSON.stringify(giftBoxState) // Send the entire gift box state.
            },
            success: function(response) {
                if (response.success) {
                    // On success, display success message and update UI.
                    $addToCartMessages.removeClass('error alert alert-danger').addClass('success alert alert-success').html(response.data.message || wgbb_data.l10n.add_to_cart_success).show();
                    $addToCartButton.hide(); // Hide "Add to Cart" button.
                    $viewCartButton.attr('href', response.data.cart_url || wgbb_data.cart_url).show(); // Show "View Cart".
                    $buildAnotherBoxButton.show().prop('disabled', false); // Show and enable "Build Another Box".
                    
                    // Trigger WooCommerce events to update mini-cart and other cart-related elements.
                    $(document.body).trigger('wc_fragment_refresh'); 
                    $(document.body).trigger('added_to_cart', [response.data.fragments, response.data.cart_hash]);
                } else {
                    // On failure, display error message and re-enable "Add to Cart" button.
                    $addToCartMessages.removeClass('success alert alert-success').addClass('error alert alert-danger').html(response.data.message || wgbb_data.l10n.add_to_cart_failure).show();
                    $addToCartButton.prop('disabled', false).text(wgbb_data.l10n.add_to_cart_button_text || 'Add to Cart');
                }
            },
            error: function(xhr, status, error) {
                // Handle AJAX transport errors.
                console.error('Add to cart error:', status, error);
                $addToCartMessages.removeClass('success alert alert-success').addClass('error alert alert-danger').html(wgbb_data.l10n.add_to_cart_failure).show();
                $addToCartButton.prop('disabled', false).text(wgbb_data.l10n.add_to_cart_button_text || 'Add to Cart');
            }
        });
    });

    // Step 4: "Build Another Box" Button
    $buildAnotherBoxButton.on('click', function() {
        // Reset the gift box state.
        giftBoxState = {
            selectedBox: null,
            items: []
        };
        renderSummary(); // Clear the summary display.

        // Reset UI elements from Step 1 (e.g., remove selection highlight).
        $('#wgbb-step-1 .wgbb-product-card.selected').removeClass('selected');
        
        // Navigate back to Step 1.
        showStep(1); 
        // Button visibility and states are automatically handled by showStep calling updateButtonVisibilityExtended.
    });

    // --- Initial Setup ---
    showStep(1);      // Display the first step on page load.
    renderSummary();  // Render the initial state of the summary (which will be empty).
});
>>>>>>> REPLACE
