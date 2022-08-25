<?php

// change language
if(in_array(query('lang'), [ 'ar', 'en' ])) setlang(query('lang'));

// AUTH
if(isset($_SESSION[config('auth.LOGIN')])) {
  $token = db()->select('tokens', 'user_id,token', [
    '`expire_at` > ' . time(),
    'token'      => $_SESSION[config('auth.LOGIN')],
    'token_type' => config('auth.LOGIN')
  ]);

  $auth_user = [];

  if(!empty($token)) {
    $auth_user = db()->select('users', '*', [
      'id' => $token[0]['user_id'] 
      ]);

    db()->update('tokens',
      [
        'expire_at'  => time() + config('auth.idle')
      ],
      [
        'token'   => $token[0]['token'],
        'user_id' => $token[0]['user_id']
      ]);
  }

  if(!empty($auth_user)) {
    $auth_user = $auth_user[0];
    //$auth_user['login_at'] =  date(config("app.date"), $auth_user['login_at']);
  }
  else unset($_SESSION[config('auth.LOGIN')]);
}

if (!is_except()
&& !isset($_SESSION[config('auth.LOGIN')])) abort('login', null, 401);//redirect('login', lang('err.not_loggedin'));
// AUTH