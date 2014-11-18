<?php
	define('SITE_PATH',getcwd());
	define('THINK_PATH','./ThinkPHP/');
	define('APP_PATH','./index/');
	define('APP_NAME','index');
	define('APP_DEBUG',true);//正式服务器要关闭debug，否则不能生成验证码
        
	require	THINK_PATH.'ThinkPHP.php';

?>