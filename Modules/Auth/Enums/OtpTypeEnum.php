<?php

namespace Modules\Auth\Enums;

enum OtpTypeEnum: string
{
    case LOGIN    = 'login';
    case REGISTER = 'register';
    case RESET    = 'reset';
}