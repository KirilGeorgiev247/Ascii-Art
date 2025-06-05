<?php

// use OpenSwoole\WebSocket\Server;
// use OpenSwoole\Http\Request;
// use OpenSwoole\WebSocket\Frame;

// require_once __DIR__ . '/../../vendor/autoload.php';

// $server = new Server("0.0.0.0", 9501);

// $drawingState = [];

// $server->on("start", function (Server $server) {
//     echo "WebSocket server started at ws://localhost:9501\n";
// });

// $server->on("open", function (Server $server, Request $request) use (&$drawingState) {
//     echo "Connection opened: {$request->fd}\n";

//     // Изпрати текущото състояние на рисунката на новия потребител
//     foreach ($drawingState as $drawEvent) {
//         $server->push($request->fd, json_encode($drawEvent));
//     }
// });

// $server->on("message", function (Server $server, Frame $frame) use (&$drawingState) {
//     echo "Received message from {$frame->fd}: {$frame->data}\n";

//     $data = json_decode($frame->data, true);

//     if ($data['type'] === 'draw') {
//         $drawingState[] = $data;
//     } elseif ($data['type'] === 'clear') {
//         $drawingState = [];
//     }

//     foreach ($server->connections as $fd) {
//         if ($server->isEstablished($fd)) {
//             $server->push($fd, $frame->data);
//         }
//     }
// });

// $server->on("close", function (Server $server, int $fd) {
//     echo "Connection closed: {$fd}\n";
// });

// $server->start();