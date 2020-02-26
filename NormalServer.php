<?php

namespace yxmingy;

@require_once "Socket/socket_h.php";

class NormalServer
{
  protected $sock;
  protected $clients = [];
  //protected $cid;
  protected $on_connect;
  protected $on_message;
  protected $on_disconnect;
  public function __construct(string $addr = '0',int $port = 23)
  {
    $this->sock = new ServerSocket(SocketBase::DOM_IPV4,SocketBase::TYPE_TCP);
    $this->sock->bind($addr,$port)->listen();
  }
  //Not supposed to use
  public function acceptClient():?ClientSocket
  {
    $this->sock->setNonBlock();
    return @$this->sock->accept();
  }
  public function selectClient():?ClientSocket
  {
    return $this->sock->selectNewClient();
  }
  public function selectMessage(array &$clients):?ClientSocket
  {
    return $this->sock->selectNewMessage($clients);
  }
  public function start()
  {
    while (true) {
      if ($c=$this->selectClient()) {
        $this->clients[$c->cid()] = $c;
        if($this->on_connect)
          ($this->on_connect)($c,$this);
      }
      if($c=$this->selectMessage($this->clients)) {
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
    foreach($this->clients as $socket) {
      if(!$socket->closed())
        $socket->write($msg);
    }
  }
  public function kick(ClientSocket $client,bool $call = true)
  {
    if($call)
      ($this->on_disconnect)($client,$this);
    $client->safeClose();
    unset($this->clients[$client->cid()]);
  }
  public function stop()
  {
    foreach ($this->clients as $client){
      $client->safeClose();
    }
    $this->sock->safeClose();
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
}