<?php
require_once "Socket/socket_h.php";
require_once "AsyncServer.php";
require_once "NormalServer.php";
$s = new \yxmingy\AsyncServer("0.0.0.0",2333);
$s->onConnect(function ($c,$s) {
  $c->write("Welcome.\n");
  //var_dump($c);
  //var_dump($s);
  $s->broadcast($c->getPeerAddr()." connected ~ welcome\n");
});
$s->onMessage(function ($c,string $msg,$s) {
  $s->broadcast($c->getPeerAddr().": ".$msg.PHP_EOL);
  $s->kick($c);
});
$s->onDisconnect(function ($c,$s) {
  $c->write("bye\n");
  $s->broadcast($c->getPeerAddr()." disconnected\n");
});
$s2 = new \yxmingy\AsyncServer("0.0.0.0",2334);
$s2->onConnect(function ($c,$s) {
  $c->write("Welcome to s2.\n");
  //var_dump($c);
  //var_dump($s);
  $s->broadcast($c->getPeerAddr()." connected2 ~ welcome\n");
});
$s2->onMessage(function ($c,string $msg,$s) {
  $s->broadcast($c->getPeerAddr().": ".$msg.PHP_EOL);
  $s->kick($c);
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
