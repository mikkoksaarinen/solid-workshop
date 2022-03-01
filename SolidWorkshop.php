<?php

namespace MyShop\Orders;

class OrderService
{
    public function executeQuery(string $sql)
    {
        $servername = "localhost";
        $username = "username";
        $password = "password";
        $database = "orders";

        $conn = new mysqli($servername, $username, $password, $database);
        if ($conn->connect_error) {
          die("Connection failed: " . $conn->connect_error);
        }

        if ($conn->query($sql) === TRUE) {
          echo "Query was successful";
        } else {
          echo "Error with sql query: " . $conn->error;
        }

        $conn->close();
    }
    
    public function order(int $productId, int $quantity, int $customerId)
    {
        $this->executeQuery(
            "INSERT INTO log (product_id, quantity, customer_id, timestamp) VALUES (:productId, :quantity, :customerId, datetime())",
            $productId, $quantity, $customerId
        );

        if ($quantity > 10) {
            throw new \Exception("Cannot order more than 10 pcs");
        }

        $inStockAmount = $this->executeQuery("SELECT amount FROM products WHERE id = $productId");

        if ($inStockAmount < $quantity) {
            throw new \Exception("Only have $inStockAmount pcs of products");
        }

        $product = $this->executeQuery("SELECT price, name FROM products WHERE id = :id", $productId);
        $customer =
            $this->executeQuery("SELECT shippingAddress, name, email FROM customer WHERE id = :id", $customerId);

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
