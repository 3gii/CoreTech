<?php
# ================================================================
# 程序核心文件，包含基础功能函数，数据库函数，主题函数及其他API
# @E-mail:3gii@sina.cn
# ================================================================

if(!defined('SYSTEM_DIR'))exit('Access denied!');//判断是否定义系统文件路径
require_once SYSTEM_DIR.'config.php';//包含系统配置文件

abstract class Core{

	private $db_link=null;//数据库

	public $template;

	public $cache;

	public $do;

	public $config;

	public function  __construct(){//构造方法
		$file='install.lock';//定义文件install.lock，防止重装
		if (!is_file($file)){
			header("Location: install.php");
		}
		$this->config_db(DB_HOST,DB_USER,DB_PASS,DB_NAME);//连接数据库
		@set_magic_quotes_runtime(0);//关闭外部引入数据添加反斜杠
		if(@get_magic_quotes_gpc())://判断get_magic_quotes_gpc()是否开启，php6中已不再使用
		    $_GET=$this->remove_slashes($_GET);
		    $_POST=$this->remove_slashes($_POST);
		    $_COOKIE=$this->remove_slashes($_COOKIE);
		endif;
		$this->autoloadClass();//包含system文件中的所有类
		$this->template=new template();//实例化模板类
		$this->cache=new dbCache();//实例化缓存类
		$this->do=isset($_REQUEST['do'])?trim($_REQUEST['do']):'';//获取提交的数据
		$this->config=$this->appConfig();//获取所有apps，数组
		$this->template->in('path',PATH);//设置根目录变量
		$this->template->in('sitename',$this->config['sitename']);//获取网站名并输出
		$this->template->in('keywords',$this->config['sitekeywords']);//获取网站关键字并输出
		$this->template->in('description',$this->config['sitedescription']);//获取网站描述并输出
		$this->template->in('config',$this->config);//设置所有apps变量，数组
		$this->template->in('isMobile',$this->isMobile());//设置isMobile判断结果，是为true，否则为false
		if(isset($_COOKIE['token'])){
			$row=$this->row("SELECT user_id,user_name,user_avatar,user_email FROM `user` WHERE `user_id`='".$this->authstr($_COOKIE['token'])."'");
			$_SESSION['user_id']=$row['user_id'];
			$_SESSION['user_name']=$row['user_name'];
			$_SESSION['user_avatar']=$row['user_avatar'];
			$_SESSION['user_email']=$row['user_email'];
		}
		if($this->check_access())://检查后台权限
			$this->template->in('adminmenu',$this->getMenu());
		endif;
		$this->template->in('isAdmin',$this->check_access());
		
		#关闭错误提示
		
	}

	
	public static function APP($app,$action='index'){

        if (isset($_GET['app'])) :
            $app = trim(strtolower($_GET['app']));
        endif;
        if (isset($_GET['action'])) :
            $action = trim($_GET['action']);
        endif;
        $runapp=APPS_DIR.$app.'.app.php';
        is_file($runapp)?require($runapp):exit('no this apps');
        $run = new $app();
        include SYSTEM_DIR.'function.php';
        if(!is_callable(array($app,$action)))http_404();
        echo $run->$action();
	}

 /*    public function URL($app, $action, $param = null) {
        $r = 'index.php?app='.$app.'&action='.$action;
        if ($param == null) :
            return $r;
        endif;
        if (gettype($param == 'string')) :
            return $r . '&' . $param;
        endif;
        if (gettype($param) == 'array') :
            foreach ($param as $k => $v) :
                $r .= '&' . $k . '=' . $v;
            endforeach;
            return $r;
        endif;
        return $r;
    } */
	# 自动加载所有类
	private function autoloadClass(){
		if($handle=opendir("system")):
			while(false!==($dir=readdir($handle))){
				if (strpos($dir,'class')!==false)
					require_once(SYSTEM_DIR.$dir);
			}
			closedir($handle);
		endif;
	}
	# 检查后台权限
	public function check_access(){
		$user_id=@intval($_SESSION['user_id']);
		$user_status=$this->value("user","user_status","user_id='$user_id'");
		if($user_status==3):
			return true;
		else :
			return false;
		endif;
	}
	# 检查用户权限
	public function check_user_access(){
		$user_id=@intval($_SESSION['user_id']);
		$user_status=$this->value("user","user_status","user_id='$user_id'");
		if($user_status==2):
			return true;
		else :
			return false;
		endif;
	}
	protected function appConfig(){
		$array=array();
		$result=$this->result("SELECT app_key FROM apps WHERE app_name!='apps'");
		if($result):
			foreach($result as $row):
				if(!empty($row['app_key'])):
					$array=array_merge($array,unserialize(base64_decode($row['app_key'])));
				endif;
			endforeach;
		endif;
		return $array;
	}
	public function getApps(){
		$array=array();
		if(!$this->cache->read('apps')):
			$value=$this->value("apps","app_key","app_name='apps'");
			if(!empty($value)):
				$array=explode("|",$value);
			else :
				$array=array();
			endif;
			$this->cache->write('apps',$array);	
		else :
			$array=$this->cache->read('apps');
		endif;
		return $array;
	}
	public function getMenu(){
		$apps=$this->getApps();
		$array=array();
		if($apps):
			$no=1;
			foreach($apps as $app) :
				$file=APPS_DIR.$app.'.info.php';
				if(file_exists($file)) :
					$menu=include $file;
					if(@$menu['menu']) :
						$item=array();
						foreach($menu['menu']['item'] as $k=>$v):
								$item[$k]=$v;
						endforeach;
					else:
						$item='';
					endif;
					$hash=md5(@$menu['menu']['name']);
					$array[$hash]['code']=$app;
					$array[$hash]['name']=@$menu['menu']['name'];
					$array[$hash]['icon']=@$menu['icon'];
					$array[$hash]['children']=$item;
					$array[$hash]['no']=$no;
					++$no;
				endif;
			endforeach;
			ksort($array);
		endif;
		return $array;
	}
	public function __destruct(){//析构方法
		if($this->db_link):
			@mysql_close($this->db_link);
		endif;
	}
	protected function config_db($db_host,$db_user,$db_pass,$db_name){
		$this->db_link = @mysql_connect($db_host,$db_user,$db_pass) or die("Can't connect MySQL server($db_host)!");
		mysql_query("SET NAMES 'utf8'");
		mysql_select_db($db_name,$this->db_link);
	}
	#执行SQL
	public function query($sql){
		return mysql_query($sql);
	}
	#查询单行数据，返回数组
	public function row($sql){
		$temp;
		$result=$this->query($sql);
	    if ($result){
			$temp=mysql_fetch_array($result);
			mysql_free_result($result);
		}else{
			$temp=false;
		}
		return $temp;
	}
	#查询数据，返回数组
	public function result($sql){
		$temp=false;
	    $result=$this->query($sql);
	    if($result){
	        $array = array();
	        while ($row = mysql_fetch_assoc($result)){
	            $array[] = $row;
	        }
	        $temp=$array;
			mysql_free_result($result);
	    }
		return $temp;
	}
	#获取指定字段返回数组
	public function value($table,$field,$where=''){
		if(empty($table)||empty($field))return false;
		$result=$this->row("SELECT ".$field." FROM ".$table." WHERE ".$where."");
		return $result[0];
	}
	#获取指定字段返回布尔值
	public function repeat($table,$field,$value){
		$row=$this->row("SELECT $field FROM $table WHERE $field='$value' LIMIT 1");
		return $row?true:false;
	}
	#插入数据
	public function insert($table,$values,$debug=false){
		$ks='';
		$vs='';
		foreach($values as $key => $value){
			$ks.=$ks?",`$key`":"`$key`";
			$vs.=$vs?",'$value'":"'$value'";
		}
		$sql="insert into `$table` ($ks) values ($vs)";
		if($debug)return $sql;
		return $this->query($sql);
	}
	#更新
	public function update($table,$values,$condition='',$debug=false){
		$v='';
		if(is_string($values)){
			$v.=$values;
		}else{
			foreach($values as $key => $value){
				$v.=$v?",`$key`='$value'":"`$key`='$value'";
			}
		}
		$sql="update `$table` set $v  where $condition";
		if($debug)return $sql;
		return $this->query($sql);
	}
	# 删除数据
	public function del($table,$condition='',$debug=false){
		if(empty($condition)||$condition==''){
			$sql="delete from $table";
		}else{
			$sql="delete from $table where $condition";
		}
		if($debug)return $sql;
		return $this->query($sql);
	}
	#获取新插入ID
	public function id(){
	    return mysql_insert_id($this->db_link);
	}
	public function count($sql,$mode=true){
		if($mode){
			return mysql_num_rows($this->query($sql));
		}else{
			$count=$this->row($sql);
			return intval($count[0]); 
		}
    }
	public function get_self(){
	    return htmlentities(isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME']);
	}
	# 获取虚拟绝对路径
	public function path(){
	    $php_self=$this->get_self();
	    $self=explode('/',$php_self);
	    $self_count=count($self);
	    $url='http://'.$_SERVER['SERVER_NAME'];
	    if($self_count>1) :
	        $url.=str_replace('/'.$self[$self_count-1],'',$php_self);
	    endif;
	    if(substr($url,-1)!='/') :
	        $url.='/';
	    endif;
	    return $url;
	}
	# 公用模板变量 链接列表
	public function links(){
		$links=array();
		$link=$this->result("select * from link order by link_id asc");
		foreach($link as $row){
			$links[$row['link_id']]['id']=$row['link_id'];
			$links[$row['link_id']]['name']=$row['link_name'];
			$links[$row['link_id']]['url']=$row['link_url'];
			$links[$row['link_id']]['text']=$row['link_text'];
		}
		$this->template->in('links',$links);
	}
	# 检查提交
	public function check(){
	    if(empty($_SERVER['HTTP_REFERER'])||(preg_replace("/https?:\/\/([^\:\/]+).*/i","\\1",$_SERVER['HTTP_REFERER'])!=preg_replace("/([^\:]+).*/", "\\1",$_SERVER['HTTP_HOST']))):
	        @header("HTTP/1.0 404 Not Found");
	        exit;
	    endif;
	}
	# 移除反斜杠
	public function remove_slashes($s){
	    if(is_array($s)) :
	        foreach ($s as $k=>$v)$s[$k]=$this->remove_slashes($v);
	    else :
	        $s=stripslashes($s);
	    endif;
	    return $s;
	}
	# 获取类文件中继承文件名
	public function getExtendsClassName($filePath){
		$content = file_get_contents($filePath);
		preg_match("/extends(.*?){/", $content,$matchs);
		unset($content);
		return trim($matchs[1]);
	}
	# 字符串加密解密 ENCODE为加密，DECODE为解密 expiry 过期时间
	public function authstr($string, $operation = 'DECODE', $key = KEY, $expiry = 0) {
		$key_length = 3; # 随机密钥长度 取值 0-32
		$fixedkey = md5($key);
		$egiskeys = md5(substr($fixedkey, 16, 16));
		$runtokey = $key_length ? ($operation == 'DECODE' ? substr($string, 0, $key_length) : substr(md5(microtime(true)), -$key_length)) : '';
		$keys = md5(substr($runtokey, 0, 16) . substr($fixedkey, 0, 16) . substr($runtokey, 16) . substr($fixedkey, 16));
		$string = $operation == 'ENCODE' ? sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$egiskeys), 0, 16) . $string : base64_decode(substr($string, $key_length));
		$i = 0;
		$result = '';
		$string_length = strlen($string);
		for ($i = 0; $i < $string_length; $i++) {
			$result .= chr(ord($string{$i}) ^ ord($keys{$i % 32}));
		}
		if($operation == 'ENCODE') {
			return $runtokey . str_replace('=', '', base64_encode($result));
		} else {
			if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) && substr($result, 10, 16) == substr(md5(substr($result, 26).$egiskeys), 0, 16)) {
				return substr($result, 26);
			} else {
				return '';
			}
		}
	}
	# 防御XSS攻击
	public function xss_clean($var) {
		if (is_array($var)) :
			foreach ($var as $key => $val) :
				if (is_array($val)) :
					$var[$key] = $this->xss_clean($val);
				else :
					$var[$key] = $this->xss_clean_fuc($val);
				endif;
			endforeach;
		elseif (is_string($var)) :
			$var = $this->xss_clean_fuc($var);
		endif;
		return $var;
	}
	public function xss_clean_fuc($data) {
		$data = str_replace(array('&amp;', '&lt;', '&gt;'), array('&amp;amp;', '&amp;lt;', '&amp;gt;'), $data);
		$data = preg_replace('/(&#*\w+)[\x00-\x20]+;/u', '$1;', $data);
		$data = preg_replace('/(&#x*[0-9A-F]+);*/iu', '$1;', $data);
		$data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');
		$data = preg_replace('#(<[^>]+?[\x00-\x20"\'])(?:on|xmlns)[^>]*+>#iu', '$1>', $data);
		$data = preg_replace('#([a-z]*)[\x00-\x20]*=[\x00-\x20]*([`\'"]*)[\x00-\x20]*j[\x00-\x20]*a[\x00-\x20]*v[\x00-\x20]*a[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2nojavascript...', $data);
		$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*v[\x00-\x20]*b[\x00-\x20]*s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:#iu', '$1=$2novbscript...', $data);
		$data = preg_replace('#([a-z]*)[\x00-\x20]*=([\'"]*)[\x00-\x20]*-moz-binding[\x00-\x20]*:#u', '$1=$2nomozbinding...', $data);
		$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?expression[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
		$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?behaviour[\x00-\x20]*\([^>]*+>#i', '$1>', $data);
		$data = preg_replace('#(<[^>]+?)style[\x00-\x20]*=[\x00-\x20]*[`\'"]*.*?s[\x00-\x20]*c[\x00-\x20]*r[\x00-\x20]*i[\x00-\x20]*p[\x00-\x20]*t[\x00-\x20]*:*[^>]*+>#iu', '$1>', $data);
		$data = preg_replace('#</*\w+:\w[^>]*+>#i', '', $data);
		do {
			$old_data = $data;
			$data = preg_replace('#</*(?:applet|b(?:ase|gsound|link)|embed|iframe|frame(?:set)?|i(?:frame|layer)|l(?:ayer|ink)|meta|object|s(?:cript|tyle)|title|xml)[^>]*+>#i', '', $data);
		} while ($old_data !== $data);
		return $data;
	}
	
	

	
	# 分页$url 1伪静态2正常 $page_name 页面文件 $page_parameters 页面参数 $page_current 页面当前页面 $page_size 页面显示各数 $count 总数据
	public function pager($page_name,$page_parameters='',$page_current,$page_size,$count,$url=2){
	    parse_str($page_parameters);
	    $page_count     =ceil($count/$page_size);
	    $page_start     =$page_current-2;#开始
	    $page_end       =$page_current+2;#结束
	    if($page_current<=3){
	        $page_start =1;
	        $page_end   =5;
	    }
	    if($page_current>$page_count-4){
	        $page_start =$page_count-5;
	        $page_end   =$page_count;
	    }
	    if($page_start<1)$page_start=1;
	    if($page_end>$page_count)$page_end=$page_count;
	    if($url==1){
		    $html="";
		    $html.="<div class=\"text-center\"><ul class=\"pagination\">";
		    if($page_current!=1){
		        $page_prev=$page_current-1;
		        $html.="<li><a href='".$page_name."/".$page_parameters."-".$page_prev."'>上一页</a></li>";
		    }else{
		        $html.="<li class=\"disabled\"><a>&laquo;</a></li>";
		    }
		    for($i=$page_start;$i<=$page_end;$i++){
		        if($i==$page_current){
		            $html.="<li class=\"active\"><a>".$i."</a></li>";
		        }else{
		            $html.="<li><a href='".$page_name."/".$page_parameters."-".$i."'>".$i."</a></li>";
		        }
		    }
		    if($page_current<=$page_count-1){
		        $page_next=$page_current+1;
		        $html.="<li><a href='".$page_name."/".$page_parameters."-".$page_next."'>下一页</a></li>";
		    }else{
		        $html.="<li class=\"disabled\"><a>&raquo;</a></li>";
		    }
		    $html.="</ul></div>";
	    }
	    if($url==2){
		
		    $html="";
		    $html.="<div class=\"text-center\"><ul class=\"pagination\">";
		    if($page_current!=1){
		        $page_prev=$page_current-1;
		        $html.="<li><a href='".$page_name."&id=".$page_parameters."&page=".$page_prev."'>上一页</a></li>";
		    }else{
		        $html.="<li class=\"disabled\"><a>&laquo;</a></li>";
		    }
		    for($i=$page_start;$i<=$page_end;$i++){
		        if($i==$page_current){
		            $html.="<li class=\"active\"><a>".$i."</a></li>";
		        }else{
		            $html.="<li><a href='".$page_name."&id=".$page_parameters."&page=".$i."'>".$i."</a></li>";
		        }
		    }
		    if($page_current<=$page_count-1){
		        $page_next=$page_current+1;
		        $html.="<li><a href='".$page_name."&id=".$page_parameters."&page=".$page_next."'>下一页</a></li>";
		    }else{
		        $html.="<li class=\"disabled\"><a>&raquo;</a></li>";
		    }
		    $html.="</ul></div>";
	    }
	    return $html;
	}
	public function isMobile(){
	    $HTTP_USER_AGENT = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : "";
	    if( preg_match('/(Mobile|iPhone|Android|WAP|NetFront|JAVA|OperasMini|UCWEB|WindowssCE|Symbian|Series|webOS|SonyEricsson|Sony|BlackBerry|Cellphone|dopod|Nokia|samsung|PalmSource|Xphone|Xda|Smartphone|PIEPlus|MEIZU|MIDP|CLDC)/i',$HTTP_USER_AGENT) ){
	        return true;
	    }
	    return false;
	}
}

?>