<?php

namespace yxmingy\socket\server;
use Thread;

class AsyncServer extends
  Thread {
  private $res;
  private static $ss;
  protected $clients;
  public $on_connect;
  public $on_message;
  public $on_disconnect;
  private $closed = false;
  public function __construct(string $addr = '0',int $port = 23)
  {
    $sock = new ServerSocket(SocketBase::DOM_IPV4,SocketBase::TYPE_TCP);
    $sock->rebind($addr,$port)->listen();
    $this->res = $sock->getSocketResource();
    $this->clients = [];
  }
  public function getServer()
  {
    if(null == self::$ss) {
      return (self::$ss = new ServerSocket(SocketBase::DOM_IPV4,SocketBase::TYPE_TCP,$this->res));
    }else{
      return self::$ss;
    }
  }
  public function run()
  {
    while (!$this->closed) {
      if ($c=$this->getServer()->selectNewClient()) {
        $this->clients[$c->cid()] = $c->getSocketResource();
        if($this->on_connect)
          ($this->on_connect)($c,$this);
      }
      if($c=$this->getServer()->selectNewMessage($this->clients)) {
        //Check if client disconnected
        if(($msg=$c->read())===null) {
          //Preclose let it be ignored when broadcast, but not delete res.
          $c->preClose();
          if($this->on_disconnect)
            ($this->on_disconnect)($c,$this);
          $this->kick($c,false);
          continue;
        }
        if($this->on_message)
          ($this->on_message)($c, $msg,$this);
      }
    }
  }
  public function send(string $cid,string $msg):bool
  {
    if(isset($this->clients[$cid])) {
      return $this->clients[$cid]->write($msg) ? true : false;
    }
    return false;
  }
  public function broadcast(string $msg)
  {
    foreach ($this->clients as $client)
    {
      $this->getServer()->getClientInstance($client)->write($msg);
    }
  }
  public function kick(ClientSocket $client,bool $call = true)
  {
    if($call && $this->on_disconnect)
      ($this->on_disconnect)($client,$this);
    $client->safeClose();
    unset($this->clients[$client->cid()]);
  }
  //Stopping a Async Server seems to cause to stop all threads
  //I cannot solve the error 'zend_mm_heap corrupted'
  public function stop()
  {
    $this->closed = true;
    foreach ($this->clients as $client){
      $c = $this->getServer()->getClientInstance($client);
      $c->write("Server stopped");
      $c->safeClose();
    }
    $this->getServer()->safeClose();
  }
  public function onConnect(Callable $cable)
  {
    $this->on_connect = $cable;
  }
  public function onMessage(Callable $cable)
  {
    $this->on_message = $cable;
  }
  public function onDisconnect(Callable $cable)
  {
    $this->on_disconnect = $cable;
  }
};