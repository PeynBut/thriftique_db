<?php
require_once 'DBconnection.php';

class DBoperations {
    private $con;

    function __construct() {
        $db = new DBconnection();
        $this->con = $db->connection();
    }
    // Create a new user
    /** Check if user exists */
    private function isUserExist($email) {
        $stmt = $this->con->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    /** Crud -> Create */
    public function createUser($firstName, $lastName, $email, $password) {
        if ($this->isUserExist($email)) {
            return 0;
        } else {
            $hashedPassword = md5($password); // Hash the password using md5
            $token = bin2hex(random_bytes(16)); // Generate a random token
            $stmt = $this->con->prepare("INSERT INTO `users` (`firstName`, `lastName`, `email`, `password`, `token`) VALUES (?, ?, ?, ?, ?);");
            $stmt->bind_param("sssss", $firstName, $lastName, $email, $hashedPassword, $token);

            if ($stmt->execute()) {
                return array('status' => 1, 'token' => $token);
            } else {
                return array('status' => 2);
            }
        }
    }

    public function userLogin($email, $pass) {
        $password = md5($pass); // Ensure this matches the hashing method used during registration
        $stmt = $this->con->prepare("SELECT id FROM users WHERE email = ? AND password = ?");
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }

    public function getUserByEmail($email) {
        $stmt = $this->con->prepare("SELECT id, firstName, lastName, email FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function storeToken($userId, $token) {
        $stmt = $this->con->prepare("UPDATE users SET token = ? WHERE id = ?");
        $stmt->bind_param("si", $token, $userId);
        $stmt->execute();
    }

    public function validateToken($token) {
        $stmt = $this->con->prepare("SELECT id FROM users WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0;
    }
    //Product Management System
    // Create a new product
    public function createProduct($name, $description, $price) {
        $stmt = $this->con->prepare("INSERT INTO products (name, description, price) VALUES (?, ?, ?)");
        $stmt->bind_param("ssd", $name, $description, $price);
        if ($stmt->execute()) {
            return 1;
        } else {
            return 2;
        }
    }

    // Get all products
    public function getProducts() {
        $stmt = $this->con->prepare("SELECT * FROM products");
        $stmt->execute();
        return $stmt->get_result();
    }

    // Get a single product by ID
    public function getProductById($id) {
        $stmt = $this->con->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Update a product
    public function updateProduct($id, $name, $description, $price) {
        $stmt = $this->con->prepare("UPDATE products SET name = ?, description = ?, price = ? WHERE id = ?");
        $stmt->bind_param("ssdi", $name, $description, $price, $id);
        if ($stmt->execute()) {
            return 1;
        } else {
            return 2;
        }
    }

    // Delete a product
    public function deleteProduct($id) {
        $stmt = $this->con->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            return 1;
        } else {
            return 2;
        }
    }
    //Category Management System
    // Create a new category
    public function createCategory($name, $description) {
        $stmt = $this->con->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $description);
        if ($stmt->execute()) {
            return 1;
        } else {
            return 2;
        }
    }

    // Get all categories
    public function getCategories() {
        $stmt = $this->con->prepare("SELECT * FROM categories");
        $stmt->execute();
        return $stmt->get_result();
    }

    // Get a single category by ID
    public function getCategoryById($id) {
        $stmt = $this->con->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Update a category
    public function updateCategory($id, $name, $description) {
        $stmt = $this->con->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
        $stmt->bind_param("ssi", $name, $description, $id);
        if ($stmt->execute()) {
            return 1;
        } else {
            return 2;
        }
    }

    // Delete a category
    public function deleteCategory($id) {
        $stmt = $this->con->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            return 1;
        } else {
            return 2;
        }
    }
    //Order Management System
    // Create a new order
    public function createOrder($user_id, $product_id, $quantity, $total_price) {
        $stmt = $this->con->prepare("INSERT INTO orders (user_id, product_id, quantity, total_price) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiid", $user_id, $product_id, $quantity, $total_price);
        if ($stmt->execute()) {
            return 1;
        } else {
            return 2;
        }
    }

    // Get all orders
    public function getOrders() {
        $stmt = $this->con->prepare("SELECT * FROM orders");
        $stmt->execute();
        return $stmt->get_result();
    }

    // Get a single order by ID
    public function getOrderById($id) {
        $stmt = $this->con->prepare("SELECT * FROM orders WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Update an order
    public function updateOrder($id, $status) {
        $stmt = $this->con->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        if ($stmt->execute()) {
            return 1;
        } else {
            return 2;
        }
    }

    // Delete an order
    public function deleteOrder($id) {
        $stmt = $this->con->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            return 1;
        } else {
            return 2;
        }
    }
    //Cart Management System
     // Add to cart
     public function addToCart($user_id, $product_id, $quantity) {
        $stmt = $this->con->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)");
        $stmt->bind_param("iii", $user_id, $product_id, $quantity);
        if ($stmt->execute()) {
            return 1;
        } else {
            return 2;
        }
    }

    // Update cart
    public function updateCart($user_id, $product_id, $quantity) {
        $stmt = $this->con->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("iii", $quantity, $user_id, $product_id);
        if ($stmt->execute()) {
            return 1;
        } else {
            return 2;
        }
    }

    // Get cart
    public function getCart($user_id) {
        $stmt = $this->con->prepare("SELECT * FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Clear cart
    public function clearCart($user_id) {
        $stmt = $this->con->prepare("DELETE FROM cart WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            return 1;
        } else {
            return 2;
        }
    }
    //Payment Management System
     // Process payment
     public function processPayment($order_id, $user_id, $amount) {
        $stmt = $this->con->prepare("INSERT INTO payments (order_id, user_id, amount) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $order_id, $user_id, $amount);
        if ($stmt->execute()) {
            return 1;
        } else {
            return 2;
        }
    }

    // Get payment status
    public function getPaymentStatus($id) {
        $stmt = $this->con->prepare("SELECT * FROM payments WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    //Review Management System
       // Add review
       public function addReview($product_id, $user_id, $rating, $review) {
        $stmt = $this->con->prepare("INSERT INTO reviews (product_id, user_id, rating, review) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiis", $product_id, $user_id, $rating, $review);
        if ($stmt->execute()) {
            return 1;
        } else {
            return 2;
        }
    }

    // Update review
    public function updateReview($id, $rating, $review) {
        $stmt = $this->con->prepare("UPDATE reviews SET rating = ?, review = ? WHERE id = ?");
        $stmt->bind_param("isi", $rating, $review, $id);
        if ($stmt->execute()) {
            return 1;
        } else {
            return 2;
        }
    }

    // Delete review
    public function deleteReview($id) {
        $stmt = $this->con->prepare("DELETE FROM reviews WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            return 1;
        } else {
            return 2;
        }
    }

    // Get reviews for a product
    public function getReviews($product_id) {
        $stmt = $this->con->prepare("SELECT * FROM reviews WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        return $stmt->get_result();
    }
    //Wishlist Management System
     // Add to wishlist
     public function addToWishlist($user_id, $product_id) {
        $stmt = $this->con->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $product_id);
        if ($stmt->execute()) {
            return 1;
        } else {
            return 2;
        }
    }

    // Remove from wishlist
    public function removeFromWishlist($user_id, $product_id) {
        $stmt = $this->con->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
        $stmt->bind_param("ii", $user_id, $product_id);
        if ($stmt->execute()) {
            return 1;
        } else {
            return 2;
        }
    }

    // Get wishlist
    public function getWishlist($user_id) {
        $stmt = $this->con->prepare("SELECT * FROM wishlist WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        return $stmt->get_result();
    }
}
?>