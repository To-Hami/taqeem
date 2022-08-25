<?php
class template
{
  private $tpl_content;
  public static $public = [];
  public static $TPL_DIR;
  public static $TPL_EXT = 'tpl';
  public static $CACHE = true;
  private static $HTTP_CODES = [
    '200' => 'OK',
    '201' => 'Created',
    '202' => 'Accepted',
    '301' => 'Moved Permanently',
    '307' => 'Temporary Redirect',
    '400' => 'Bad Request',
    '401' => 'Unauthorized',
    '402' => 'Payment Required',
    '403' => 'Forbidden',
    '404' => 'Not Found',
    '405' => 'Method Not Allowed',
    '406' => 'Not Acceptable',
    '422' => 'Unprocessable Entity',
    '500' => 'Internal Server Error',
    '502' => 'Bad Gateway',
    '503' => 'Service Unavailable',
  ];
  private $ignoreAjax = false;

  // Setting
  public function __construct()
  {
    self::$TPL_DIR = APP_DIR . '/tpl';
    //$this->tpl_ext = '.'.$config['TemplateExt'];
  }

  public function tpl($TemplateName, $data = []) {
    $this->ignoreAjax = true;
    return $this->display($TemplateName, $data, 200);
  }

  // Display Template File
  public function display($TemplateName, $data = [], $code = 200)
  {
    if (is_ajax() && !$this->ignoreAjax) return $this->print_json($data, null, $code);

    extract($data ?: []);
    extract(self::$public);

    $TemplateName  = str_replace(".", "/", $TemplateName);
    $TemplateName  = self::$TPL_DIR . '/' . $TemplateName . '.' . self::$TPL_EXT . '.html';

    $TemplateCache  = APP_DIR . '/cache/' . md5($TemplateName) . '.cache' ;

    // orginal tpl modefied ??
    if (self::$CACHE && file_exists($TemplateCache)) {
      if (filemtime($TemplateName) <= filemtime($TemplateCache)) {
        include  $TemplateCache;
        return '';
      }
    }

    // tpl path
    $outtpl = $TemplateName;


    ob_start(); // Get tpl content
    @include $outtpl;
    $this->tpl_content = ob_get_contents();
    ob_end_clean();
    $out = $this->__compile($this->tpl_content);

    if (self::$CACHE) {
      // save cache;
      $f = fopen($TemplateCache, "w+");
      fwrite($f, $out);
      fclose($f);
      // saved
    }


    //echo $out;
    ob_start();
    eval(" ?>" . $out . "<?php "); //*/
    $html = ob_get_clean();

    return $html;
  }

  public function print_json($data = [], $message = null, $code = 200)
  {
    if (isset($_SERVER['HTTP_ORIGIN'])) {
      header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
      header('Access-Control-Allow-Credentials: true');
    } else {
      header("Access-Control-Allow-Origin: *");
    }

    $http_message = array_key_exists($code, self::$HTTP_CODES) ? self::$HTTP_CODES[$code] : '';

    header('HTTP/1.1 ' . $code . ' ' . $http_message);
    header('Access-Control-Allow-Headers: Authorization, X-Brq-Auth, X-Requested-With, Content-Type, User-Agent, Origin ');
    header('Access-Control-Allow-Methods: GET, PUT, POST, DELETE, OPTIONS');
    header('Content-Type: application/json');

    $out = [];
    $is_data = true;
    $out["code"]    = $code;
    $out["message"] = $message ? $message : $http_message;
    $out['errors']  = get_errors();
    $out['data']    = [];

    //if(count(query())) $out['query'] = query();
    if (empty($out['errors'])) unset($out['errors']);

    if (is_array($data)) {
      if (array_key_exists('redirect', $data)) {
        $out['redirect'] = $data['redirect'];
        $is_data = false;
      }
      if (array_key_exists('data', $data)) {
        $out['data'] = $data['data'];
        $is_data = false;
      }
    }

    if ($is_data) $out["data"] = $data;

    return json_encode($out, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE);
  }

  // Compile
  private function __compile($Content)
  {
    $Content = preg_replace("/{(else)}/Ui", "<?php } else { ?>", $Content);
    $Content = preg_replace("/{\/(if|repeat|inlist|listelse)}/Ui", "<?php } ?>", $Content);
    $Content = preg_replace("/{(listelse)}/Ui", "<?php }}else{ ?>", $Content);
    $Content = preg_replace("/{\/(list)}/Ui", "<?php }} ?>", $Content);

    $Content = preg_replace_callback("/{display=\"([a-zA-Z0-9_\-\.\/]+)\"}/Ui", array($this, '__include'), $Content);
    $Content = preg_replace_callback("/{(if|elseif)\s([a-zA-Z][a-zA-Z0-9_]*.*)\s([eq|neq|gt|ge|lt|le]+)\s(.+)}/Ui", array($this, '__if'), $Content);
    $Content = preg_replace_callback("/{(if|elseif) ([$]*[a-zA-Z][a-zA-Z0-9_\-\.]*) ([eq|neq|gt|ge|lt|le]+) (.*)}/Ui", array($this, '__if'), $Content);
    $Content = preg_replace_callback("/{(if|elseif) ([$]*[a-zA-Z][a-zA-Z0-9_\-\.]*) ([eq|neq|gt|ge|lt|le]+) ([$]*[a-zA-z][a-zA-z0-9_\-\.]*)}/Ui", array($this, '__if'), $Content);
    $Content = preg_replace_callback("/{(if|elseif) (.*)}/Ui", array($this, '__if'), $Content);
    $Content = preg_replace_callback("/{inlist=\"([$]*[a-zA-Z][a-zA-Z0-9_\-\.]*)\" search=\"([$]*[a-zA-Z][a-zA-Z0-9_\-\.]*)\"}/Ui", array($this, '__inlist'), $Content);
    $Content = preg_replace_callback("/{list=\"([$]*[a-zA-Z][a-zA-Z0-9_\-\.]*)\" name=\"([A-Za-z0-9_]+)\"}/Ui", array($this, '__list'), $Content);
    $Content = preg_replace_callback("/{list=\"(.+)\" name=\"([A-Za-z0-9_]+)\"}/Ui", array($this, '__list'), $Content);
    $Content = preg_replace_callback("/{(break|continue)}/Ui", array($this, '__keywords'), $Content);
    $Content = preg_replace_callback("/{([a-zA-Z\-_]+)\((.*)\)}/Ui", array($this, '__function2'), $Content);
    $Content = preg_replace_callback("/{function=\"([a-zA-Z\-_]+)\((.*)\)(,[A-Za-z_]+)*\"}/Ui", array($this, '__function'), $Content);
    $Content = preg_replace_callback("/{repeat=\"([-]*[0-9]+),([0-9]+),([-]*[0-9]+)\"}/Ui", array($this, '__repeat'), $Content);
    $Content = preg_replace_callback("/{([$]+[a-zA-Z][a-zA-Z0-9_\-]*) = (.*)}/Ui", array($this, '__uservars'), $Content);
    $Content = preg_replace_callback("/{([$]*[a-zA-Z][a-zA-Z0-9_\-\.]*)}/Ui", array($this, '__vars'), $Content);
    $Content = preg_replace_callback("/{!!(.+)!!}/Ui", array($this, '__varsUnsafe'), $Content);
    $Content = preg_replace_callback("/{^([$]*[a-zA-Z0-9_]+.+[a-zA-Z0-9_]*)}/Ui", array($this, '__vars'), $Content);
    //$Content = preg_replace_callback("/{apply_hook=\"([a-zA-Z0-9_\-\/]+)\"}/Ui",array($this,'__apply_hook'),$Content);
    //$Content = preg_replace_callback("/{link\(([a-zA-Z0-9_\-\/\.]+)(,\s([$]*[a-zA-Z][a-zA-Z0-9_\-\.]*))*\)}/Ui",array($this,'__link'),$Content);

    return $Content;
  }

  #######################
  #######compilers#######
  #######################

  // vars
  private function __keywords($Matches, $Print = TRUE)
  {
    return  '<?php ' . $this->__vars($Matches, false) . '; ?>';
  }

  private function __varsUnsafe($Matches, $Print = TRUE)
  {
    return  '<?= ' . $this->__vars($Matches, false) . '; ?>';
  }

  // vars
  private function __vars($Matches, $Print = TRUE)
  {
    $out = $Matches[1];
    $out2 = '';
    $Firstchr = substr($out, 0, 1); // first char of variable
    
    
    // check if array?
    if (@preg_match("#(\.+)#", $Matches[1])) {
      $Matches[1] = explode('.', $Matches[1]);
      $out        = array_shift($Matches[1]); // remove first item
      $out2       = '[\'' . implode('\'][\'', $Matches[1]) . '\']';
    }

    $out             = $out . $out2;
    if ($Print) $out = '<?= escape(' . $out . '); ?>';

    return $out;
  }

  // include
  private function __include($Matches)
  {
    $out = '<?= $this->display("' . $Matches[1] . '", $data); ?>';
    return $out;
  }

  // apply hook
  private function __apply_hook($Matches)
  {
    $out = '<?php $this->c->apply_hook("template_' . $Matches[1] . '"); ?>';
    return $out;
  }

  // link
  private function __link($Matches)
  {
    $out = $this->__vars(array('0', $Matches[3]), FALSE);
    $out = '<?= BRQ()->getLink("' . $Matches[1] . '".' . $out . '); ?>';

    return $out;
  }

  // User vars
  private function __uservars($Matches)
  {
    $out = '<?php @' . $Matches[1] . ' = ' . $this->__vars(['', $Matches[2]], false) . '; ?>';
    return $out;
  }


  // if statement
  private function __if($Matches)
  {
    $out = '';
    switch (@$Matches[3]) {
      case 'eq':
        $out = ' == ';
        break;

      case 'neq':
        $out = ' != ';
        break;

      case 'gt':
        $out = ' > ';
        break;

      case 'ge':
        $out = ' >= ';
        break;

      case 'lt':
        $out = ' < ';
        break;

      case 'le':
        $out = ' <= ';
        break;
    }

    $Matches[1] = ($Matches[1] == 'elseif') ? '}elseif' : 'if';
    $out = '<?php ' . $Matches[1] . '(' . $this->__vars(array('0', @$Matches[2]), FALSE) . $out . $this->__vars(array('0', @$Matches[4]), FALSE) . '){ ?>';

    return $out;
  }

  // function
  private function __function($Matches)
  {
    $Parametres = explode(',', $Matches[2]);
    foreach ($Parametres as $k => $v) {
      $Parametres[$k] = $this->__vars(array('0', $v), FALSE);
    }
    $Parametres = implode(',', $Parametres);

    $Matches[3] = (@$Matches[3] != '') ? '$' . substr($Matches[3], 1) . ' = ' : '';
    $out = '<?php ' . $Matches[3] . $Matches[1] . '(' . $Parametres . '); ?>';

    return $out;
  }

  private function __function2($Matches)
  {
    $Parametres = explode(',', $Matches[2]);
    foreach ($Parametres as $k => $v) {
      if(preg_match("#^'([^']+)'#ui", $v)) {
         $Parametres[$k] = $v;
      } 
      else $Parametres[$k] = $this->__vars(array('0', $v), FALSE);
    }
    $Parametres = implode(',', $Parametres);
    
    $out = '<?= ' . $Matches[1] . '(' . $Parametres . '); ?>';

    return $out;
  }

  // list
  private function __list($Matches)
  {
    $arr = $this->__vars($Matches, FALSE);
    $out = '<?php  $count_' . $Matches[2] . ' = count(' . $arr . '); $i' . $Matches[2] . ' = 0;';
    $out .= 'if(is_array(' . $arr . ') && count(' . $arr . ') > 0){';
    $out .= 'foreach(' . $arr . ' as $key' . $Matches[2] . ' => $' . $Matches[2] . '){ $i' . $Matches[2] . '++;  ?>';
    return $out;
  }

  // in list
  private function __inlist($Matches)
  {
    $v1 = $this->__vars($Matches, FALSE);
    $v2 = $this->__vars(array('0', $Matches[2]), FALSE);

    $out = '<?php  if(in_array(' . $v2 . ', ' . $v1 . ')){ ?>';
    return $out;
  }

  // repeat
  private function __repeat($Matches)
  {
    static $repeati;
    $repeati++;

    $out  = '<?php $rvalue=' . $Matches[1] . ';';
    $out .= 'for($i' . $repeati . '=0; $i' . $repeati . '<' . $Matches[2] . '; $i' . $repeati . '++){';
    $out .= '$rvalue = $rvalue +' . $Matches[3] . '; ?>';

    return $out;
  }
}
