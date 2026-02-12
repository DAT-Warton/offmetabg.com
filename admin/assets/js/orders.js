function viewOrder(itemsJson) {
    var items = JSON.parse(itemsJson);
    var html = '<table style="width: 100%;"><thead><tr><th style="text-align: left;">Product</th><th>Qty</th><th>Price</th><th>Total</th></tr></thead><tbody>';

    items.forEach(function(item) {
        html += '<tr><td>' + item.name + '</td><td>' + item.quantity + '</td><td>$' + item.price.toFixed(2) + '</td><td>$' + (item.price * item.quantity).toFixed(2) + '</td></tr>';
    });

    html += '</tbody></table>';
    document.getElementById('orderItems').innerHTML = html;
    document.getElementById('orderModal').style.display = 'flex';
}

function closeOrderModal() {
    document.getElementById('orderModal').style.display = 'none';
}
