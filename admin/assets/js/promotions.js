function updatePromotionFields() {
    var type = document.getElementById('promotionType').value;
    var visualFields = document.getElementById('visualFields');
    var salesFields = document.getElementById('salesFields');
    var productFields = document.getElementById('productFields');
    var categoryFields = document.getElementById('categoryFields');
    var buyXGetYFields = document.getElementById('buyXGetYFields');

    if (visualFields) visualFields.style.display = 'none';
    if (salesFields) salesFields.style.display = 'none';
    if (productFields) productFields.style.display = 'none';
    if (categoryFields) categoryFields.style.display = 'none';
    if (buyXGetYFields) buyXGetYFields.style.display = 'none';

    var visualTypes = ['banner', 'popup', 'notification', 'homepage'];
    if (visualTypes.includes(type)) {
        if (visualFields) visualFields.style.display = 'block';
    }

    var salesTypes = ['bundle', 'buy_x_get_y', 'product_discount', 'category_discount', 'cart_discount'];
    if (salesTypes.includes(type)) {
        if (salesFields) salesFields.style.display = 'block';

        if (type === 'bundle' || type === 'product_discount') {
            if (productFields) productFields.style.display = 'block';
        }

        if (type === 'buy_x_get_y') {
            if (productFields) productFields.style.display = 'block';
            if (buyXGetYFields) buyXGetYFields.style.display = 'block';
        }

        if (type === 'category_discount') {
            if (categoryFields) categoryFields.style.display = 'block';
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('promotionType')) {
        updatePromotionFields();
    }
});
