<?php

date_default_timezone_set("Asia/Dubai");

function dd($var)
{
  die(var_dump($var));
}

//config('db.host');
function config($key)
{
  $key = explode('.', $key);
  $file = array_shift($key);
  $config = include(APP_DIR . '/config/' . $file . '.php');
  $out = $config;

  foreach ($key as $val) {
    $out = $out[$val];
  }

  return $out;
}

function lang($key=null)
{
  if(is_null($key)) return getlang();
  
  $lang = getlang();
  $langfile = APP_DIR . '/lang/' . $lang . '.php';
  if(!file_exists($langfile)) {
    setlang('ar');
    return lang($key);
  }

  $config = include($langfile);
  $out = $config;
  
  $key = explode('.', $key);
  foreach ($key as $val) $out = $out[$val];

  return $out;
}

function setlang($lang)
{
  $_SESSION['APP_LANG'] = $lang;
}

function getlang()
{
  return $_SESSION['APP_LANG'] ?: 'ar';
}

function json($key)
{
  $key = explode('.', $key);
  $file = array_shift($key);
  ob_start();
  include(APP_DIR . '/json/' . $file . '.json');
  $config = json_decode(ob_get_clean(), true);
  $out = $config;

  foreach ($key as $val) $out = $out[$val];

  return $out;
}

function lib($name)
{
  if(substr($name,0, 3) == 'app') {
    $name = str_replace("\\", "/", substr($name,4));
    $lib =   APP_DIR . '/' . $name . '.php';
  } else {
    $lib = __DIR__ . '/lib/' . $name . '.php';
  }
  if (!file_exists($lib)) return false;
  require_once $lib;
}


spl_autoload_register('lib', false, true);


if (!defined('APP')) {
  define('APP', 'app');
}

define('APP_DIR', dirname(__DIR__) . '/' . APP);

// get request method type
define('METHOD', strtoupper(in_array(strtoupper(@$_POST['_method']), ['DELETE','PUT']) ? $_POST['_method'] : $_SERVER['REQUEST_METHOD']));

// loading helpers
require_once __DIR__  . '/helpers.php';

// execute defaults
loadenv();



define('PUBLIC_DIR', dirname(__DIR__) . '/public');

if (!defined('SUB_DIR')) {
  define('SUB_DIR', env('SUBDIR', basename(dirname(__DIR__))));
}


error_reporting(E_ALL & ~E_NOTICE);
if (defined('DEBUG') && DEBUG) {
  new ErrorHandler;
} else error_reporting(0);


session_save_path(config('app.session_dir'));


if (defined('SESSION')) {
  if (SESSION) session_start();
  else $_SESSION = [];
} else $_SESSION = [];


if (!defined('TEMPLATE')) {
  $tpl = new template();
}


require_once __DIR__  . '/router.php';
