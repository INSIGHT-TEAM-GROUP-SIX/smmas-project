<?php
// cofig_online.php
// Auto-detect if we are on infinityfree
$is_online = ($_SERVER['SERVER_NAME'] != 'localhost' && $_SERVER['SERVER_NAME'] != '127.0.0.1');

if($is_online){

define('DB_HOST', 'sql201.infinityfree.com');
define('DB_NAME', 'if0_41961194_smmas_db1');
define('DB_USER', 'if0_41961194');
define('DB_PASS', 'G990KtgAJ1R4D0');
}
?>