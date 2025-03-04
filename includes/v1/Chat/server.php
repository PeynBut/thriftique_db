<?php
require 'vendor/autoload.php';

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

// Chat Server Class
class ChatServer implements MessageComponentInterface {
    protected $clients;
    protected $pdo;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->pdo = $this->connectDatabase();
    }

    private function connectDatabase() {
        $host = 'localhost';
        $dbname = 'thriftique';
        $username = 'root';
        $password = '';

        try {
            return new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]);
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }

    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection ({$conn->resourceId})\n";
    
        // Example: Fetch chat history (adjust based on your logic)
        $history = $this->getChatHistory('User1', 'User2'); 
        foreach ($history as $msg) {
            $conn->send(json_encode($msg));
        }
    }
    
    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
    
        // Validate that all required fields exist
        if (!isset($data['sender']) || !isset($data['receiver']) || !isset($data['message'])) {
            echo "Invalid message format received. Required keys missing.\n";
            return;
        }
    
        $sender = trim($data['sender']);
        $receiver = trim($data['receiver']);
        $message = trim($data['message']);
    
        // Ensure message is not empty before saving
        if (empty($message)) {
            echo "Empty message received. Not saving to database.\n";
            return;
        }
    
        // Save message to database
        $this->saveMessage($sender, $receiver, $message);
    
        // Send message to all clients
        foreach ($this->clients as $client) {
            $client->send(json_encode([
                'sender' => $sender,
                'receiver' => $receiver,
                'message' => $message
            ]));
        }
    }
    
    

    public function saveMessage($sender, $receiver, $message) {
        if (empty($sender) || empty($receiver) || empty($message)) {
            echo "Skipping message save: Missing sender, receiver, or message.\n";
            return;
        }
    
        try {
            $stmt = $this->pdo->prepare("INSERT INTO messages (sender, receiver, message) VALUES (?, ?, ?)");
            $stmt->execute([$sender, $receiver, $message]);
            echo "Message saved: $message\n";
        } catch (PDOException $e) {
            echo "Database error: " . $e->getMessage() . "\n";
        }
    }
    
    
    
    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} closed\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }
    public function getChatHistory($sender, $receiver) {
        $stmt = $this->pdo->prepare("SELECT sender, message FROM messages 
                                    WHERE (sender = ? AND receiver = ?) OR (sender = ? AND receiver = ?) 
                                    ORDER BY timestamp ASC");
        $stmt->execute([$sender, $receiver, $receiver, $sender]);
        return $stmt->fetchAll();
    }
    
}

// Run WebSocket Server
if (php_sapi_name() === 'cli') {
    $server = \Ratchet\Server\IoServer::factory(
        new \Ratchet\Http\HttpServer(
            new \Ratchet\WebSocket\WsServer(
                new ChatServer()
            )
        ),
        8080
    );

    echo "WebSocket Server started at ws://localhost:8080\n";
    $server->run();
    exit;
}

// Frontend HTML with WebSocket Chat
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Thriftique</title>
    <link rel="stylesheet" href="chat.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="menu-toggle" onclick="toggleMenu()">☰</div>
        <h2 style="margin-left: 20px;">Admin Dashboard</h2>
    </div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
    <a href="http://localhost/thriftique_db/includes/v1//admin/dashboard.html">🏠 Dashboard</a>
    <a href="http://localhost/thriftique_db/includes/v1/Products/products.php">📦 Products</a>
    <a href="http://localhost/thriftique_db/includes/v1/Orders/Order.html">📦 Orders</a>
    <a href="http://localhost/thriftique_db/includes/v1/Categories/Categories.php">📂 Categories</a>
    <a href="#" onclick="openChat()">💬 Messages</a>
</div>
    <!-- Content -->
    <div class="content" id="content">
        <h2>Welcome to your Dashboard</h2>
        <p>Select an option from the sidebar to get started.</p>

        <!-- Dashboard Overview -->
        <div class="dashboard-overview">
            <div class="card">
                <h3>Total Products</h3>
                <p>120</p>
            </div>
            <div class="card">
                <h3>Total Categories</h3>
                <p>15</p>
            </div>
            <div class="card">
                <h3>Monthly Sales</h3>
                <p>$5,230</p>
            </div>
            <div class="card">
                <h3>Active Users</h3>
                <p>320</p>
            </div>
        </div>

        <!-- Chat Section (Hidden Initially) -->
        <div id="chat-section" class="chat-box">
            <div class="chat-header">
                <h3>Live Chat</h3>
                <button onclick="closeChat()">✖</button>
            </div>
            <div id="chat-messages" class="chat-messages"></div>
            <div class="chat-input">
                <input type="text" id="chatMessage" placeholder="Type a message..." />
                <button onclick="sendMessage()">Send</button>
            </div>
        </div>
        
    </div>

    <script>
        function toggleMenu() {
            document.getElementById('sidebar').classList.toggle('active');
            document.getElementById('content').classList.toggle('shift');
        }

        function openChat() {
            document.getElementById('chat-section').style.display = "block";
        }

        function closeChat() {
            document.getElementById('chat-section').style.display = "none";
        }

        // WebSocket Connection
        let ws;

function connectWebSocket() {
    ws = new WebSocket("ws://localhost:8080");

    ws.onopen = function () {
        console.log("Connected to WebSocket");
    };

    ws.onmessage = function (event) {
        console.log("WebSocket message received:", event.data);

        const chatMessages = document.getElementById("chat-messages");
        if (!chatMessages) {
            console.error("Chat messages container not found!");
            return;
        }

        const data = JSON.parse(event.data);
        if (!data.message) {
            console.error("Invalid message format received:", data);
            return;
        }

        const message = document.createElement("div");
        message.classList.add("message");
        message.textContent = `${data.sender}: ${data.message}`;

        chatMessages.appendChild(message);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    };

    ws.onclose = function () {
        console.warn("WebSocket closed. Reconnecting in 3 seconds...");
        setTimeout(connectWebSocket, 3000); // Reconnect after 3s
    };

    ws.onerror = function (error) {
        console.error("WebSocket error:", error);
        ws.close();
    };
}

// Start WebSocket connection
connectWebSocket();

function sendMessage() {
    const input = document.getElementById("chatMessage");
    const message = input.value.trim();

    if (!message) {
        console.warn("Cannot send an empty message.");
        return;
    }

    if (ws.readyState === WebSocket.OPEN) {
        ws.send(JSON.stringify({ sender: "Admin", receiver: "User", message: message }));
        input.value = "";
    } else {
        console.error("WebSocket is not open. Cannot send message.");
    }
}



    </script>
</body>
</html>
