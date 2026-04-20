<?php

declare(strict_types=1);

namespace WebScheduler\Enum;

enum CapabilityEnum: string
{
    case FASTCGI_FINISH_REQUEST = 'fastcgi_finish_request';
    case PROC_OPEN = 'proc_open';
    case PHP_CLI_BINARY = 'php_cli_binary';
    case SET_TIME_LIMIT = 'set_time_limit';
    case IGNORE_USER_ABORT = 'ignore_user_abort';
}
