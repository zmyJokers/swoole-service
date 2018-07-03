<?php
$client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
$client->set(array(
	'open_eof_split'=>true,
	'open_eof_check' => true,
	'package_eof' => '"}',	
	'package_max_length' => 1024 * 1024 * 8,
));
$client->on("connect", function($cli) {
	$cli->send("login");
});
$client->on("receive", function($cli, $data){
	print_r($data);
	$message = $data;
	if(empty($data)){
		$cli->close();
	}else{
		$data=json_decode($data,true);
		if($data['info']=="success"){
			$cli->send("trade");
		}else{
			if($data['msg'] == 'orderDealEvent'){
				update_cc($message);
			}	
		}
	}
});
$client->on("error", function($cli){
	echo "Connect failed\n";
	$cli->connect("0.0.0.0",19995,2);
});
$client->on("close", function($cli){
	echo "Connection close\n";
	$cli->connect("0.0.0.0",19995,2);
});
$client->connect("0.0.0.0",19995,2);

function update_cc($data){ // 更新对应合约
	$http = new swoole_http_client('127.0.0.1', 1215);
	$http->post('/update_moni_kp', ['data'=>$data], function ($http) {
		$http->close();
	});
}
