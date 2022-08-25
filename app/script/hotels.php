<?php

use app\constants\User;

function script_index()
{
  auth([User::ROOT, User::USER]);

  $name = query('name');
  $ids  = [];
  $search = [];
  $branches = [];

  if($name) $search = [ "`name` like '%$name%'" ];

  $hotels = db()->select('hotels', 'id,name,address,branch', $search);
  foreach ($hotels as $k => $v) $ids[] = $v['id'];
  //if(count($hotels) > 0) $branches = $name ? db()->select('branches', '*', [ "`id` IN (" . implode(',', $ids) . ")" ]) : [];

  // foreach ($hotels as $k => $v) {
  //   $hotels[$k]['branch'] = '';
  //   foreach ($branches as $kr => $vr) {
  //     if($vr['id'] != $v['branch_id']) continue;
  //     $hotels[$k]['branch'] = $vr['name'];
  //   }
  // }

  return display('hotels.list', compact('hotels'));
}


function script_add()
{
  auth([User::ROOT]);
  return display('hotels.form');
}

function script_edit($id=0)
{
  auth([User::ROOT]);
  
  $id = intval($id);
  if($id <= 0) redirect('/hotels', lang('hotel_notfound'));

  $hotel = db()->select('hotels', '*', [ 'id' => $id ]);
  $hotel = $hotel[0];
  
  if(!$hotel) redirect('/hotels', lang('hotel_notfound'));

  return display('hotels.form', compact('hotel'));
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
    'address'   => 'required|min:3',
    'branch'    => 'required|min:3',
    'email'     => 'required|email'
  ]);

  $res = db()->insertInto('hotels', [
    'name'      => data('name'),
    'address'   => data('address'),
    'branch'    => data('branch'),
    'email'     => data('email')
    
  ]);

  return redirect('/hotels', lang('msg.hotel_added'));
}

/**
 * @Method put
 * @Name edit
 */
function script_doEdit($id=0)
{
  auth([User::ROOT]);
  
  $id = intval($id);
  if($id <= 0) redirect('/hotels', lang('hotel_notfound'));
  
  validate([
    'name'      => 'required|min:3',
    'address'   => 'required|min:3',
    'branch'    => 'required|min:3',
    'email'     => 'required|email'
  ]);

  $res = db()->update('hotels', [
    'name'      => data('name'),
    'address'   => data('address'),
    'branch'    => data('branch'),
    'email'     => data('email')
  ], [ 'id' => $id ]);

  return redirect('/hotels', lang('msg.hotel_updated'));
}


function script_del($id=0)
{
  auth([User::ROOT]);
  
  $id = intval($id);
  if($id <= 0) redirect('/hotels', lang('hotel_notfound'));

  $res = db()->deleteFrom('hotels', [ 'id' => $id ]);

  return redirect('/hotels', lang('msg.hotel_deleted'));
}
