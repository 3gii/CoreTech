<?php
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
require_once SYSTEM_DIR.'core.php';
require_once SYSTEM_DIR.'function.php';
header('Content-Type: text/html; charset=utf-8');
if (is_file("install.lock")){
alert('已安装!如需重装请删除【install.lock】',"");
}
class install extends Core{


	function __construct(){

		if($_POST){

			$safe_key		=trim($_POST['safe_key']);
			$admin_name		=trim($_POST['admin_name']);
			$admin_email	=trim($_POST['admin_email']);
			$admin_password=compile_password(trim($_POST['admin_password']),$safe_key);
			$db_host		=trim($_POST['DB_HOST']);
			$db_user		=trim($_POST['DB_USER']);
			$db_pass	=trim($_POST['DB_PASSWORD']);
			$db_name		=trim($_POST['DB_NAME']);
			$path		=trim($_POST['PATH']);
			if(empty($path)){
				$path='/';
			}
			$this->config_db($db_host,$db_user,$db_pass,$db_name);
			#数据库创建表
			$query=array();
		
			$query[]="CREATE TABLE IF NOT EXISTS `apps` (
			  `app_name` varchar(10) NOT NULL,
			  `app_key` text NOT NULL
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;";


			$query[]="INSERT INTO `apps` VALUES ('apps','Core'), 
			('Core','YTo4OntzOjg6InNpdGVuYW1lIjtzOjg6IkNvcmVUZWNoIjtzOjExOiJzaXRlc3VibmFtZSI7czo4OiJDb3JlVGVjaCI7czoxMjoic2l0ZWtleXdvcmRzIjtzOjg6IkNvcmVUZWNoIjtzOjE1OiJzaXRlZGVzY3JpcHRpb24iO3M6ODoiQ29yZVRlY2giO3M6ODoic3RhdGNvZGUiO3M6MDoiIjtzOjY6Im5vdGljZSI7czowOiIiO3M6MjoiYWQiO3M6MDoiIjtzOjM6ImljcCI7czowOiIiO30=');";
			
			#执行命令
			if(count($query)>0){
				foreach($query as $sql){
					$this->query($sql);
				}
			}
			$CONFIG="<?php\n";
			$CONFIG.="define('PATH','$path');\n\n";
			$CONFIG.="define('DB_HOST','$db_host');\n\n";
			$CONFIG.="define('DB_USER','$db_user');\n\n";
			$CONFIG.="define('DB_PASS','$db_pass');\n\n";
			$CONFIG.="define('DB_NAME','$db_name');\n\n";
			$CONFIG.="define('KEY','$safe_key');\n\n";
			file_put_contents(ROOT.'system/config.php',$CONFIG) or die("请检查文件system/config.php的权限是否为0777!");
			file_put_contents('install.lock', time());
			alert('恭喜！安装完毕，为安全起见，建议您删除【install.php】',$path);
		}
	}
}
new install();
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8" />
<title>Install</title>
<style type="text/css">
*{padding:0;margin:0;}
html,body{font:normal 12px 'Microsoft Yahei';color:#666;}
.install{width: 300px;margin:20px auto;}
#info{background:#3385ff;overflow:hidden;height:30px;color: #fff;text-align: center;line-height: 30px;}
p{height: 30px;line-height: 30px;font-weight: bold;}
.input{width:278px;border:1px solid #ccc;background:#fff;padding:10px;font:normal 12px 'Microsoft Yahei';outline:0;color:#222}
.input:focus{border:1px solid #555;}
.submit{border-radius:2px;text-align:center;border:none;padding:9px 15px;background:#3385ff;cursor:pointer;font:bold 12px 'Microsoft Yahei';color:#fff;margin-top: 10px;}
</style>
 </head>
 <body>
 <div id="info">环境：<?php echo $_SERVER['SERVER_SOFTWARE'];?>
</div>
 <div class="install">
	 <form method="post">
	 <p>数据库主机</p>
	 <input type="text" name="DB_HOST" size="30" class="input" value="localhost" />
	 <p>数据库用户</p>
	 <input type="text" name="DB_USER" size="30"  class="input" value="root" />
	 <p>数据库密码</p>
	 <input type="text" name="DB_PASSWORD" size="30"  class="input"  value=""/>
	 <p>数据库名</p>
	 <input type="text" name="DB_NAME" size="30"  class="input" value="test" />
	 <p>管理员昵称</p>
	 <input type="text" name="admin_name" size="30" class="input" value="admin" />
	 <p>管理员邮箱</p>
	 <input type="text" name="admin_email" size="30" class="input" value="admin@admin.com" />
	 <p>管理员密码</p>
	 <input type="text" name="admin_password" size="30" class="input" value="111111" />
	 <p>安全密匙KEY</p>
	 <input type="text" name="safe_key" size="30" class="input" value="<?php echo getRandomKey(15)?>" />
	 <div onclick="if(confirm('确定要安装吗?')){document.forms[0].submit()}" class="submit">开始安装</div>
	 <input type="hidden" name="PATH" size="30"  class="input" value="<?php echo str_replace('install.php','',$_SERVER['SCRIPT_NAME'])?>"/>
	 </form>
 </div>
 </body>
 </html>