<?php
$data = json_decode(file_get_contents('storage/products.json'), true);
if (isset($data[0])) {
    echo "=== KEYS ===\n";
    print_r(array_keys($data[0]));
    echo "\n=== FIRST PRODUCT (Описание field) ===\n";
    echo "Raw Описание: \n";
    var_dump($data[0]['Описание'] ?? 'NOT FOUND');
    echo "\n\nLength: " . strlen($data[0]['Описание'] ?? '') . "\n";
}
