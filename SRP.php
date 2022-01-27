<?php

class OrderService
{
    public function order(int $productId, int $quantity, int $customerId)
    {
        $this->db->executeQuery(
            "INSERT INTO log (product_id, quantity, customer_id, timestamp) VALUES (:productId, :quantity, :customerId, datetime())",
            $productId, $quantity, $customerId
        );

        if ($quantity > 10) {
            throw new \Exception("Cannot order more than 10 pcs");
        }

        $inStockAmount = $this->db->executeQuery("SELECT amount FROM products WHERE id = $productId");

        if ($inStockAmount < $quantity) {
            throw new \Exception("Only have $inStockAmount pcs of products");
        }

        $product = $this->db->executeQuery("SELECT price, name FROM products WHERE id = :id", $productId);
        $customer =
            $this->db->executeQuery("SELECT shippingAddress, name, email FROM customer WHERE id = :id", $customerId);

        $message = "Send to customer $product $customer $quantity";
        mail('warehouse@example.com', 'new order', $message);

        $message = "Your order is on handled ..";
        mail($customer['email'], 'thanks for ordering', $message);

        $localVat = match (true) {
            $this->getCountry($customer['shippingAddress']) === 'Finland' => 24,
            default => 0,
        };
        $message = "please pay".  $product['price'] * ($localVat / 100 + 1 );
        mail($customer['email'], 'invoice for order', $message);
    }
}
