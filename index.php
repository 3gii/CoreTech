<?php
# ================================================================
# 程序入口文件
# @core     3gii
# @author   张国鹏
# @update   2016.9.29*
# @notice   您只能在不用于商业目的的前提下对程序代码进行修改和使用
# ================================================================
define('DROP', TRUE);
if (!defined('ROOT')) {
	define('ROOT', dirname(__FILE__) . DIRECTORY_SEPARATOR);
}
if (!defined('SYSTEM_DIR')) {
	define('SYSTEM_DIR', ROOT . 'system' . DIRECTORY_SEPARATOR);	
}
if (!defined('APPS_DIR')) {
	define('APPS_DIR', ROOT . 'apps' . DIRECTORY_SEPARATOR);	
}
date_default_timezone_set('PRC');
require_once SYSTEM_DIR.'core.php';

header('Content-Type: text/html; charset=utf-8');
date_default_timezone_set('PRC');
session_start();
Core::APP('Tech');//框架

?>