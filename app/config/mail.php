<?php

return [

  'from'  => [
    'email' => env('MAIL_FROM', 'noreplay@' . $_SERVER['HTTP_HOST']),
    'name'  => 'no-reply'
  ],

  'mail'  => env('MAIL_TYPE', 'smtp'),

  'smtp'  => [
    'type'      => 'SMTP',
    'host'      => env('SMTP_HOST', 'smtp.gmail.com'),
    'username'  => env('SMTP_USERNAME', ''),
    'password'  => env('SMTP_PASSWORD', ''),
    'port'      => env('SMTP_PORT', 587),
    'tls'       => env('SMTP_TLS', false),
    'auth'      => env('SMTP_AUTH', true),
  ]
];
