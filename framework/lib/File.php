<?php
/*
** BRQ
**
** @author     Binkhamis(BRQ) <bkhamis0@gmail.com>
** @copyright  BRQnet © 2021
** @link       http://brqnet.com
**
** ------------------------------------------------------------------- */


class File
{
  private $file,
          $config,
          $uploaded = false,
          $multi = false;

  public function __construct( $file,  $config=[])
  {
    $this->file = $file;
    if($this->file == null) return null;
    if(!is_null($config)) $this->config = $config;
    $this->multi= is_array($file['name']);
    $this->buildArray($file);
  }

  private function buildArray( $files)
  {
    if(!$this->is_multi()) {
      $mime = mime_content_type($this->file['tmp_name']);
      $mime2= explode('/', $mime);
      $this->file['mime'] = $mime2[1];
      $this->file['type'] = array_shift($mime2);
      $this->file['ext']  = pathinfo($this->file['name'], PATHINFO_EXTENSION);
      return;
    }

    $list = [];
    foreach ($files['name'] as $k => $v) {
      $mime = mime_content_type($files['tmp_name'][$k]);
      $mime2= explode('/', $mime);
      $list[$k] = new self([
        'name'  => $files['name'][$k],
        'error' => $files['error'][$k],
        'size'  => $files['size'][$k],
        'mime'  => $mime2[1],
        'type'  => array_shift($mime2),
        'ext'   => pathinfo($files['tmp_name'][$k], PATHINFO_EXTENSION),
        'tmp_name'  => $files['tmp_name'][$k],
      ], $this->config);
    }

    $this->file = $list;
  }

  private function getFile($key=null)
  {
    if($this->file == null) return null;
    if ($this->is_multi()) {
      return is_null($key) ? $this->file[0] : $this->file[$key];
    }
    
    return $this;
  }

  public function is_uploaded()
  {
    return $this->is_multi() ? null : ($this->info('error') == 0 ? true : false);
  }

  public function is_multi()
  {
    return $this->multi;
  }

  public function get_files()
  {
    return $this->file;
  }

  public function __invoke($key=null)
  {
    return $this->getFile($key);
  }

  public function info( $type=null)
  {
    if(!in_array($type, ['size', 'type', 'name', 'mime', 'ext', 'error', 'url'])) return null;
    return @$this->file[$type];
  }

  public function can_upload()
  {
    if($this->is_multi()) return false;
    if($this->info('error') > 0) return false;
    if($this->info('size') == 0) return false;
    
    if(isset($this->config['size'])) {
      if($this->info('size') > $this->config['size']) return false;
    }

    if(isset($this->config['type'])) {
      if(!in_array($this->info('mime'), explode(',', $this->config['type']))) return false;
    }

    return true;
  }

  public function upload( $dir=null, string $filename=null)
  {
    // do upload
    //file('image')->uplod() <== single;
    //file('image')($keyORindex)->uplod() <== multi;
    if(!$this->can_upload()) return false;
    if($this->is_multi()) return false;
    if($this->uploaded) return $this->info('url');

    $dir = $dir ? $dir : $this->config['upload_dir'];
    if(!$filename) $filename = $this->info('name');
    $newfile = preg_replace("#/+#", '/', $dir . '/' . $filename);

    // uploading...
    if (move_uploaded_file($this->file['tmp_name'], $newfile)) {
      $this->file['tmp_name'] = $newfile;
      $this->file['url']      = '/' . trim(str_replace(PUBLIC_DIR, SUB_DIR, $newfile), '/');
      $this->file['name']     = pathinfo($newfile, PATHINFO_BASENAME);
      $this->uploaded         = true;
      return $this->info('url');
    }

    return false;
  }
  
}