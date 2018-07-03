<?php
$redis = new Redis;
$redis->connect('127.0.0.1', 6379);
$redis->auth('aewrxkyjMiq4sozM4ubf');

$client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
$client->set(array(
	'open_eof_check' => true,
	'package_eof' => '}',	
	'package_max_length' => 1024 * 1024 * 8,
));
$client->on("connect", function($cli) {
	$cli->send("login");
});
$client->on("receive", function($cli, $data) use ($redis){
	$data = $data;
	if(empty($data)){		
		$cli->close();
	}else{
		$data = json_decode($data,true);
		if($data['info'] == 'success'){
			sleep(1);
			$cli->send("market");			
		}else{
			if($data['msg']=="market"){
				$data = json_encode(['data'=>$data['info']]);			
				$redis->set('hq_sc',$data);						
				update_hq($data);
				echo '保存成功！';			
			}	
		}
	}
});
$client->on("error", function($cli){
	echo "Connect failed\n";
	$cli->connect("0.0.0.0",1212,2);
});
$client->on("close", function($cli){
	echo "Connection close\n";
	$cli->connect("0.0.0.0",1212,2);
});
$client->connect("0.0.0.0",1212,2);
function update_hq($data){ // 更新对应合约
	$http = new swoole_http_client('127.0.0.1', 1215); 
	$http->post('/update_hq', ['data'=>$data], function ($http) {
		if($http->statusCode == 200){
			$http->close();
		}
	});
}
