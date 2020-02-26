<?php
namespace yxmingy;
use Exception;
use Volatile;

class ServerSocket extends SocketBase
{
  
  const SELECT_BLOCK = null;
  const SELECT_NONBLOCK = 0;
  
  public function listen(int $backlog = 0):ServerSocket
  {
    if(socket_listen($this->socket,$backlog) === false)
      exit($this->last_error()->getTraceAsString());
    return $this;
  }
  public function _accept()
  {
    return socket_accept($this->socket);
  }
  public function accept():?ClientSocket
  {
    $socket = $this->_accept();
    return ($socket !== false ? $this->getClientInstance($socket) : null);
  }
  public function _select(array &$reads,array &$writes,array &$excepts,int $t_sec,int $t_usec = 0):int
  {
    try {
      return socket_select($reads, $writes, $excepts, $t_sec, $t_usec);
    }catch (Exception $e){
      exit($e->getTraceAsString());
    }
  }
  
  public function select(array &$reads,array &$writes,array &$excepts,int $t_sec,int $t_usec = 0):int
  {
    foreach($reads as $read) {
      if($read->closed) return -1;
    }
    foreach($writes as $write) {
      if($write->closed) return -1;
    }
    foreach($excepts as $except) {
      if($except->closed) return -1;
    }
    $creads = SocketBase::get_resources($reads);
    $cwrites = SocketBase::get_resources($writes);
    $cexcepts = SocketBase::get_resources($excepts);
    $reads = $writes = $excepts = [];
    $code = $this->_select($creads,$cwrites,$cexcepts,$t_sec,$t_usec);
    //var_dump(error_get_last());
    if($code !== false && $code !== null && $code > 0) {
      if(in_array($this->socket,$creads)) {
        $reads[] = $this;
        $key = array_search($this->socket,$creads);
        unset($creads[$key]);
      }
      foreach($creads as $key=>$read) {
          $reads[$key] = $this->getClientInstance($read,$key);
      }
      foreach($cwrites as $key=>$write) {
          $writes[$key] = $this->getClientInstance($write,$key);
      }
      foreach($cexcepts as $key=>$except) {
          $excepts[$key] = $this->getClientInstance($except,$key);
      }
    }
    if($code === false || $code === null) {
       var_dump($code);
       exit();
    }
    return $code ?? -1;
  }
  public function selectNewClient():?ClientSocket
  {
    $reads = [$this,];
    $writes = $excepts = [];
    $code = $this->select($reads,$writes,$excepts,0);
    if($code > 0 && in_array($this,$reads)) {
       return $this->accept();
    }
    return null;
  }
  public function selectNewMessage($clients):?ClientSocket
  {
    if($clients instanceof Volatile) {
      $clients = (array)$clients;
      foreach ($clients as &$client) {
        $client = $this->getClientInstance($client);
      }
    }
    if(empty($clients)) return null;
    $writes = $excepts = [];
    $code = $this->select($clients,$writes,$excepts,0);
    if($code > 0 && count($clients) > 0) {
      return array_shift($clients);
    }
    return null;
  }
}