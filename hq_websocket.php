<?php
$server = new swoole_websocket_server("0.0.0.0",9504);

$server->on('open', function (swoole_websocket_server $server, $request) {
	global $user;
	global $timer;
	$user[$request->fd] = $request->fd;
	echo "server: handshake success with fd{$request->fd}\n";
});

$server->on('message', function (swoole_websocket_server $server, $frame) {
	
	$redis = new Redis;
	$redis->connect('127.0.0.1', 6379);
	$redis->auth('');
		global $timer;
		$timer = swoole_timer_tick(1000, function () use ($server,$redis) {				
			global $user;
			$hq = $redis->get('hq_sc');
			foreach($user as $fd){
				$server->push($fd, $hq);
			}  
		});
});

$server->on('close', function ($ser, $fd) {
	global $user;
	unset($user[$fd]);
	global $timer;
	swoole_timer_clear($timer);
    echo "client {$fd} closed\n";
});
$server->start();
