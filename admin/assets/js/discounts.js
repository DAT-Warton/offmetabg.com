function updateDiscountFields() {
    var type = document.getElementById('discount_type').value;
    var valueField = document.getElementById('value_field');
    var valueInput = document.getElementById('value_input');
    var valueLabel = document.getElementById('value_label');
    var valueHint = document.getElementById('value_hint');

    if (type === 'free_shipping') {
        valueField.style.display = 'none';
        valueInput.removeAttribute('required');
        valueInput.value = '0';
    } else {
        valueField.style.display = 'block';
        valueInput.setAttribute('required', 'required');

        if (type === 'percentage') {
            valueLabel.textContent = 'Процент';
            valueHint.textContent = 'Процент отстъпка (напр. 10 за 10%)';
            valueInput.max = '100';
            valueInput.placeholder = '10';
        } else if (type === 'fixed') {
            valueLabel.textContent = 'Сума (€)';
            valueHint.textContent = 'Фиксирана сума в евро (напр. 5.00 за €5 отстъпка)';
            valueInput.removeAttribute('max');
            valueInput.placeholder = '5.00';
        }
    }
}

document.addEventListener('DOMContentLoaded', function() {
    updateDiscountFields();
});
