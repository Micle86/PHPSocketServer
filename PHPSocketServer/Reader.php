<?php

namespace GoodWheels\SocketServer;
/**
*класс по чтению сообщений из всех подключений к сокету и передача этих сообщений в управляющий класс  
*
*@property \SocketServerConnector $socket
*@property bool $is_doing true - читать все входящие соединения | false - остановить чтение всех входящих соединений
*@property array $client_sockets - массив всех подключений, которые сейчас открыты
*@property array $client_web_sockets - массив всех только web_sockets подключений, которые сейчас открыты, также они входя в массив $client_sockets
*@property \SocketServerCommanderI $commander - класс по упарвлению и распределению входящими на сокет сервер информационными потоками
*/
class Reader
{
	protected $socket;
	protected $is_doing = true; 
	protected $client_sockets = []; 
	protected $client_web_sockets = []; 
	protected $commander; 
	public function __construct( \GoodWheels\SocketServer\Connector $obj )
	{
		$this->socket = $obj;
	}
	/**
	*Отрубание сервера
	*
	*/
	public function __destruct()
	{
		//цикл по всем соединениям с целью предупредить, что сервер выключается и мягкое отключение всех подключений
		foreach($this->client_sockets as $key => $client){
			$this->sendMessage( [ 'client'=> $client, 'message'=> 'Сервер выключается' ] );
			$this->disсonnect( $client );
		}
		unset($this->socket);
		$this->setLog( 'Сервер выключен' );
	}
	/**
	*устанавливает руководителя информационным потоком
	*
	*@param \SocketServerCommanderI $obj
	*/
	public function setCommander( \GoodWheels\interfaces\SocketServerCommanderI $obj )
	{
		$this->commander = $obj;
		$this->commander->setLevers( $this );
	}
	/**
	*метод чтения всех входящих соединений постоянно работающий цикл
	*
	*@param array $params [ 'max_clients'=> '' ] максимальное количество соединений
	*/
	public function goRead( array $params )
	{
		if( !isset( $this->commander ) ) 
			$this->catchError('После объявлении экземпляра класса '. __CLASS__ . ' и до вызова метода '. __METHOD__ .' необходимо установить SocketServerCommander');
		if( !$params['max_clients'] ) $this->catchError('При запуске чтения входящих соединений не был передан параметр максимального кол-ва соединений с сервером');
		$this->setLog('Сервер запущен и прослушивает соединение на порту : '.$this->socket->getPort() );
		
		//предустанвоки
		$NULL = NULL;
		$read = [ $this->socket->getSocket() ]; //массив отслеживаемых сокетов - здесь сидит главный сокет и сокеты подключений
		
		//бесконечный цикл, который прервывается, если подключились к порту и отправили на него сообщение об остановке прослушки входящих соединений
		while( $this->is_doing )
		{
			usleep(100000);//для экономии ресурсов процессора (отдых в течении 0.1 с после действия)
			//Произошли ли изменения в отслеживаемых сокетах ? (200 000 - = 0.2 секунды время межде прослушками всех соединений)
			if( socket_select($read, $NULL, $NULL, 0, 200000) )
			{ 
				//Есть ли новые подключения у главного сокета, если есть и количество новых подключений не превышает лимит - добавляем к очереди прослушки 
				if(  (in_array( $this->socket->getSocket() , $read)) && ( sizeof($this->client_sockets) < $params['max_clients'] ) )
				{
					$connector = socket_accept( $this->socket->getSocket() );
					$this->client_sockets[] = $connector;
					$this->catchWebSocket( $connector );
				}
				//считываем входящие сообщения
				$this->readClientsConnection( $read );
			}
			$read = $this->client_sockets;
			$read[] = $this->socket->getSocket();
		}
	}
	/**
	*считывает данные из сокета
	*
	*@param resource $client
	*/
	protected function socketRead( $client )
	{
		if( false === ( $data = @socket_read($client, 1024) ) ){
			$this->disсonnect( $client );
			return false;
		}
		//если это вебсокет, то после прочтения информации, её надо перекодировать
		if( ( $key = array_search( $client, $this->client_web_sockets ) ) !== false ){
			$data = \GoodWheels\HTTP\HttpTools::decodeWebSocketMessage( [ 'information'=> $data, 'message_only'=> true ] );
			print_r( $data );
		}
		if( $data ){
			//$this->setLog( $data );
			$this->commander->doCommand( ['information'=>$data, 'client'=>$client] );
			return $data;
		}
	}
	/**
	*останавливает сокет сервер
	*
	*/
	public function stopRead()
	{
		$this->is_doing = false;
	}
	/**
	*отправка сообщения клиенту
	*
	*@param array $params [ 'client'=> resource, 'message'=> string ]
	*/
	public function sendMessage( $params )
	{
		if( !isset($params['client']) || !isset($params['message']) ) $this->catchError('При попытке отправки сообщения не указали тело сообщения, либо адресата');
		if(!$params['message']) $this->catchError('При попытке отправки сообщения не указали тело сообщения');
		//если это вебсокет, то шифруем данные
		if( ( $key = array_search( $params['client'], $this->client_web_sockets ) ) !== false ){
			$params[ 'message'] = \GoodWheels\HTTP\HttpTools::encodeWebSocketMessage( [ 'information'=> $params[ 'message'] ] );
		}
		//отправляем сообщение
		if( @socket_write( $params[ 'client'], $params[ 'message'] ) === false ) {
			$this->disсonnect( $params[ 'client'] );
			return false;
		}
		return true;
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
	/**
	*вывод сообщений, если при запуске сокет сервера сделать вывод в файл например php server.php > log.txt то имнно в файл будут писаться все логи
	*
	*@param string $log_text
	*/
	protected function setLog($log_text)
	{
		echo date( 'd.m.Y H:i:s', time() ).' - '.$log_text. PHP_EOL;
	}
	/**
	*считывает данные со всех входящих соединений
	*
	*/
	protected function readClientsConnection( array $read )
	{
		//Цикл по всем подключениям с проверкой изменений в каждом из них
		foreach($this->client_sockets as $key => $client){ 
			// Новые данные в клиентском сокете? Прочитать и отправить информацию командиру
			if(in_array($client, $read)) $this->socketRead( $client );
		}
	}
	/**
	*отлавливает web-socket соединение, если к серверу стучится web-socket соединение - отправляется рукопожатие, клиент заносится в массив $this->client_web_sockets
	*
	*
	*@param resource $client 
	*/
	protected function catchWebSocket( $client )
	{
		if( !isset($client) ) 
			$this->catchError('При определинии входящего соединения как web-socket - не был передан сокет соединения для исследования поступившей информации');
		//если не удалось прочесть, нет необходимости дальше с открытым для соединения сокетом что то делать
		if( ($data = $this->socketRead( $client ) ) === false ) return false;
		//если имеем дело с вебсокетами, то делаем рукопожатие
		if( stripos( $data, 'Sec-WebSocket-Key:' ) !== false ){
			//получаем Http заголовки
			$headers = \GoodWheels\HTTP\HttpTools::getHttpHeaders( $data. "\n\r\n\r" /*PHP_EOL . PHP_EOL*/ );
			//формируем ключ ответа
			$responce_key = \GoodWheels\HTTP\HttpTools::getWebSocketAccept( $headers['Sec-WebSocket-Key'] );
			$command = 'HTTP/1.1 101 Switching Protocols' . PHP_EOL
						.'Upgrade: websocket' . PHP_EOL
						.'Connection: Upgrade' . PHP_EOL
						.'Sec-WebSocket-Accept: '.$responce_key . PHP_EOL
						.'Sec-WebSocket-Version: 13' . PHP_EOL . PHP_EOL;
			//если удалось отправить без ошибок сообщение, то добавляем в массив вебсокетов это соединение
			if( $this->sendMessage( [ 'client'=> $client, 'message'=> $command ] ) ) $this->client_web_sockets[] = $client;
		} 
		
	}
	/**
	*мягко разорвать соединение с конкретным клиентом
	*
	*@param resource $client
	*/
	public function disсonnect( $client )
	{
		
		if( ( $key = array_search( $client, $this->client_sockets ) ) !== false ) unset($this->client_sockets[$key]);
		if( ( $key = array_search( $client, $this->client_web_sockets ) ) !== false ){
			unset($this->client_web_sockets[$key]);
		} 
		@socket_shutdown( $client );
		@socket_close( $client );
	}
}

