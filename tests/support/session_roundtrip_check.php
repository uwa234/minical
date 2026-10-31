<?php
// Manual check: php tests/support/session_roundtrip_check.php
define('BASEPATH', __DIR__ . '/../../public/system/');
define('APPPATH', __DIR__ . '/../../public/application/');

function log_message($level, $msg) {}
function &get_instance() { global $CI; return $CI; }
function config_item($k) { global $CI; return $CI->config->item($k); }

require BASEPATH . 'helpers/string_helper.php';

class StubConfig { public $items; function item($k) { return isset($this->items[$k]) ? $this->items[$k] : FALSE; } }
class StubInput {
    function cookie($k) { return isset($_COOKIE[$k]) ? $_COOKIE[$k] : FALSE; }
    function ip_address() { return '127.0.0.1'; }
    function user_agent() { return 'cli'; }
    function is_ajax_request() { return FALSE; }
}
class StubLoad { function helper($h) {} function library($l) {} function database() {} }

$CI = new stdClass();
$CI->config = new StubConfig();
$CI->config->items = array(
    'sess_encrypt_cookie' => TRUE, 'sess_use_database' => FALSE, 'sess_table_name' => 'sessions',
    'sess_expiration' => 43200, 'sess_expire_on_close' => FALSE, 'sess_match_ip' => FALSE,
    'sess_match_useragent' => FALSE, 'sess_cookie_name' => 'ci_session', 'cookie_path' => '/',
    'cookie_domain' => '', 'cookie_secure' => FALSE, 'sess_time_to_update' => 3600,
    'time_reference' => 'local', 'cookie_prefix' => '', 'encryption_key' => 'test-key-0123456789abcdef',
);
$CI->input = new StubInput();
$CI->load = new StubLoad();

require APPPATH . 'libraries/Encrypt.php';
$CI->encrypt = new CI_Encrypt();
require BASEPATH . 'libraries/Session.php';
require APPPATH . 'libraries/MY_Session.php';

function make_cookie($s, $userdata) {
    $data = $s->CI->encrypt->encode($s->_serialize($userdata));
    return $data . $s->_cookie_signature($data);
}

$ok = true;
$_COOKIE = array();
$s1 = @new MY_Session();
$sid = $s1->userdata['session_id'];
$ok &= strlen($sid) === 32 && ctype_xdigit($sid);
echo 'new session id is 32 hex chars: ' . (strlen($sid) === 32 && ctype_xdigit($sid) ? 'PASS' : 'FAIL') . "\n";

$_COOKIE['ci_session'] = make_cookie($s1, $s1->userdata + array('user_id' => 42));
$s2 = @new MY_Session();
$pass = $s2->userdata['session_id'] === $sid && $s2->userdata['user_id'] === 42;
$ok &= $pass;
echo 'valid signed cookie is read back: ' . ($pass ? 'PASS' : 'FAIL') . "\n";

$tampered = $_COOKIE['ci_session'];
$tampered[5] = $tampered[5] === 'A' ? 'B' : 'A';
$_COOKIE['ci_session'] = $tampered;
$s3 = @new MY_Session();
$pass = $s3->userdata['session_id'] !== $sid;
$ok &= $pass;
echo 'tampered cookie is rejected: ' . ($pass ? 'PASS' : 'FAIL') . "\n";

$_COOKIE['ci_session'] = $s1->CI->encrypt->encode($s1->_serialize($s1->userdata));
$s4 = @new MY_Session();
$pass = $s4->userdata['session_id'] !== $sid;
$ok &= $pass;
echo 'old unsigned cookie starts a fresh session: ' . ($pass ? 'PASS' : 'FAIL') . "\n";

exit($ok ? 0 : 1);
