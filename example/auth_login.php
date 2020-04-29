<?php
require_once '../init.php';

$type = $_GET['type'];
$ret = ContentAuth\OAuthLoginClass\OAuthLoginClass::authLogin($type);
print_r($ret);
