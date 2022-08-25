<?php

use app\constants\User;

except([
  'login', // نموذج تسجيل الدخول
  'dologin', // تسجيل الدخول post
  'forgotpass',
  'doforgotpass',
]);


function script_index()
{
  return display('home');
}

function script_logout()
{
  db()->deleteFrom('tokens', [
    'token_type' => config('auth.LOGIN'),
    'token' => $_SESSION[config('auth.LOGIN')]
  ]);

  unset($_SESSION[config('auth.LOGIN')]);

  return redirect('/');
}


function script_login()
{
  if (auth()) return redirect('/');
  return display('login');
}

/**
 * @Method post
 * @Name login
 */
function script_dologin()
{
  if (auth()) return redirect('/');
  
  validate([
    'email'     => 'required|min:10',
    'password'  => 'required|min:6',
  ]);

  $user = db()->select('users', 'id, email, password', [
    'email' => data('email'),
    '`status` > ' . User::DISABLED
  ]);

  if (empty($user)) return goBack(lang('err.check_login'));

  $user = $user[0];
  // check password
  if (!password_verify(data('password'), $user['password'])) return goBack(lang('err.check_login'));

  // TOKEN
  $token = md5(implode('.', $user));
  db()->replace('tokens', [
    'user_id'    => $user['id'],
    'token'      => $token,
    'created_at' => time(),
    'expire_at'  => time() + config('auth.idle'),
    'token_type' => config('auth.LOGIN')
  ]);
  // TOKEN

  db()->update('users', [ 'login_at' => date('Y-m-d') ], ['id' => $user['id']]);

  $_SESSION[config('auth.LOGIN')] = $token;

  return redirect('/');
}

function script_forgotpass()
{
  return display('forgot_pass');
}

/**
 * @Name forgotpass
 * @Method post
 */
function script_doforgotpass()
{
  validate([
    'email' => 'required|email|min:10'
  ]);

  // check exists
  $checking = db()->select('users', 'id,email, name', [
    'email'  => data('email'),
    '`status` > ' . User::DISABLED
  ]);

  if (!empty($checking)) {
    // TOKEN
    $token = md5(implode('.', $checking[0]));
    db()->replace('tokens', [
      'user_id'    => $checking[0]['id'],
      'token'      => $token,
      'created_at' => time(),
      'expire_at'  => time() + config('auth.token_life'),
      'token_type' => config('auth.PASS_RESET'),
      ]);
      // TOKEN
      
    $link = domain() . "/confirm/" . $token;

    mailto(data('email'), $checking[0]['name'], lang('pass_reset'), display('mail.pass_reset', ["user" => $checking[0], "link" => $link]));
  }

  return redirect('/login', lang('msg.pass_reset_sent'));
}