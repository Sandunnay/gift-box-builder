jQuery(document).ready(function($) {
    function toggleFields(selector) {
        var type = $(selector).val();
        var parent = $(selector).closest('p').parent(); // Go up to the container of this step's fields

        if (type === 'products') {
            parent.find('.wgbb-products-field').show();
            parent.find('.wgbb-category-field').hide();
        } else if (type === 'category') {
            parent.find('.wgbb-products-field').hide();
            parent.find('.wgbb-category-field').show();
        } else {
            parent.find('.wgbb-products-field').hide();
            parent.find('.wgbb-category-field').hide();
        }
    }

    // Initial toggle on page load
    $('.wgbb-type-selector').each(function() {
        toggleFields(this);
    });

    // Toggle on change
    $(document).on('change', '.wgbb-type-selector', function() {
        toggleFields(this);
    });
});
