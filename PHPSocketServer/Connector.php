<?php

namespace GoodWheels\SocketServer;
/**
*Класс по работе с сокетами в качестве серверной стороны
*
*@property resource $socket созданный сокет, как правило привязанный к локальному серверу и указанному клиентом порту, и стоящий на прослушке
*@property int port
*/
class Connector
{
	protected $socket;
	protected $port;
	/**
	*формирует слушающий сокет
	*
	*@param array $params ['port'=>int(1025..65000)]
	*/
	public function __construct( array $params )
	{
		//проверяем входящее значение
		if( !isset($params['port']) ) $this->catchError('При создании экземпляра класса '. __CLASS__ .' не указали порт');
		if( $params['port'] < 1025 ) $this->catchError('При создании экземпляра класса '. __CLASS__ .'указали порт зарезервированный OC');
		if( $params['port'] > 65500 ) 
			$this->catchError('При создании экземпляра класса '. __CLASS__ .'указали порт выходящий за пределы допустимого значения 65000');
		$this->port = $params['port'];
		$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP); //выделяем память для сокета
		if( !@socket_bind( $this->socket , '127.0.0.1', $params['port'] ) ) $this->catchError('', true); //подключаем к порту
		//максимальное кол-во подключений к порту будет обрезано настройками ОС
		if( !@socket_listen( $this->socket ) ) $this->catchError('', true); //ставим на прослушку сокет
	}
	/**
	*высвобождает порт сокета, очищает буфер сокета, удаляет сокет при высвобождении ссылки на объект класса или при завершении скрипта
	*
	*/
	public function __destruct()
	{
		socket_shutdown( $this->socket );
		socket_close( $this->socket );
	}
	/**
	*метод отлова ошибок
	*
	*@param bool $socket_error - если необходимо отлавливать последние ошибки сокета
	*@param string $error_text - выкидывать исключение 
	*/
	protected function catchError( $error_text ='', $socket_error = false )
	{
		if( $socket_error ) 
			throw new \Exception( 
									'Ошибка сокета : '. socket_strerror( socket_last_error ( $this->socket ) )
									. ' код ошибки : '.socket_last_error ( $this->socket )
									);
		else
			throw new \Exception( $error_text );
	}
	public function getSocket()
	{
		return $this->socket;
	}
	public function getPort()
	{
		return $this->port;
	}
	
}