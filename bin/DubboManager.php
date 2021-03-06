<?php
/*
  +----------------------------------------------------------------------+
  | dubbo-php-framework                                                        |
  +----------------------------------------------------------------------+
  | This source file is subject to version 2.0 of the Apache license,    |
  | that is bundled with this package in the file LICENSE, and is        |
  | available through the world-wide-web at the following url:           |
  | http://www.apache.org/licenses/LICENSE-2.0.html                      |
  +----------------------------------------------------------------------+
  | Author: Jinxi Wang  <crazyxman01@gmail.com>                              |
  +----------------------------------------------------------------------+
*/


define("VENDOR_DIR", __DIR__ . '/../../../');

include VENDOR_DIR . "/autoload.php";

use Dubbo\Provider\Startup;
use Dubbo\Provider\Initialization;

class DubboManager
{
    public function checkEnvironment()
    {
        if (php_sapi_name() != 'cli') {
            exit("Must be run in php cli mode! \n");
        }
        $req_extension = '';
        if (!extension_loaded('swoole')) {
            $req_extension .= 'swoole ';
        }
        if (!extension_loaded('yaml')) {
            $req_extension .= ' yaml';
        }
        if ($req_extension) {
            exit("Need {$req_extension} extension! \n");
        }
    }

    public function getOpt()
    {
        if (false) {
            help:
            $help = <<<HELP
Usage:
    php DubboManager.php [-h] [-y filename] [-y filename -s signal]
Options:
    -y filename            : This is a provider config file
    -s signal              : send signal to a master process: stop, reload 
    -h                     : Display this help message \n
HELP;
            exit($help);
        }
        $options = getopt("y:s:h");
        if (isset($options['h'])) {
            goto help;
        }
        $y = $options['y'] ?? '';
        if (!is_file($y)) {
            goto help;
        }
        try {
            $initialization = new Initialization($y);
        } catch (\Exception $exception) {
            exit($exception->getMessage());
        }
        $s = $options['s'] ?? '';
        if ($s) {
            if (!$y) {
                goto help;
            }
            if ($s != "stop" && $s != 'reload') {
                goto help;
            }
            $pidFile = $initialization->getApplicationPidFile();
            $fp = fopen($pidFile, 'cb');
            if (flock($fp, LOCK_EX | LOCK_NB)) {
                exit("'{$initialization->getApplicationName()}' this application dose not start\n");
            }
            $pid = $initialization->getPid();
            if (!$pid) {
                exit("No pid found in '{$pidFile}'");
            }
            if ($s == "stop") {
                posix_kill($pid, SIGTERM);
                return;
            } elseif ($s == 'reload') {
                posix_kill($pid, SIGUSR1);
                return;
            }
        }
        try {
            $initialization->startServer();
        } catch (\Exception $exception) {
            exit($exception->getMessage()."\n");
        }

    }

    public static function run()
    {
        $instance = new self();
        $instance->checkEnvironment();
        $instance->getOpt();
    }
}

DubboManager::run();
