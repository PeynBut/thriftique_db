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
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        if ($data) {
            $this->saveMessage($data['sender'], $data['receiver'], $data['message']);

            foreach ($this->clients as $client) {
                $client->send($msg);
            }
        }
    }

    public function saveMessage($sender, $receiver, $message) {
        $stmt = $this->pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$sender, $receiver, $message]);
        echo "Message saved: $message\n";
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        echo "Connection {$conn->resourceId} closed\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
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
    <a href="http://localhost/thriftique_db/includes/v1/admin/settings.html">⚙️ Settings</a>
    <a href="http://localhost/thriftique_db/includes/v1/admin/logout.php" class="logout" onclick="logoutUser()">🚪 Logout</a>
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
        const ws = new WebSocket("ws://localhost:8080");

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

function sendMessage() {
    const input = document.getElementById("chatMessage");
    const message = input.value.trim();
    if (message) {
        ws.send(JSON.stringify({ sender: "Admin", receiver: "User", message: message }));
        input.value = "";
    }
}

    </script>
</body>
</html>
