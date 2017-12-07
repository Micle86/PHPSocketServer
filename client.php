<?php

/**
1 создаем сокет
2 подключаемся к сокету на сервере по локальному хосту
3 закрываем сокет

*/
//избавляет от буферизации NGINX
header('x-accel-buffering: no');

// поозволяет скрипту ожидать соединени¤ бесконечно. 
set_time_limit(0);

//избавляемся от буферизации php
ob_implicit_flush(1);

try{
	//создаем сокет
	$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	if( !$socket ) throw new \Exception( 'создание сокета привело к'. socket_strerror( socket_last_error() ) );
	
	//подключаемся к другому (серверному) сокету
	if( !socket_connect($socket, '127.0.0.1', 1100 ) )  throw new \Exception( 'подключение к сокету привело к'. socket_strerror( socket_last_error() ) );
	
	echo 'Соединение с сервером установлено, для отключения сервера введите exit'. PHP_EOL 
		.'для выхода "q"'. PHP_EOL 
		.'введите команду для управления сервером'. PHP_EOL;
	$go = true;
	while($go){
		$command = fgets(STDIN, 255);
		echo 'вы ввели команду : '.$command. PHP_EOL . 'Введите следующую команду:';
		//$command = 'exit';
		//$command = 'exit1';
		if( $command == 'q' ) $go = false;
		if( socket_write($socket, $command) === false ) 
				throw new \Exception( 'запись в сокет привела к '. socket_strerror( socket_last_error() ) );
		
	}
	@socket_close($socket);
}
catch(\Exception $e){
	echo 'Ошибка: '.$e->getMessage();
}
finally{
	//закрываем сокет
	@socket_close($socket);
}	


































/*
try{
	//создаем сокет
	$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
	if( !$socket ) throw new \Exception( 'создание сокета привело к'. socket_strerror( socket_last_error() ) );
	
	//подключаемся к другому (серверному) сокету
	if( !socket_connect($socket, '127.0.0.1', 1100 ) )  throw new \Exception( 'подключение к сокету привело к'. socket_strerror( socket_last_error() ) );
	
	echo 'Соединение с сервером установлено, ожидаю ответ:'. PHP_EOL;
	$result = '';
	//while( ($read = socket_read($socket, 1024) ) )
	//{
	//	echo $read. PHP_EOL;
	//	$result .= $read; 
	//}
	echo $result. PHP_EOL;
	//echo socket_read($socket, 1024). PHP_EOL;
	//$command = fgets(STDIN, 255);
	$command = 'выход';
	echo 'вы ввели: '.$command. PHP_EOL;
	if( socket_write($socket, $command) === false ) 
			throw new \Exception( 'запись в сокет привела к'. socket_strerror( socket_last_error() ) );
	$result = '';
	//считываем приветствие сервера
	//while($read = socket_read($socket, 1024))
	//{
	//	$result .= $read; 
	//}
	//echo $result . PHP_EOL;
	//while(true){
	//	//считывае данные с клавиатуры
	//	echo 'команда: ';
	//	$command = fgets(STDIN, 255);
	//	//отправляем сообщение на сервере
	//	if( socket_write($socket, $command) === false ) 
	//		throw new \Exception( 'запись в сокет привела к'. socket_strerror( socket_last_error() ) );
	//	
	//	//считываем ответ сервера 
	//	$result = '';
	//	//while($read = socket_read($socket, 1024))
	//	//{
	//	//	$result .= $read; 
	//	//}
	//	echo $result . PHP_EOL;
	//	break;
	//}
	
}
catch(\Exception $e){
	echo 'Ошибка: '.$e->getMessage();
}
finally{
	//закрываем сокет
	socket_close($socket);
}
*/