<?php //-->
ini_set('display_errors', 1);
error_reporting(E_ALL);

if($_SERVER['REQUEST_URI'] == '/assets' 
	|| strpos($_SERVER['REQUEST_URI'], '/assets/') === 0
	|| strpos($_SERVER['REQUEST_URI'], '/assets?') === 0) {
	require('assets.php');
} else if($_SERVER['REQUEST_URI'] == '/upload' 
	|| strpos($_SERVER['REQUEST_URI'], '/uploads/') === 0
	|| strpos($_SERVER['REQUEST_URI'], '/uploads?') === 0) {
	require('uploads.php');
} else if($_SERVER['HTTP_HOST'] === 'local.control.jldmp.ph'
	|| $_SERVER['HTTP_HOST'] === 'control.jldmp.mykhel15.kd.io') {
	include('control.php');
} else { 
	require('front.php'); 
}