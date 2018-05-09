<?php
require_once '../php/bibli_generale.php';

session_unset();
session_destroy();	

$cookieParams = session_get_cookie_params();
setcookie(session_name(), 
		'', 
		time() - 86400,
     	$cookieParams['path'], 
     	$cookieParams['domain'],
     	$cookieParams['secure'],
     	$cookieParams['httponly']
	);

td_redirection("../index.php");

?>