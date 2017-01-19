<?php
header("Content-type: text/html; charset=utf-8");
header('X-FRAME-OPTIONS: SAMEORIGIN');//clickjacking

$place = "mysql:host=localhost;dbname=kimoto_4;charset=utf8";
$user = "kimoto";
$pswd = "waWeYG6fppfk";
$pdo = new PDO($place, $user, $pswd, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));

function h($str) {
return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

function rid_space($values) {
	$values = preg_replace('/^[ 　]+/u', '', $values);//first
	$values = preg_replace('/[ 　]+$/u', '', $values);//end
	return $values;
}
?>


