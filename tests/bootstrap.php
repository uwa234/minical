<?php

define('BASEPATH', dirname(__DIR__) . '/public/system/');
define('APPPATH', dirname(__DIR__) . '/public/application/');

if (!class_exists('CI_Model', false)) {
    class CI_Model
    {
    }
}

require_once APPPATH . 'helpers/module_helper.php';
require_once APPPATH . 'config/constants.php';
