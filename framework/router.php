<?php

$ext    = null;
$routes = [];
$params = [];
$uri    = [];
$annot  = false;
$current_route = null;

function get_annotations()
{
  global $routes, $uri;
  $funcs = get_defined_functions()['user'];
  foreach ($funcs as $func) {
    if(!str_starts_with($func, 'script_')) continue;
    
    $name = str_replace('script_', '', $func);
    $fncRefl = new ReflectionFunction($func);
    $com = $fncRefl->getDocComment();
    if(!empty($com)) {
      preg_match_all("/(?:\@)([A-Z][a-z]+)\s(.+)/u", $com, $annot);
      $res = array_combine($annot[1], $annot[2]);
      $method = $res['Method'] ? $res['Method'] : METHOD;
      if(!method($method)) continue;
      $name = trim(isset($res['Name']) ? $res['Name'] : $name);
    }
    $routes[$name] = $func;
  }
}

$uri = strtolower($_SERVER['REQUEST_URI']);
$uri = substr($uri, 0, strpos($uri, '?') ? strpos($uri, '?') : strlen($uri));
$ext = pathinfo($uri, PATHINFO_EXTENSION);
$uri = preg_replace("#[^a-z0-9\.\-_/%]+#ui", '', $uri);

if (preg_match("#^\/" . SUB_DIR . "#ui", $uri))  {
  $uri = preg_replace("#^\/" . SUB_DIR . "#ui", '', $uri);
}

$uri = preg_replace("#\." . $ext . "$#u", '/', $uri);
$uri = preg_replace("#[/]+#u", '/', $uri);
$uri = explode('/', trim($uri, '/'));

$file = empty($uri) ? 'home' : $uri[0];

if(!file_exists(APP_DIR . '/script/' . $file . '.php')) $file = 'home';
else $file = array_shift($uri);
if(!file_exists(APP_DIR . '/script/' . $file . '.php')) die("{$file} not exist");
require_once APP_DIR . '/script/' . $file . '.php';

$func = @$uri[0];
get_annotations();

if (in_array($func, array_keys($routes))) {
  array_shift($uri);
}
else
{
  $func = 'index';
}

if(in_array($func, array_keys($routes))) {
  $func = $routes[$func];
}
else
{
  die(404);
}


// elseif(!$annot) $func = array_shift($uri);
// if(!in_array($func, array_values($routes))) die('404');
// if(!function_exists($func)) die("{$func} not found");

$current_route = str_replace('script_', '', $func);

$paramslist = [];
$fncRefl = (new ReflectionFunction($func))->getParameters();
foreach ($fncRefl as $p) {
  $paramslist[] = $p->name;
}

if(count($paramslist) == count($uri)) $params = array_combine($paramslist, $uri);
else $params = $uri;


// get main.php if exists
if(file_exists(APP_DIR . '/main.php') && !defined('NO_MAIN')) require_once APP_DIR . '/main.php';

// get helpers.php if exists
if(file_exists(APP_DIR . '/helpers.php')) require_once APP_DIR . '/helpers.php';


// show the page
$res = call_user_func_array($func, $uri);

if(is_array($res)) echo print_json($res);
else echo $res;

