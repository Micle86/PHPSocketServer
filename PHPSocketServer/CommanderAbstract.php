<?php

namespace GoodWheels\SocketServer;
/**
*класс по управлению потоками информации, приходящими с сокет сервера
*получает входящую информацию и может отпарвлять комманды отправки сообщений каким захочет получателям
*
*@property \SocketServerReader $levers
*/
abstract class CommanderAbstract  implements \GoodWheels\interfaces\SocketServerCommanderI
{
	protected $levers;
	/**
	*получает в распоряжение посыльного, с помошью него также может отсылать распоряжения
	*
	*@param \SocketServerReader $obj
	*/
	public function setLevers( \GoodWheels\SocketServer\Reader $obj)
	{
		$this->levers = $obj;
	}
	/**
	*на основе поступающей извне информации выполняет комманды
	*
	*@param array $params ['information'=>string, 'client'=>resource]
	*/
	abstract public function doCommand( $params );
}