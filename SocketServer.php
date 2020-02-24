<?php

namespace yxmingy;
require_once "Socket/socket_h.php";

class SocketServer
{
  protected $sock;
  protected $clients = [];
  //protected $cid;
  protected $on_connect;
  protected $on_message;
  protected $on_disconnect;
  public function __construct(string $addr = '0',int $port)
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
        \call_user_func($this->on_connect, $c);
      }
      if($c=$this->selectMessage($this->clients)) {
        //Check if client disconnected
        if(($msg=$c->read())===null) {
          //Preclose let it be ignored when broadcast, but not delete res.
          $c->preClose();
          \call_user_func($this->on_disconnect, $c);
          $this->kick($c);
          continue;
        }
        \call_user_func($this->on_message, $c, $msg);
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
    batch_write($this->clients,$msg);
  }
  public function kick(ClientSocket $client)
  {
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