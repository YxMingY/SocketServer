<?php
function autoload(string $class)
{
  $class = str_replace('\\', '/', $class);
  $file_name = dirname(__FILE__).'/'.$class.'.php';
  require_once $file_name;
}
spl_autoload_register("autoload");
$s = new \yxmingy\socket\server\AsyncServer("0.0.0.0",2333);
$s->onConnect(function ($c,$s) {
  $c->write("Welcome.\n");
  $s->broadcast($c->getPeerAddr()." connected ~ welcome\n");
});
$s->onMessage(function ($c,string $msg,$s) {
  $s->broadcast($c->getPeerAddr().": ".$msg.PHP_EOL);
  if(trim($msg) == "stop") {
    $s->stop();
    //zend_mm_heap corrupted
  }
  $s->kick($c);
});
$s->onDisconnect(function ($c,$s) {
  $c->write("bye\n");
  $s->broadcast($c->getPeerAddr()." disconnected\n");
});
$s2 = new \yxmingy\socket\server\NormalServer("0.0.0.0",2334);
$s2->onConnect(function ($c,$s) {
  $c->write("Welcome to s2.\n");
  $s->broadcast($c->getPeerAddr()." connected2 ~ welcome\n");
});
$s2->onMessage(function ($c,string $msg,$s) {
  $s->broadcast($c->getPeerAddr().": ".$msg.PHP_EOL);
  if(trim($msg) == "stop") {
    $s->stop();
  }
});
$s2->onDisconnect(function ($c,$s) {
  $c->write("2bye\n");
  $s->broadcast($c->getPeerAddr()." 2disconnected\n");
});
$s->start();
$s2->start();
echo "aa";
//
////异步测试
//while(1)
//{
//  echo "a";
//  sleep(1);
//}