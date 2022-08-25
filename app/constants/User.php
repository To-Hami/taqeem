<?php

namespace app\constants;

class User {

    // user status
    const DISABLED  = 0,
          USER      = 1,
          ROOT      = 2,
          GMANAGER  = 3,
          MANAGER   = 4;

    const T_MANAGMENT        = 'managment',
          T_GENERAL_MANAGERS = 'generalmanagers',
          T_MANAGERS         = 'managers';

}