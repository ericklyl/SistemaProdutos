<?php

/**
 * Filtra os produtos pelo preço máximo.
 *
 * @param array $products Array de produtos.
 * @param float $maxPrice Preço máximo permitido para os produtos.
 * @return array Array de produtos com preço menor ou igual ao preço máximo.
 */
function filterProductsByPrice($products, $maxPrice) {
    // Verifica se o parâmetro $products é um array válido e não vazio.
    if (!is_array($products) || empty($products)) {
        return [];
    }

    // Filtra os produtos com base no preço máximo.
    $filteredProducts = array_filter($products, function($product) use ($maxPrice) {
        return isset($product['price']) && $product['price'] <= $maxPrice;
    });

    return $filteredProducts;
}

// Exemplo de entrada
$products = [
    ['id' => 1, 'name' => 'Caneta Azul', 'price' => 2.50],
    ['id' => 2, 'name' => 'Caderno de 10 Matérias', 'price' => 15.00],
    ['id' => 3, 'name' => 'Borracha', 'price' => 1.25],
    ['id' => 4, 'name' => 'Estojo para Lápis', 'price' => 25.00]
];

$maxPrice = 10.00;

// Chama a função e exibe o resultado
$filteredProducts = filterProductsByPrice($products, $maxPrice);
print_r($filteredProducts);

// Caso de teste para verificar a validação dos dados de entrada
$invalidProducts = "not an array";
$emptyProducts = [];

$filteredInvalidProducts = filterProductsByPrice($invalidProducts, $maxPrice);
$filteredEmptyProducts = filterProductsByPrice($emptyProducts, $maxPrice);

echo "Teste com entrada inválida:\n";
print_r($filteredInvalidProducts);

echo "Teste com array vazio:\n";
print_r($filteredEmptyProducts);

?>
