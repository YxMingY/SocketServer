<?php
declare(strict_types=1);
namespace yxmingy;
use Exception;

/**
 * @method listen()
 */
abstract class SocketBase
{
  const DOM_IPV4 = AF_INET;
  const DOM_IPV6 = AF_INET6;
  const DOM_LOCAL = AF_UNIX;
  const TYPE_TCP = SOCK_STREAM;
  const TYPE_UDP = SOCK_DGRAM;//DATAGRAM
  const TYPE_ICMP = SOCK_RAW;
  
  protected $socket;
  protected $domin_type;
  protected $type;
  protected $protocol;
  protected $closed = false;
  
  public function __construct(int $domin = AF_INET,int $type = SOCK_STREAM, $socket = null)
  {
    $this->domin_type = $domin;
    $this->type = $type;
    switch($type) {
      case self::TYPE_TCP:
        $this->protocol = SOL_TCP;
        break;
      case self::TYPE_UDP:
        $this->protocol = SOL_UDP;
        break;
      case self::TYPE_ICMP:
        $this->protocol = getprotobyname("icmp");
        break;
      default:
        exit("[Socket] Protocol type not exists!");
    }
    if($socket != null) {
      $this->socket = $socket;
    }else {
      $this->socket = socket_create($domin,$type,$this->protocol);
    }
    if($this->socket === false)
      exit($this->last_error()->getTraceAsString());
  }
  
  
  public function getServerInstance($resource)
  {
    return new ServerSocket($this->domin_type,$this->type,$resource);
  }
  public function getClientInstance($resource,$cid = null)
  {
    return new ClientSocket($this->domin_type,$this->type,$resource,$cid);
  }

  /* param SocketBase[] */
  public static function get_resources(array $sockets):array
  {
    $res = [];
    foreach($sockets as $key=>$socket)
    {
      $res[$key] = $socket->getSocketResource();
    }
    return $res;
  }
  
  public function bind(string $address = '0',int $port = 0):SocketBase
  {
    socket_bind($this->socket,$address,$port);
    return $this;
  }
  
  public function equals(SocketBase $socket)
  {
    return $socket->getSocketResource() == $this->socket;
  }
  
  
  public function _read(int $length)
  {
    if($this->closed) return "";
    return socket_read($this->socket,$length);
  }
  
  public function read(int $length = 1024):?string
  {
    $data = $this->_read($length);
    if($data == "") {
      if($data === "")
        return null;
      return "";
    }
    return $data;
  }
  
  public function receive(&$buffer,int $length = 1024):SocketBase
  {
    $buffer = $this->read($length);
    return $this;
  }
  
  public function _write(string $msg,int $length)
  {
    if($this->closed) return false;
    return socket_write($this->socket,$msg,$length);
  }
  
  public function write(string $msg):?SocketBase
  {
    $length = strlen($msg);
    while(true) {
      $sent = $this->_write($msg,$length);
      if($sent === false)
        return null;
      if($sent < $length) {
        $msg = substr($msg,$sent);
        $length -= $sent;
      }else {
        break;
      }
    }
    return $this;
  }
  
  public function shutdown():bool
  {
    return @socket_shutdown($this->socket,2);
  }
  
  public function close():void
  {
    $this->preClose();
    socket_close($this->socket);
  }
  
  public function safeClose():void
  {
    $this->shutdown();
    $this->close();
  }

  public function preClose():void
  {
    $this->closed = true;
  }

  public function closed():bool
  {
    return $this->closed;
  }

  protected function last_error(): Exception
  {
    return new Exception("[Socket] Error ".socket_strerror(socket_last_error()));
  }
  
  public function setBlock():bool
  {
    return socket_set_block($this->socket);
  }
  
  public function setNonBlock():bool
  {
    return socket_set_nonblock($this->socket);
  }
  
  public function getSocketResource()
  {
    return ($this->socket);
  }
  
  public function getSockName():?string
  {
    $code = socket_getsockname($this->socket,$address);
    return $code ? $address : null;
  }
  
  public function getSockAddr():?string
  {
    $code = socket_getsockname($this->socket,$address,$port);
    return $code ? $address.":".$port : null;
  }
  
  public function recSockName(&$name):SocketBase
  {
    $name = $this->getSockName();
    return $this;
  }
  
  public function recSockAddr(&$addr):SocketBase
  {
    $addr = $this->getSockAddr();
    return $this;
  }
  
  
}