<?php
function autoload(string $class)
{
  $class = str_replace('\\', '/', $class);
  $file_name = dirname(__FILE__).'/'.$class.'.php';
  require_once $file_name;
}
spl_autoload_register("autoload");