<?php

function setting($key=null)
{
  global $setting;
  return is_null($setting) ? $setting : $setting[$key];
}