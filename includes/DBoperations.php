<?php
require_once 'DBconnection.php';

class DBoperations {
    private $con;

    public function __construct() {
        // Assuming you're using mysqli to connect
        $this->con = new mysqli("localhost", "root", "", "thriftique");
        
        if ($this->con->connect_error) {
            die("Connection failed: " . $this->con->connect_error);
        }
    }

    public function connection() {
        return $this->con; // Return the database connection
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
    public function checkUserExists($email) {
        $stmt = $this->con->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        return $stmt->num_rows > 0; // Returns true if user exists
    }
    
    

    public function userLogin($email, $password) {
        $stmt = $this->con->prepare("SELECT password FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
    
        if ($stmt->num_rows > 0) {
            $stmt->bind_result($db_password);
            $stmt->fetch();
    
            if (password_verify($password, $db_password)) { // <-- Checks hashed password
                return true;
            } else {
                error_log("Password verification failed for user: $email");
            }
        } else {
            error_log("User not found: $email");
        }
        return false;
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

    public function createProductWithImage($name, $description, $price, $imagePath) {
        $stmt = $this->con->prepare("INSERT INTO products (name, description, price, image) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssis", $name, $description, $price, $imagePath);
    
        if ($stmt->execute()) {
            return 1; // Success
        } else {
            return 0; // Failure
        }
    }
    
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
    return $stmt->execute() ? "Category created successfully" : "Failed to create category";
}

// Get all categories
public function getCategories() {
    $stmt = $this->con->prepare("SELECT * FROM categories ORDER BY name ASC");
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

// Update a category (with existence check)
public function updateCategory($id, $name, $description) {
    if (!$this->getCategoryById($id)) {
        return "Category not found";
    }

    $stmt = $this->con->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
    $stmt->bind_param("ssi", $name, $description, $id);
    return $stmt->execute() ? "Category updated successfully" : "Failed to update category";
}

// Delete a category (check if products exist before deletion)
public function deleteCategory($id) {
    if (!$this->getCategoryById($id)) {
        return "Category not found";
    }

    // Check if there are products linked to this category
    $stmt = $this->con->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();

    if ($count > 0) {
        return "Cannot delete category: products are associated with it";
    }

    // Proceed with deletion
    $stmt = $this->con->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    return $stmt->execute() ? "Category deleted successfully" : "Failed to delete category";
}
//Order Management System
public function createOrder($user_id, $product_id, $quantity, $total_price) {
    // Get the product price from the database
    $stmt = $this->con->prepare("SELECT price FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $price = $row['price']; // Get the price from products table
        $total_price = $price * $quantity; // Calculate total price
    } else {
        return 2; // Product not found
    }

    // Set default order status to 'Placed'
    $status = "Placed";

    // Insert order with calculated price and default status
    $stmt = $this->con->prepare("INSERT INTO orders (user_id, product_id, quantity, price, total_price, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiidds", $user_id, $product_id, $quantity, $price, $total_price, $status);

    if ($stmt->execute()) {
        return 1;
    } else {
        return 2;
    }
}

public function updateOrderStatus($order_id, $status) {
    // Define allowed statuses
    $allowedStatuses = ['Placed', 'Preparing', 'Ready', 'Completed'];

    // Check if the given status is valid
    if (!in_array($status, $allowedStatuses)) {
        return 3; // Invalid status
    }

    $stmt = $this->con->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $status, $order_id);

    if ($stmt->execute()) {
        return 1; // Success
    } else {
        return 2; // Failure
    }
}

    public function getOrders() {
        $conn = $this->con;
        $sql = "SELECT o.*, 
                       p.name AS product_name, 
                       p.category AS category_name, 
                       u.FirstName, 
                       u.Lastname 
                FROM orders o
                LEFT JOIN users u ON o.user_id = u.id
                LEFT JOIN products p ON o.product_id = p.id
                ORDER BY o.created_at DESC";
    
        return $conn->query($sql);
    }
    
    

    
    // Get a single order by ID
    public function getOrderById($id) {
        // Ensure ID is a valid positive integer
        if (!is_numeric($id) || $id <= 0) {
            return null; // Prevent query execution
        }
    
        $stmt = $this->con->prepare("SELECT orders.*, 
                                            users.FirstName, users.Lastname 
                                     FROM orders 
                                     LEFT JOIN users ON orders.user_id = users.id
                                     WHERE orders.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    
    public function getOrderHistory($phone) {
        $sql = "SELECT orders.*, users.first_name, users.last_name 
                FROM orders 
                JOIN users ON orders.user_id = users.id 
                WHERE users.phone = ?";
        $stmt = $this->con->prepare($sql);
        $stmt->bind_param("s", $phone);
        $stmt->execute();
        return $stmt->get_result();
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
    public function trackOrder($phone, $tracking_number) {
        // For example:
        $stmt = $this->con->prepare("SELECT * FROM orders WHERE phone = ? AND tracking_number = ?");
        $stmt->bind_param("ss", $phone, $tracking_number);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
    
    public function registerUser($firstName, $lastName, $email, $hashedPassword, $token) {
        try {
            $stmt = $this->con->prepare("INSERT INTO users (first_name, last_name, email, password, token) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $firstName, $lastName, $email, $hashedPassword, $token);
            if ($stmt->execute()) {
                return true;
            } else {
                error_log("MySQL Error: " . $stmt->error); // Log database error
                return false;
            }
        } catch (Exception $e) {
            error_log("Exception: " . $e->getMessage());
            return false;
        }
    }
    
    
    
    
    public function registerAdmin($data) {
        $firstName = trim($data['first_name']);
        $lastName = trim($data['last_name']);
        $email = trim($data['email']);
        $password = password_hash(trim($data['password']), PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32)); // Generate a secure token
    
        // Check if email already exists
        $checkQuery = $this->con->prepare("SELECT id FROM admins WHERE email = ?");
        $checkQuery->bind_param("s", $email);
        $checkQuery->execute();
        $checkQuery->store_result();
        if ($checkQuery->num_rows > 0) {
            return ["status" => 0, "message" => "Email already registered"];
        }
    
        // Insert into database with token
        $stmt = $this->con->prepare("INSERT INTO admins (first_name, last_name, email, password, token) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $firstName, $lastName, $email, $password, $token);
    
        if ($stmt->execute()) {
            return ["status" => 1, "message" => "Admin registered successfully", "token" => $token];
        } else {
            return ["status" => 0, "message" => "Database error: " . $stmt->error];
        }
    }
    public function getUserCount() {
        $stmt = $this->con->prepare("SELECT COUNT(*) AS count FROM users"); 
        $stmt->execute();
        $stmt->bind_result($count);
        $stmt->fetch();
        $stmt->close();
        return $count;
    }
    public function executeQuery($sql, $params = []) {
        $stmt = $this->con->prepare($sql);
        if ($params) {
            $stmt->bind_param(str_repeat("s", count($params)), ...$params);
        }
        $stmt->execute();
        return $stmt->get_result();
    }
}    
?>