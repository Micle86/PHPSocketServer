<?php

class Commander extends \GoodWheels\SocketServer\CommanderAbstract
{
	/**
	*в зависимости от входящей информации - отдает команды всем подключившимся
	*
	*@param array $params ['information'=>string, 'client'=>resource]
	*/
	public function doCommand( $params )
	{
		//разорвать соединение
		//$this->levers->disсonnect( $params['client'] );
		
		//входящее сообщение  
		//echo $params['information'];
		
		//отправка сообщения
		//$message = 'сообщение';
		//$this->levers->sendMessage( [ 'client'=> $params['client'], 'message'=> $message ] )
	}
}