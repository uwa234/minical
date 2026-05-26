<?php if (!defined('BASEPATH')) exit('No direct script access allowed');
/*
| -------------------------------------------------------------------------
| Email
| -------------------------------------------------------------------------
| This file lets you define parameters for sending emails.
| Please see the user guide for info:
|
|	http://codeigniter.com/user_guide/libraries/email.html
|
*/
$config['mailtype'] = 'html';
$config['charset'] = 'utf-8';
$config['newline'] = "\r\n";
$config['protocol'] = 'smtp';
$config['smtp_host'] = function_exists('minical_env') ? minical_env('SMTP_HOST', 'smtp.sendgrid.com') : (getenv('SMTP_HOST') ?: 'smtp.sendgrid.com');
$config['smtp_port'] = function_exists('minical_env') ? minical_env('SMTP_PORT', '587') : (getenv('SMTP_PORT') ?: '587');
$config['smtp_timeout'] = 30;
$config['smtp_crypto'] = function_exists('minical_env') ? minical_env('SMTP_CRYPTO', '') : (getenv('SMTP_CRYPTO') ?: '');
$config['smtp_user'] = function_exists('minical_env') ? minical_env('SMTP_USER', '') : getenv('SMTP_USER');
$config['smtp_pass'] = function_exists('minical_env') ? minical_env('SMTP_PASS', '') : getenv('SMTP_PASS');

/* End of file email.php */
/* Location: ./application/config/email.php */