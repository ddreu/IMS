<?php
require 'vendor/autoload.php';
use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use React\EventLoop\Factory;
use React\Socket\SecureServer;
use React\Socket\Server;

class StreamServer implements \Ratchet\MessageComponentInterface {
    protected $clients;
    protected $scheduleStreams;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->scheduleStreams = [];
        echo "Stream server initialized\n";
    }

    public function onOpen(\Ratchet\ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
        
        // Send welcome message
        $conn->send(json_encode([
            'type' => 'welcome',
            'message' => "Connected successfully"
        ]));
    }

    public function onMessage(\Ratchet\ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (!isset($data['type']) || !isset($data['scheduleId'])) {
            echo "Received message without type or scheduleId\n";
            return;
        }

        $scheduleId = $data['scheduleId'];

        echo "Received message type: {$data['type']} for schedule: {$scheduleId}\n";

        switch ($data['type']) {
            case 'init':
                if (!isset($data['role'])) return;
                
                $from->scheduleId = $scheduleId;
                $from->role = $data['role'];
                
                if (!isset($this->scheduleStreams[$scheduleId])) {
                    $this->scheduleStreams[$scheduleId] = [
                        'broadcaster' => null,
                        'viewers' => new \SplObjectStorage
                    ];
                }
                
                if ($data['role'] === 'broadcaster') {
                    if ($this->scheduleStreams[$scheduleId]['broadcaster']) {
                        echo "Closing existing broadcaster connection\n";
                        $this->scheduleStreams[$scheduleId]['broadcaster']->close();
                    }
                    $this->scheduleStreams[$scheduleId]['broadcaster'] = $from;
                    echo "Registered broadcaster for schedule {$scheduleId}\n";
                } else {
                    $this->scheduleStreams[$scheduleId]['viewers']->attach($from);
                    echo "Added viewer to schedule {$scheduleId}\n";
                }
                
                echo "Processing init message: " . json_encode($data) . "\n";
                // Send confirmation back to broadcaster
                $from->send(json_encode([
                    'type' => 'init_response',
                    'status' => 'success'
                ]));
                
                echo "Sent init response to: {$from->resourceId}\n";
                echo "Init message processed successfully\n";
                break;

            case 'video':
                if (!isset($from->role) || $from->role !== 'broadcaster') return;
                
                echo "Forwarding video data to viewers for schedule: {$scheduleId}\n";
                if (isset($this->scheduleStreams[$scheduleId])) {
                    foreach ($this->scheduleStreams[$scheduleId]['viewers'] as $client) {
                        $client->send($msg);
                    }
                }
                break;
        }
    }

    public function onClose(\Ratchet\ConnectionInterface $conn) {
        // Remove from clients list
        $this->clients->detach($conn);
        
        // Clean up schedule streams
        if (isset($conn->scheduleId)) {
            $scheduleId = $conn->scheduleId;
            echo "Connection {$conn->resourceId} closing for schedule {$scheduleId}\n";
            
            if (isset($this->scheduleStreams[$scheduleId])) {
                if ($conn->role === 'broadcaster') {
                    $this->scheduleStreams[$scheduleId]['broadcaster'] = null;
                    echo "Broadcaster disconnected from schedule {$scheduleId}\n";
                } else if ($conn->role === 'viewer') {
                    $this->scheduleStreams[$scheduleId]['viewers']->detach($conn);
                    echo "Viewer disconnected from schedule {$scheduleId}, remaining viewers: {$this->scheduleStreams[$scheduleId]['viewers']->count()}\n";
                }
                
                // Remove schedule stream if no broadcaster and no viewers
                if ($this->scheduleStreams[$scheduleId]['broadcaster'] === null && 
                    $this->scheduleStreams[$scheduleId]['viewers']->count() === 0) {
                    unset($this->scheduleStreams[$scheduleId]);
                    echo "Removed empty stream for schedule {$scheduleId}\n";
                }
            }
        }
        
        echo "Connection {$conn->resourceId} has disconnected\n";
    }

    public function onError(\Ratchet\ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Create event loop and socket server
$loop = Factory::create();
$socket = new Server('0.0.0.0:8090', $loop);

// Create WebSocket server
$server = new IoServer(
    new HttpServer(
        new WsServer(
            new StreamServer()
        )
    ),
    $socket,
    $loop
);

echo "WebSocket server started on port 8090\n";
$loop->run();
