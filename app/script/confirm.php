<?php

except('*');


function script_index($code=null)
{
  if(!$code) redirect('/');
  
  $token = db()->select('tokens', 'user_id,token_type', [ 'token' => $code ]);
  if(!$token) redirect('/');

  $message = null;
  $token   = $token[0];

  switch ($token['token_type']) {
      
    case config('auth.PASS_RESET'):
      return display('new_pass', [ 'token' => $code ]);
      break;

  }

  db()->deleteFrom('tokens', ['token' => $code, 'user_id' => $token['user_id']]);

  return redirect('/', $message);
}

