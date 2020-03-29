$<?php
function autoload(string $class)
{
  $class = str_replace('\\', '/', $class);
  $class = substr($class,strlen('/yxmingy/socket'));
  $file_name = dirname(__FILE__).'/'.$class.'.php';
  require_once $file_name;
}
spl_autoload_register("autoload");
$s = new \yxmingy\socket\server\NormalServer("0.0.0.0",2333);
$s->onConnect(function ($c,$s) {
  echo $c->getPeerAddr().PHP_EOL;
  $c->write("Welcome.\n");
  $s->broadcast($c->getPeerAddr()." connected ~ welcome\n");
});
$s->onMessage(function ($c,string $msg,$s) {
  echo $c->getPeerAddr().$msg.PHP_EOL;
  $s->broadcast($c->getPeerAddr().": ".$msg.PHP_EOL);
  if(trim($msg) == "stop") {
    $s->stop();
    //zend_mm_heap corrupted
  }
  $s->kick($c);
});
$s->onDisconnect(function ($c,$s) {
  echo $c->getPeerAddr()."disc".PHP_EOL;
  $c->write("bye\n");
  $s->broadcast($c->getPeerAddr()." disconnected\n");
});
$s->start();