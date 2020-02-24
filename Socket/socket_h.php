<?php
namespace yxmingy;
set_time_limit(0);
ob_implicit_flush();
require_once __DIR__."/SocketBase.php";
require_once __DIR__."/ClientSocket.php";
require_once __DIR__."/ServerSocket.php";
  /* param SocketBase[] */
  function get_resources(array $sockets):array
  {
    $res = [];
    foreach($sockets as $key=>$socket)
    {
      $res[$key] = $socket->getSocketResource();
    }
    return $res;
  }
  function batch_write(array $sockets,string $msg)
  {
    foreach($sockets as $socket) {
      if(!$socket->closed())
        $socket->write($msg);
    }
  }