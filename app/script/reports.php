<?php

use app\constants\User;

function script_index($id=0, $action=null)
{
  // IF ROOT
  $checkuser =  [];
  // ELSE
  switch (auth('status')) {

    case User::USER:
      $checkuser = ['user_id' => (int) auth('id')];
      break;

    case User::MANAGER:
    case User::GMANAGER:
      $checkuser = ['hotel_id' => (int) auth('hotel_id')];
      break;

  }

  if($id > 0) {
    $msg = null;

    switch ($action) {
      case 'del':
        db()->update('reports', [ 'deleted_at' => date('Y-m-d') ], array_merge([ 'id' => $id ], $checkuser));
        $msg = lang('msg.report_deleted');
        break;
        
      default:
        $report = db()->select('reports', '*', array_merge([ 'id' => $id, "`deleted_at` IS NULL" ], $checkuser));
        $report = $report[0];
        if($report) {
          $report['options'] =  json_decode($report['options'], true);
          //return $report;
          return display('report', compact('report'));
        } 
        $msg = lang('err.report_notfound');
        break;
    }

    return redirect('/reports', $msg);
  }

  $reports = db()->select('reports', '*', array_merge([ "`deleted_at` IS NULL" ], $checkuser), false, [ 'ORDER BY `created_at` DESC, `id` DESC' ]);
  
  return display('reports', compact('reports'));
}

function script_reportok()
{
  return display('reportok');
}

/**
 * @Name index
 * @Method post
 */
function script_doreport()
{
  validate([
    'hotel_id'  => 'required|min:1|gt:0'
  ]);

  // new hotel
  $hotelId = (int) data('hotel_id');

  // prepare options data
  $optionsData = [];
  foreach (json('categories') as $id => $cat) {

    if($id == 9) {
      $rooms = data('room') ? data('room')[$id] : [];

      foreach ($rooms as $roomid => $room) {
        $optionsData[$id][$roomid] = [
          'title'   => $room,
          'type'    => $cat['type'],
          'tab_id'  => $cat['tab_id'],
          'options' => [],
          'result'  => [
            'points' => 0,
            'percent' => 0
          ]
        ];

        foreach (json('subcategories') as $subid => $subcat) {
          if ($subcat['cat_id'] != $id) continue;
          $optionsData[$id][$roomid]['options'][$subid] = [
            'title' => $subcat['title'],
            'image' => data('image')[$id][$subid][$roomid],
            'result' => (int) data('options')[$id][$subid][$roomid]
          ];
        }
      }


      continue;
    }

    $optionsData[$id] = [
      'title'   => $cat['title'],
      'type'    => $cat['type'],
      'tab_id'  => $cat['tab_id'],
      'options' => [],
      'result'  => [
        'points' => 0,
        'percent'=> 0
      ]
    ];
    foreach (json('subcategories') as $subid => $subcat) {
      if($subcat['cat_id'] != $id) continue;
      $optionsData[$id]['options'][$subid] = [
        'title' => $subcat['title'],
        'image' => data('image')[$id][$subid],
        'result'=> (int) data('options')[$id][$subid]
      ];
      if($cat['type'] == 'RATE') $optionsData[$id]['result']['points'] += (int) data('options')[$id][$subid] > 0 ? (int) data('options')[$id][$subid] : 0;
    }
    if($cat['type'] == 'RATE') $optionsData[$id]['result']['percent'] = floor($optionsData[$id]['result']['points'] / count($optionsData[$id]['options']));
  }
  

  $hotel = db()->select('hotels', 'email, name', [ 'id' => $hotelId ]);
  $hotel = $hotel[0];
  $users = db()->select('users', 'email', [ 'hotel_id' => $hotelId, '`status` > ' . User::ROOT  ]);

  $emails = array_map(function ($u){ return $u['email']; }, $users);
  

  // save report
  db()->insertInto('reports', [
    'user_id'    => auth('id'),
    'username'   => auth('name'),
    'created_at' => date('Y-m-d', time()),
    'hotel_id'   => $hotelId,
    'hotel'      => $hotel['name'],
    'address'    => $hotel['address'],
    'branch'     => $hotel['branch'],
    'options'    => json_encode($optionsData, JSON_NUMERIC_CHECK | JSON_UNESCAPED_UNICODE),
  ]);

  mailto(array_merge($emails, $hotel), $hotel['name'], lang('new_report') . '-' . $hotel['name'], tpl('mail.newreport', ["name" => $hotel['name']]));

  return redirect('/reports/reportok');
}


/**
 * @Method post
 */
function script_img()
{
  // images
  $image  = null;
  $file   = files('image');
  if ($file) {
    $image = $file->upload(null, md5($file->info('name') . time()) . '.' . $file->info('ext'));
  }
  // images

  if(!$image) return print_json([ "url" => null ], lang('err.cantupload'), 400);
  //$image = domain() . $image;

  return [
    "url"=> $image
  ];
}


/**
 * @Method post
 */
function script_alert()
{

  validate([
    'hotel_id'  => 'required|min:1|gt:0',
    'message'   => 'required|min:10'
  ]);

  $hotel = db()->select('hotels', 'name,email', [ 'id' => data('hotel_id') ]);
  $hotel = $hotel[0];

  mailto($hotel['email'], $hotel['name'], lang('new_alert'), tpl('mail.alert', ["message" => data('message'), "name" => $hotel['name']]));

  return print_json([], lang('msg.alert_sent'));
}
