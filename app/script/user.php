<?php

use app\constants\User;

except([
  'doresetpass'
]);


function script_index($show=null)
{
  auth([User::ROOT]);

  $type = [];
  switch ($show) {
    case User::T_MANAGMENT:
      $type = [ 'status' => User::USER ];
      break;
    case User::T_GENERAL_MANAGERS:
      $type = [ 'status' => User::GMANAGER ];
      break;
    case User::T_MANAGERS:
      $type = [ 'status' => User::MANAGER ];
      break;
  }

  $users = db()->select('users', '*,login_at,created_at,status',
    array_merge(['`id` != ' . auth('id'), '`id` > 1'], $type),
    false, [ 'ORDER BY `id` DESC' ]);

  return display('users.list', compact('users'));
}


function script_changepass()
{
  return display('changepass');
}


/**
 * @Name changepass
 * @Method put
 */
function script_dochangepass()
{
  validate([
    'oldpassword'=> 'required|min:6|match_auth_password',
    'password'   => 'required|min:6',
    'vpassword'  => 'required|match:password',
  ]);

  db()->update('users', ['password' => password_hash(data('password'), PASSWORD_DEFAULT)], ['id' => auth('id')]);

  return redirect('/user/changepass', lang('msg.password_changed'));
}


/**
 * @Name resetpass
 * @Method put
 */
function script_doresetpass()
{
  validate([
    '_token'     => 'required|min:32',
    'password'   => 'required|min:6',
    'vpassword'  => 'required|match:password',
  ]);

  $token = db()->select('tokens', 'user_id', ['token' => data('_token'), 'token_type' => config('auth.PASS_RESET')]);
  if (!$token) redirect('/');

  $token = $token[0];

  db()->update('users', ['password' => password_hash(data('password'), PASSWORD_DEFAULT)], ['id' => $token['user_id'], '`status` > 0']);

  db()->deleteFrom('tokens', [ 'token_type' => config('auth.PASS_RESET'), 'user_id' => $token['user_id'] ]);

  return redirect('/login', lang('msg.password_changed'));
}


function script_edit($id=0, $show=null)
{
  auth([User::ROOT]);
  
  $id = intval($id);
  if($id <= 0) redirect('/user', lang('user_notfound'));

  $user = db()->select('users', '*', [ 'id' => $id ]);
  
  if(!$user) redirect('/user', lang('user_notfound'));

  $hotels = db()->select('hotels', 'id,name');
  $user = $user[0];
  return display('users.form', compact('user', 'hotels'));
}

function script_add($show=null)
{
  auth([User::ROOT]);

  $hotels = db()->select('hotels', 'id,name');
  return display('users.form', compact('hotels'));
}

/**
 * @Method post
 * @Name add
 */
function script_doAdd()
{
  auth([User::ROOT]);
  
  validate([
    'name'      => 'required|min:3',
    'email'     => 'required|email|min:10',
    'password'  => 'required|min:6',
    'vpassword' => 'required|match:password',
  ]);

  // check exists
  $checking = db()->select('users', 'id,email', [ 'email'  => data('email') ]);

  if (!empty($checking)) {
    $checking = $checking[0];
    if ($checking['email'] == data('email')) add_error(lang('err.item_exists'), 'email');
    return goBack();
  }

  $hotel_id = intval(data('hotel_id'));
  $status   = User::USER;

  switch (data('user_type')) {
    case User::T_MANAGERS:
      $status = User::MANAGER;
      break;
    case User::T_GENERAL_MANAGERS:
      $status = User::GMANAGER;
      break;
  }

  if(in_array($status, [ User::GMANAGER, User::MANAGER ])) return goBack(lang('err.please_choosehotel'));

  $res = db()->insertInto('users', [
    'name'      => data('name'),
    'email'     => data('email'),
    'password'  => password_hash(data('password'), PASSWORD_DEFAULT),
    'created_at'=> date('Y-m-d'),
    'hotel_id'  => $hotel_id,
    'status'    => $status
  ]);

  return redirect('/user', lang('msg.user_added'));
}


/**
 * @Method put
 * @Name edit
 */
function script_doEdit($id)
{
  auth([User::ROOT]);
  
  $id = intval($id);
  if($id <= 0) redirect('/user', lang('user_notfound'));

  validate([
    'name'      => 'required|min:3',
    'email'     => 'required|email|min:10',
    'password'  => 'min:6',
    'vpassword' => 'match:password',
  ]);

  // check exists
  $checking = db()->select('users', 'id,email', [ 'email'  => data('email'), '`id` != ' . $id ]);
  
  if (!empty($checking)) {
    $checking = $checking[0];
    if ($checking['email'] == data('email')) add_error(lang('err.item_exists'), 'email');
    return goBack();
  }

  $hotel_id = intval(data('hotel_id'));

  $data2update = [
    'name'      => data('name'),
    'email'     => data('email'),
    'hotel_id'  => $hotel_id,
  ];

  //if 2 change password
  if(data('password')) $data2update['password'] = password_hash(data('password'), PASSWORD_DEFAULT);

  $res = db()->update('users', $data2update, [ 'id' => $id ]);

  return redirect('/user', lang('msg.user_updated'));
}


function script_lock($id)
{
  auth([User::ROOT]);
  if (auth('id') == $id)   return redirect('/'); // CAN'T LOCK YOURSELF
  
  $id = intval($id);
  if($id <= 0) redirect('/user', lang('user_notfound'));

  $res = db()->update('users', [ 'status' => 0 ], [ 'id' => $id ]);
  db()->deleteFrom('tokens', ['user_id' => $id]); // logout the user
  
  return redirect('/user', lang('msg.user_updated'));
}


function script_unlock($id)
{
  auth([User::ROOT]);
  
  $id = intval($id);
  if($id <= 0) redirect('/user', lang('user_notfound'));
  $res = db()->update('users', [ 'status' => 1 ], [ 'id' => $id ]);

  return redirect('/user', lang('msg.user_updated'));
}
