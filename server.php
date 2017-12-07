<?php

proc_nice(5);
//избавляет от буферизации NGINX
header('x-accel-buffering: no');

function __autoload($class_name) {   
	require_once(str_replace( '\\', '/', $class_name ). '.php'); 
}

include_once( __DIR__ .'/Classes/Commander.php');
/**
*Собственно сокет -сервер - который постоянно включен
*держит в памяти все подключения
*отлавливает информацию о входящих вызовах, о поднятых трубках, о завершающих разговорах
*отправляет информацию в буфер сокетов активных менеджеров, которые в текущий момент не разговаривают
*постоянно хранит в памяти информацию о разговаривающих в данный момент менеджерах - это период с момента поднятия трубки и завершения разговора
*
*
*
*/
$port = 1100;
try{
	$server = new \GoodWheels\SocketServer\Connector( ['port'=> $port ] );
	$reader = new \GoodWheels\SocketServer\Reader( $server );
	$commander = new Commander();
	$reader->setCommander( $commander );
	$reader->goRead( [ 'max_clients'=> 50 ] );
}
catch( \Ecxeption $e){
	echo $e->getMessage();
}
finally{
	unset( $server );
}