<?php
require_once "SocketServer.php";
$s = new \yxmingy\SocketServer("0.0.0.0",2333);
$s->onConnect(function ($c) use($s) {
  $c->write("Welcome.\n");
  $s->broadcast($c->getPeerAddr()." connected ~ welcome\n");
});
$s->onMessage(function ($c,string $msg) use($s) {
  $s->broadcast($c->getPeerAddr().": ".$msg.PHP_EOL);
});
$s->onDisconnect(function ($c) use($s) {
  $c->write("bye\n");
  $s->broadcast($c->getPeerAddr()." disconnected\n");
});
$s->start();