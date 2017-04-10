<?php
require_once './auth/OAuthLoginClass.php';

$type = $_GET['type'];
$ret = OAuthLoginFacade::authLogin($type);
print_r($ret);
