<?php

$auth_user  = null;
$db         = null;
$message    = null;
$data       = [];
$errors     = [];
$headers    = [];
$except_routes = [];


/**
 * show template file
 */
function display($tpl_name, $data = [], $code = 200)
{
  global $tpl;
  // if ajax ? print json
  $tpl_name = str_replace('.', '/', $tpl_name);
  return $tpl->display($tpl_name, $data, $code);
}

function tpl($tpl_name, $data = [])
{
  global $tpl;
  $tpl_name = str_replace('.', '/', $tpl_name);
  return $tpl->tpl($tpl_name, $data);
}

function abort($tpl_name, $data = [], $code = 200)
{
  echo display($tpl_name, $data, $code);
  die;
}

function print_json($data = [], $message = null, $code = 200)
{
  global $tpl;
  return $tpl->print_json($data, $message, $code);
  exit;
}


/**
 * encode HTML spcecial chars
 *
 * @param string $html
 */
function escape($html=null)
{
  if(!is_string($html)) return $html;
  return htmlspecialchars($html ? $html : '', ENT_QUOTES, "UTF-8", true);
}


/**
 * check current method
 */
function method($method = null)
{
  return METHOD == strtoupper(trim($method));
}



function except($routes = [])
{
  global $except_routes;
  return $except_routes = $routes;
}

function is_except()
{
  global $current_route, $except_routes;
  return is_string($except_routes) ? $except_routes == '*' : in_array($current_route, $except_routes);
}



function auth($key = null)
{
  global $auth_user;

  if(is_null($key) || empty($auth_user)) return null;

  // check if checking type
  if(is_array($key)) {
    foreach ($key as $k => $v) {
      if($auth_user['status'] != $v) continue;
      return true;
    }

    return goBack(lang('notauth'));
  }

  return ($key === true ? $auth_user : $auth_user[$key]);
}



function env($key = null, $default = '')
{
  return getenv($key) != false ? getenv($key) : $default;
}

function loadenv()
{
  $envfile = '';
  if (!file_exists(APP_DIR . '/.env')) return;
  if (file_exists(APP_DIR . '/.env')) $envfile = explode("\n", file_get_contents(APP_DIR . '/.env'));
  foreach ($envfile as $v) {
    if (trim($v) == '' || strpos(trim($v), '#') !== false) continue;
    $sep = strpos($v, '=');
    putenv(trim(substr($v, 0, $sep)) . '=' . trim(substr($v, $sep + 1)));
  }
}


function currentUrl()
{
  $requestUri = $_SERVER['REQUEST_URI'];
  return domain() . urlencode(htmlentities($requestUri));
}

/**
 * get cureent domain
 */
function domain()
{
  $http = (isset($_SERVER['HTTPS'])) ? 'https' : 'http';
  $host = $_SERVER['HTTP_HOST'];
  return $http . '://' . htmlentities($host);
}




/**
 * get http headers
 *
 */
function headers($key = null)
{
  global $headers;

  if (empty($headers)) {
    $headers = [];

    foreach ($_SERVER as $name => $value) {
      $http = substr($name, 0, 5);
      if ($http == 'HTTP_') {
        $name = str_replace('_', '-', substr(strtolower($name), 5));
        $headers[$name] = $value;
      }
    }
  }

  return is_null($key) ? $headers : $headers[$key];
}



function is_ajax()
{
  global $ext;
  $header = strtolower(headers('x-requested-with'));
  return ($header == 'xmlhttprequest') ? true : ($header == 1 ? true : $ext == 'json');
}




function str_starts_with($haystack, $needle)
{
  return preg_match("#^(" . $needle . ")#ui", $haystack);
}



// errors
function add_error($message = '', $item)
{
  if (!@$_SESSION['ERRORS']) $_SESSION['ERRORS'] = [];
  $_SESSION['ERRORS'][$item] = lang($message) ? lang($message) : $message;
}

function errors($key = null)
{
  return is_null($key) ? $_SESSION['ERRORS'] : $_SESSION['ERRORS'][$key];
}

function is_error()
{
  return !empty($_SESSION['ERRORS']);
}

function get_errors()
{
  global $errors;
  if (empty($errors)) $errors = @$_SESSION['ERRORS'];
  unset($_SESSION['ERRORS']);
  return $errors;
}

function show_errors()
{
  if (is_error()) {
    // عمل صفحة الأخطاء
    $errors = errors();
    echo display('errors');
    die;
  }
}



function message($message = '')
{
  $_SESSION['MESSAGE'] = $message;
}

function get_message()
{
  global $message;
  if (!$message) $message = @$_SESSION['MESSAGE'];
  unset($_SESSION['MESSAGE']);
  return $message;
}



function db()
{
  global $db;

  if (is_null($db)) {
    $db = new dbmysqli(config('db'));
  }

  return $db;
}



function redirect($url, $with_message = null)
{
  if ($with_message) message($with_message);
  if (!str_starts_with($url, 'http')) $url =  SUB_DIR . '/' . $url;
  $url = trim($url, '/');
  if (!str_starts_with($url, 'http')) $url = domain() . '/' . $url;

  if (!is_ajax()) {
    header('location: ' . $url);
    exit;
  }

  echo print_json([
    'redirect' => $url
  ], get_message(), 307);
}

function goBack($with_message = null)
{
  redirect($_SERVER['HTTP_REFERER'], $with_message);
}

function query($key=null)
{
  return is_null($key) ? $_GET : $_GET[$key];
}

function param($key=null)
{
  global $params;
  return is_null($key) ? $params : $params[$key];
}

function data($key = null)
{
  global $data;

  if (empty($data)) {
    $out = $input = empty($_POST) ? urldecode(file_get_contents("php://input")) : $_POST;
    if (!is_array($input)) $out = json_decode($input, true);
    if (!is_array($out)) parse_str($input, $out);

    $data = $out;
  }

  return is_null($key) ? $data : $data[$key];
}


function files($key)
{
  return new File(@$_FILES[$key], config('app.upload'));
}



##########
########## VALIDATION
##########
function validate(array $rules)
{
  $source = data();
  foreach ($rules as $item => $conditions) {
    $conditions = explode('|', $conditions);
    $data2 = $source[$item];

    // check if it's array
    if (in_array('list', $conditions)) {
      $data2 = array_filter($data2, function ($v) {
        return !is_null($v) && $v !== '';
      });
    }
    // check if it's required
    if (is_null($data2) || empty($data2) || intval($data2) < -1) {
      if (in_array('required', $conditions)) add_error("validation.required", $item);
      continue;
    }

    foreach ($conditions as $cond/*ition*/) {
      $rr = explode(':', $cond);
      $rule = @$rr[0];
      $value = @$rr[1];
      switch ($rule) {
        case 'list':
          if (!is_array($data2)) add_error("validation.list.false", $item);
          break;
        case 'inlist':
          $accepted = explode(',', $value);
          if(is_array($data2)) {
            if((array_intersect($data2, $accepted))) add_error(lang("validation.list.notIncluded") . ' [' . $value . ']', $item);
          } else {
            if(!in_array($data2, explode(',', $accepted))) add_error(lang("validation.list.notIncluded") . ' [' . $value . ']', $item);
          }
          break;
        case 'max':
          strlen((string) $data2) <= intval($value) ? '' :
            add_error("validation.max.string", $item);
          break;
        case 'min':
          strlen((string) $data2) >= intval($value) ? '' :
            add_error("validation.min.string", $item);
          break;
        case 'gt':
          if (!preg_match("/^\d+$/u", $value)) {
            intval($data2) > intval($source[$value]) ? '' :
              add_error("validation.max.num", $item);
          } else {
            intval($data2) > intval($value) ? '' :
              add_error("validation.max.num", $item);
          }
          break;
        case 'lt':
          if (!preg_match("/^\d+$/u", $value)) {
            intval($data2) < intval($source[$value]) ? '' :
              add_error("validation.min.num", $item);
          } else {
            intval($data2) < intval($value) ? '' :
              add_error("validation.min.num", $item);
          }
          break;
        case 'match_auth_password':
          password_verify($data2, auth('password')) ? '' :
            add_error("validation.match.current_pass", $item);
          break;
        case 'match':
          $data2 == $source[$value] ? '' :  add_error("validation.match.false", $item);
          break;
        case 'notmatch':
          $data2 != $source[$value] ? '' :  add_error("validation.match.true", $item);
          break;
        case 'email':
          filter_var($data2, FILTER_VALIDATE_EMAIL) ? '' :  add_error("validation.email", $item);
          break;
        case 'num':
          $data2 == intval($data2) ? '' :  add_error("validation.num", $item);
          break;
      }
    }
  }

  if (is_error()) {
    goBack();
    exit;
  }
}


function mailto($toEmail, $toName = null, $subject, $content)
{
  $mail = new PHPMailer(true);
  
  //Server settings
  $mail->SMTPDebug = SMTP::DEBUG_OFF;
  $type   = config('mail.mail');
  $config = config('mail.' . $type);
  if($config['type'] == 'SMTP') {
    $mail->isSMTP();
    $mail->Host       = $config['host'];
    $mail->SMTPAuth   = $config['auth'];
    $mail->Username   = $config['username'];
    $mail->Password   = $config['password'];
    $mail->Port       = $config['port'];
    if($config['tls']) $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
  }
  $mail->CharSet      = 'UTF-8';
  
  //Recipients
  $mail->setFrom(config('mail.from.email'), config('mail.from.name'));
  $mail->addReplyTo(config('mail.from.email'), config('mail.from.name'));
  if(is_array($toEmail)) {
    foreach ($toEmail as $k => $email) {
      $mail->addAddress($email, is_array($toName) ? $toName[$k] : '');
    }
  }
  else $mail->addAddress($toEmail, $toName);

  //Content
  $mail->isHTML(true);
  $mail->Subject = $subject;
  $mail->Body    = $content;

  try {
    $mail->send();
    return true;
  } catch (\Throwable $th) {
    return $mail->ErrorInfo;
  }
}
