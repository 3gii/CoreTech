<?php
!defined('DROP') && exit('REFUSED!');
# ================================================================
# 程序APP开发功能包
# @E-mail:3gii@sina.cn
# ================================================================
    /**
     *  判断函数
     */

	# 判断文件后缀是否为图片 $file文件路径，通常是$_FILES['file']['tmp_name']
	 function isImage($file){
		$fileextname = strtolower(substr(strrchr(rtrim(basename($file),'?'),"."),1,4));
		if(in_array($fileextname,array('jpg','jpeg','gif','png','bmp'))){
			return true;
		}else{
			return false;
		}
	}
	# 判断文件后缀是否为PHP、EXE类的可执行的不安全文件
	 function is_notsafe_file($file){
		$fileextname = strtolower(substr(strrchr(rtrim(basename($file),'?'), "."),1,4));
		if(in_array($fileextname,array('php','php3','php4','php5','exe','sh'))){
			return true;
		}else{
			return false;
		}
	}
	# 判断AJAX
	 function isAjax() {
	    if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) ) {
	        if('xmlhttprequest' == strtolower($_SERVER['HTTP_X_REQUESTED_WITH']))
	            return true;
	    }
	    if(!empty($_POST[C('VAR_AJAX_SUBMIT')]) || !empty($_GET[C('VAR_AJAX_SUBMIT')]))
	        return true;
	    return false;
	}
	# 判断POST提交
	 function isPost(){
		if(strtolower($_SERVER['REQUEST_METHOD']) == 'post'){
			return true;
		}else
			return false;
	}
	# 判断GET提交
	 function isGet(){
		if(strtolower($_SERVER['REQUEST_METHOD']) == 'get'){
			return true;
		}else
			return false;
	}
	function isMobile(){
	    $HTTP_USER_AGENT = isset($_SERVER['HTTP_USER_AGENT']) ? strtolower($_SERVER['HTTP_USER_AGENT']) : "";
	    if( preg_match('/(Mobile|iPhone|Android|WAP|NetFront|JAVA|OperasMini|UCWEB|WindowssCE|Symbian|Series|webOS|SonyEricsson|Sony|BlackBerry|Cellphone|dopod|Nokia|samsung|PalmSource|Xphone|Xda|Smartphone|PIEPlus|MEIZU|MIDP|CLDC)/i',$HTTP_USER_AGENT) ){
	        return true;
	    }
	    return false;
	}
	# 判断是否已登录
	function is_login(){
	    return isset($_SESSION['user_id'])?true:false;
	}
	# 判断是否处于微信内置浏览器中
	function in_weixin(){
		$user_agent = strtolower($_SERVER['HTTP_USER_AGENT']);
		if (preg_match('/micromessenger/i', $user_agent)){
			return true;
		}
		return false;
	}
	# 判断EMAIL合法性
	 function is_email($user_email){
	    $chars = "/^([a-z0-9+_]|\\-|\\.)+@(([a-z0-9_]|\\-)+\\.)+[a-z]{2,6}\$/i";
	    if (strpos($user_email, '@') !== false && strpos($user_email, '.') !== false){
	        if (preg_match($chars, $user_email)){
	            return true;
	        }else{
	            return false;
	        }
	    }else{
	        return false;
	    }
	}
	function clear_files(){
		$dirs=array();
		$dirs[]=ROOT.'upload/files/';
		foreach ($dirs AS $dir){
			$folder = @opendir($dir);
			if ($folder === false){
				continue;
			}
			while ($file = readdir($folder)){
				if ($file == '.'||$file=='..'||$file=='index.htm'){
					continue;
				}
				if (is_file($dir.$file)){
					 @unlink($dir . $file);
				}
			}
			closedir($folder);
		}
	}

	# 生成唯一的订单号 20140809111259232312
	function trade_no() {
			list($usec, $sec) = explode(" ", microtime());
			$usec = substr(str_replace('0.', '', $usec), 0 ,4);
			$str  = rand(10,99);
			return date("YmdHis").$usec.$str;
	}
	# 输出json
	function json($result=array(),$success=false){
		$array=array();
		if($result===true){
			$array['success']=true;
		}else{
			$array['success']=$success;
			$array['result']=$result;
		}
		header('Content-Type: application/json'); 
		echo(json_encode($array));
		exit;
	}
	function http_404(){
		header("HTTP/1.1 404 Not Found");  
		header("Status: 404 Not Found");
		exit;
	}
	function http_301($url='./'){
		header('HTTP/1.1 301 Moved Permanently');
		Header( "Location:$url");
		exit;
	}
	#有提示跳转
	function alert($text,$url=''){
		echo"<script type='text/javascript'>";
		echo"alert('$text');";
		if($url!=''){
			echo"location.href='$url';";
		}else{
			echo"history.back();";
		}
		echo"</script>";
		exit;
	}
	# 无提示跳转
	function redirect($url=""){
	    echo"<script>location.href='$url';</script>";
	    exit;
	}

    /**
     *  数据处理函数
     */
	# CURL获取文件内容
	function curl_get_contents($url, $timeout = 10){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
		if (substr($url, 0, 8) == 'https://'){
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, FALSE);
			curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1);
		}
		$result = curl_exec($curl);
		curl_close($curl);
		return $result;
	}
	# 搜索结果高亮
	function searchred($content,$keyword) {
		if ($keyword) {
			$keyword = str_replace('/','\/',$keyword);
			$keyword = trim($keyword); 
			$keyword2 = "<font style='color:red'>$keyword</font>";     
			
			$pattern='/(?!<[^>]*)('.$keyword.')(?![^<]*>)/i';
			$content = preg_replace($pattern,$keyword2,$content);
			$content = preg_replace("@&(\w{0,6})?({$keyword2})(\w{0,6})?;@","&$1$keyword$3;",$content);            

			return $content; 
		} else {
			return $content;    
		}
	}
	# 补齐未关闭的标签
	function _sanitize_naughty_html($matches){
		$str = '<'.$matches[1].$matches[2].$matches[3];
		$str .= str_replace(array('>', '<'), array('>', '<'),$matches[4]);	 
		return $str;
	}
	# 过滤图片中的JS代码
	function _js_img_removal($match){
		return str_replace(
			$match[1],
			preg_replace(
				'#src=.*?(alert\(|alert&\#40;|javascript\:|livescript\:|mocha\:|charset\=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si',
				'',
				_filter_attributes(str_replace(array('<', '>'), '', $match[1]))
			),
			$match[0]
		);
	}
	# 根据 salt 混淆密码
	function compile_password($password, $salt){
		if (strlen($password) == 32){
			return md5($password . $salt);
		}
		$password = md5(md5($password) . $salt);
		return $password;
	}
	# 文本截断 $string 要截取的字符串,$length 要截取的字数,$append 是否打印省略号移
	function truncate($string,$length,$append = true){
	    $string = trim($string);
	    $strlength = strlen($string);
	    if ($length == 0 || $length >= $strlength){
	        return $string;
	    }elseif ($length < 0){
	        $length = $strlength + $length;
	        if ($length < 0)
	        {
	            $length = $strlength;
	        }
	    }
	    if (function_exists('mb_substr')){
	        $newstr = mb_substr($string, 0, $length,"UTF-8");
	    }elseif (function_exists('iconv_substr')){
	        $newstr = iconv_substr($string, 0, $length,"UTF-8");
	    }else{
	        for($i=0;$i<$length;$i++){
	                $tempstring=substr($string,0,1);
	                if(ord($tempstring)>127){
	                    $i++;
	                    if($i<$length){
	                        $newstring[]=substr($string,0,3);
	                        $string=substr($string,3);
	                    }
	                }else{
	                    $newstring[]=substr($string,0,1);
	                    $string=substr($string,1);
	                }
	            }
	        $newstr =join($newstring);
	    }
	    if ($append && $string != $newstr){
	        $newstr .= '...';
	    }
	    return $newstr;
	}
	# 格式化时间戳
	function format_time($time){
		$dur=$_SERVER['REQUEST_TIME']-$time;
		if($dur < 60)return $dur.' 秒前';
		if($dur < 3600)return floor($dur/60).' 分钟前';
		if($dur < 86400)return floor($dur/3600).' 小时前';
		if($dur < 2592000)return floor($dur/86400).' 天前';
		return date('m月d日',$time);
	}
	# JPG图片处理 $image:图片文件,$toW:图片宽,$toH:图片高,$mode:图片显示模式 1=>居中,2=>裁切,3=>缩放;
	function ImgReduce($image,$toW,$toH,$mode){
		$img=imagecreatefromjpeg($image);
		$srcW=ImageSX($img);//获取原始图片宽度
		$srcH=ImageSY($img);//获取原始图片高度
		$width=$srcW/$toW;
		$height=$srcH/$toH;
		if($width>$height){
			$newWidth=$toW;
			$newHeight=round($srcH/$width);
		}else{
			$newWidth=round($srcW/$height);
			$newHeight=$toH;
		}
		if($mode==1){
			$x = 0;$y = 0;
			if($newHeight < $toH)$y = ($toH - $newHeight)/2;
			if($newWidth < $toW)$x= ($toW -$newWidth)/2;
			//创建画布并且复制原始图像到画布
			if (function_exists('imagecreatetruecolor')&&(function_exists('imagecopyresampled'))){
				$canvas = @imagecreatetruecolor($toW,$toH);
				@imagecopyresampled($canvas,$img,$x,$y,0,0,$newWidth,$newHeight,$srcW,$srcH);
			}else{
				$canvas=ImageCreate($toW,$toH);
				ImageCopyResized($canvas,$img,$x,$y,0,0,$newWidth,$newHeight,$srcW,$srcH);
			}
		}
		if($mode==2){
			$scale = $newHeight/$newWidth;//获取比例
			if($newWidth > $toW){$newWidth = $toW;$newHeight = round($newWidth*$scale);}
			if($newHeight > $toH){$newHeight = $toH;$newWidth = round($newHeight/$scale);}
			if($newWidth < $toW){$newWidth = $toW;$newHeight = round($newWidth*$scale);}
			if($newHeight < $toH){$newHeight = $toH;$newWidth = round($newHeight/$scale);}
			$x = 0; $y = 0;
			if($newWidth>$toW)$x=$newWidth-$toW;
			if($newHeight>$toH)$y=$newHeight-$toH;
			if (function_exists('imagecreatetruecolor')&&(function_exists('imagecopyresampled'))){
				$canvas=@ImageCreateTrueColor($toW,$toH);
				ImageCopyResampled($canvas,$img,0,0,$x,$y,$newWidth,$newHeight,$srcW,$srcH);#缩放粘帖
			}else{
				$canvas=ImageCreate($toW,$toH);
				ImageCopyResized($canvas,$img,0,0,$x,$y,$newWidth,$newHeight,$srcW,$srcH);
			}
		}
		if($mode==3){
			if (function_exists('imagecreatetruecolor')&&(function_exists('imagecopyresampled'))){
				$canvas = @imagecreatetruecolor($newWidth,$newHeight);
				@imagecopyresampled($canvas,$img,0,0,0,0,$newWidth,$newHeight,$srcW,$srcH);
			}else{
				$canvas=ImageCreate($newWidth,$newHeight);
				ImageCopyResized($canvas,$img,0,0,0,0,$newWidth,$newHeight,$srcW,$srcH);
			}
		}
		//输出图片
		@imagejpeg($canvas,$image,100);
		//回收资源
		@imagedestroy($canvas);
		@ImageDestroy($img);
		return $image;
	}
	
	# 生成指定长度随机KEY
	function getRandomKey($n = 32) {
	    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ'), 0, $n);
	}
	# 获取文件后缀名
	function get_ext($filename){
	    if(!empty($filename)){
	        $tmp_name=explode(".",strtolower($filename));
	        return end($tmp_name);
	    }
	}
	# 过滤查询
	function replace_chars($string){
		$chars=array(
			"'"=>"","\""=>"","%"=>"","and"=>"","select"=>"","@"=>"","^"=>"","&"=>"","+"=>"",","=>"","?"=>"","*"=>"","/"=>"","expression"=>"","<"=>"&lt;",">"=>"&gt;"
			);
		return str_ireplace(array_keys($chars),array_values($chars),$string);
	}
	# 过滤特定字符
	function filter_string($content,$filter=array()){
		$temp=$content;
		if(is_array($filter)&&count($filter)>0){
			foreach($filter as $value){
				$temp=str_replace($value,'',$temp);
			}
		}
		return $temp;
	}
	# 获取系统
	function getOS($AGENT){
	    if(strpos($AGENT,"iPhone"))$os="iPhone";
	    elseif(strpos($AGENT,"iPad"))$os="iPad";
	    elseif(stripos($AGENT,"samsung"))$os="Samsung";
	    elseif(strpos($AGENT,"Huawei"))$os="华为";
	    elseif(stripos($AGENT,"HTC"))$os="HTC";
	    elseif(stripos($AGENT,"SONY"))$os="SONY";
	    elseif(stripos($AGENT,"xiaomi"))$os="小米";
	    elseif(strpos($AGENT,"UCBrowser"))$os="UC浏览器";
	    elseif(strpos($AGENT,"Android"))$os="Android";
	    else $os="PC";
	    return $os;
	}
	#获取IP
	function getIp(){
	    if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
	        $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
	    }elseif (isset($_SERVER['HTTP_CLIENT_IP'])){
	        $ip=$_SERVER['HTTP_CLIENT_IP'];
	    }else{
	        $ip=$_SERVER['REMOTE_ADDR'];
	    }
	    return $ip;
	}
	#新浪接口
	function getCitySina($ip){
	    $json = file_get_contents('http://int.dpool.sina.com.cn/iplookup/iplookup.php?format=json&ip='.$ip);
	    $address = json_decode($json,true);
	    $uaddress=$address['province'].$address['city'].$address['district'].$address['isp'];
	    return $uaddress;
	}
	#淘宝接口
	function getCityTaobao($ip){
		$data="http://ip.taobao.com/service/getIpInfo.php?ip=".$ip;
		$data=curl_get_contents($data);
		$json=json_decode($data);
		if($json->code!=0){
			return 'LAN';
		}else{
			$result=array();
			if($json->data->country!="中国" && $json->data->city!=""){
				$result[]=$json->data->country;
			}
			$result[]=$json->data->region;
			$result[]=$json->data->city;
			//$result[]=$json->data->area;
			//$result[]=" ".$json->data->isp;
			$result=implode("",$result);
			// print_r($result);exit;
			return $result;
				
		}
	}

	# 下载
	function Download($filename='',$data=''){
		if ($filename =='' OR $data=='') {
			return FALSE;
		}
		if (FALSE === strpos($filename, '.')) {
			return FALSE;
		}

		$x = explode('.', $filename);
		$extension = end($x);
		$mimes = array('hqx' => 'application/mac-binhex40', 'cpt' => 'application/mac-compactpro', 'csv' => array('text/x-comma-separated-values', 'text/comma-separated-values', 'application/octet-stream', 'application/vnd.ms-excel', 'application/x-csv', 'text/x-csv', 'text/csv', 'application/csv', 'application/excel', 'application/vnd.msexcel'), 'bin' => 'application/macbinary', 'dms' => 'application/octet-stream', 'lha' => 'application/octet-stream', 'lzh' => 'application/octet-stream', 'exe' => array('application/octet-stream', 'application/x-msdownload'), 'class' => 'application/octet-stream', 'psd' => 'application/x-photoshop', 'so' => 'application/octet-stream', 'sea' => 'application/octet-stream', 'dll' => 'application/octet-stream', 'oda' => 'application/oda', 'pdf' => array('application/pdf', 'application/x-download'), 'ai' => 'application/postscript', 'eps' => 'application/postscript', 'ps' => 'application/postscript', 'smi' => 'application/smil', 'smil' => 'application/smil', 'mif' => 'application/vnd.mif', 'xls' => array('application/excel', 'application/vnd.ms-excel', 'application/msexcel'), 'ppt' => array('application/powerpoint', 'application/vnd.ms-powerpoint'), 'wbxml' => 'application/wbxml', 'wmlc' => 'application/wmlc', 'dcr' => 'application/x-director', 'dir' => 'application/x-director', 'dxr' => 'application/x-director', 'dvi' => 'application/x-dvi', 'gtar' => 'application/x-gtar', 'gz' => 'application/x-gzip', 'php' => 'application/x-httpd-php', 'php4' => 'application/x-httpd-php', 'php3' => 'application/x-httpd-php', 'phtml' => 'application/x-httpd-php', 'phps' => 'application/x-httpd-php-source', 'js' => 'application/x-javascript', 'swf' => 'application/x-shockwave-flash', 'sit' => 'application/x-stuffit', 'tar' => 'application/x-tar', 'tgz' => array('application/x-tar', 'application/x-gzip-compressed'), 'xhtml' => 'application/xhtml+xml', 'xht' => 'application/xhtml+xml', 'zip' => array('application/x-zip', 'application/zip', 'application/x-zip-compressed'), 'mid' => 'audio/midi', 'midi' => 'audio/midi', 'mpga' => 'audio/mpeg', 'mp2' => 'audio/mpeg', 'mp3' => array('audio/mpeg', 'audio/mpg', 'audio/mpeg3', 'audio/mp3'), 'aif' => 'audio/x-aiff', 'aiff' => 'audio/x-aiff', 'aifc' => 'audio/x-aiff', 'ram' => 'audio/x-pn-realaudio', 'rm' => 'audio/x-pn-realaudio', 'rpm' => 'audio/x-pn-realaudio-plugin', 'ra' => 'audio/x-realaudio', 'rv' => 'video/vnd.rn-realvideo', 'wav' => 'audio/x-wav', 'bmp' => 'image/bmp', 'gif' => 'image/gif', 'jpeg' => array('image/jpeg', 'image/pjpeg'), 'jpg' => array('image/jpeg', 'image/pjpeg'), 'jpe' => array('image/jpeg', 'image/pjpeg'), 'png' => array('image/png', 'image/x-png'), 'tiff' => 'image/tiff', 'tif' => 'image/tiff', 'css' => 'text/css', 'html' => 'text/html', 'htm' => 'text/html', 'shtml' => 'text/html', 'txt' => 'text/plain', 'text' => 'text/plain', 'log' => array('text/plain', 'text/x-log'), 'rtx' => 'text/richtext', 'rtf' => 'text/rtf', 'xml' => 'text/xml', 'xsl' => 'text/xml', 'mpeg' => 'video/mpeg', 'mpg' => 'video/mpeg', 'mpe' => 'video/mpeg', 'qt' => 'video/quicktime', 'mov' => 'video/quicktime', 'avi' => 'video/x-msvideo', 'movie' => 'video/x-sgi-movie', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'word' => array('application/msword', 'application/octet-stream'), 'xl' => 'application/excel', 'eml' => 'message/rfc822', 'json' => array('application/json', 'text/json'));
		if (!isset($mimes[$extension])) {
			$mime = 'application/octet-stream';
		} else {
			$mime = (is_array($mimes[$extension])) ? $mimes[$extension][0] : $mimes[$extension];
		}
		header('Content-Type: "' . $mime . '"');
		$tmpName = $filename;
		$filename = '"' . urlencode($tmpName) . '"'; #ie中文文件名支持
		if (strstr(strtolower($_SERVER['HTTP_USER_AGENT']), 'firefox') != false) {
			$filename = '"' . $tmpName . '"';
		}#firefox中文文件名支持
		if (strstr(strtolower($_SERVER['HTTP_USER_AGENT']), 'chrome') != false) {
			$filename = urlencode($tmpName);
		}#Chrome中文文件名支持
		header('Content-Disposition: attachment; filename=' . $filename);
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header("Content-Transfer-Encoding: binary");
		header('Pragma: no-cache');
		header("Content-Length: " . strlen($data));
		exit($data);
	}
	# 创建文件夹
	function mk_dir($dir,$mode=0777,$index=true) {
	    if(!is_dir($dir)) {
	       	mk_dir(dirname($dir));
	        mkdir($dir);
	        if($index)@file_put_contents($dir.'/index.htm','');
	    }
	}
	# 删除文件夹
	function rm_dir($dir){
	    $dh=opendir($dir);
	    while($file=readdir($dh)){
	        if($file!="."&&$file!=".."){
	            $fullpath=$dir."/".$file;
	            if(!is_dir($fullpath)){
	                unlink($fullpath);
	            }else{
	                rm_dir($fullpath);
	            }
	        }
	    }
	    closedir($dh);
	    if(rmdir($dir)){
	        return true;
	    }else{
	        return false;
	    }
	}
	# 上传文件
	function upload($upload,$target='./',$exts='jpg,jpeg,gif,png,bmp,torrent,zip,rar,7z,doc,docx,xls,xlsx,ppt,pptx,csv,mp3,wma,swf,flv,txt',$size=20,$rename=''){
	    mk_dir($target);
	    if(is_array($upload['name'])){
	        $return=array();
	        foreach ($upload["name"] as $k=>$v){
	            if (!empty($upload['name'][$k])){
	                $ext=get_ext($upload['name'][$k]);
	                if (strpos($exts,$ext)!==false&&upload_check($upload['tmp_name'][$k],$ext)==$ext&&$upload['size'][$k]<$size*1024*1024){
	                    $name=empty($rename)?upload_name($ext):upload_rename($rename,$ext);
	                    if (upload_move($upload['tmp_name'][$k],$target.$name)){
	                        $return[]=$name;
	                    }
	                }
	            }
	        }
	        return $return;
	    }else{
	        $return='';
	        if (!empty($upload['name'])){
	            $ext=get_ext($upload['name']);
	            if(strpos($exts,$ext)!==false&&upload_check($upload['tmp_name'],$ext)==$ext&&$upload['size']<$size*1024*1024){
	                $name=empty($rename)?upload_name($ext):upload_rename($rename,$ext);
	                if (upload_move($upload['tmp_name'],$target.$name)){
	                    $return=$name;
	                }
	            }
	        }
	    }
	    return $return;
	}
	function upload_name($ext){
	    $name=date('YmdHis');
	    for ($i=0; $i < 3; $i++){
	        $name.= chr(mt_rand(97, 122));
	    }
	    $name=strtoupper(md5($name)).".".$ext;
	    return (string)$name;
	}
	function upload_rename($rename,$ext){
	    $name=$rename.".".$ext;
	    return (string)$name;
	}
	# 移动上传文件
	function upload_move($from, $target= ''){
	    if (function_exists("move_uploaded_file")){
	        if (move_uploaded_file($from, $target)){
	            @chmod($target,0755);
	            return true;
	        }
	    }elseif (copy($from, $target)){
	        @chmod($target,0755);
	        return true;
	    }
	    return false;
	}
	# 检查上传文件
	function upload_check($name,$ext){
	    $str=$format='';
	    $file=@fopen($name, 'rb');
	    if ($file){
	        $str=@fread($file, 0x400);
	        @fclose($file);
	        if (strlen($str) >= 2 ){
	            if (substr($str, 0, 4)=='MThd' && $ext != 'txt'){
	                $format='mid';
	            }elseif (substr($str, 0, 4)=='RIFF' && $ext=='wav'){
	                $format='wav';
	            }elseif (substr($str ,0, 3)=="\xFF\xD8\xFF"){
	                $format='jpg';
	            }elseif (substr($str ,0, 4)=='GIF8' && $ext != 'txt'){
	                $format='gif';
	            }elseif (substr($str ,0, 8)=="\x89\x50\x4E\x47\x0D\x0A\x1A\x0A"){
	                $format='png';
	            }elseif (substr($str ,0, 2)=='BM' && $ext != 'txt'){
	                $format='bmp';
	            }elseif ((substr($str ,0, 3)=='CWS' || substr($str ,0, 3)=='FWS') && $ext != 'txt'){
	                $format='swf';
	            }elseif (substr($str ,0, 4)=="\xD0\xCF\x11\xE0"){   // D0CF11E==DOCFILE==Microsoft Office Document
	                if (substr($str,0x200,4)=="\xEC\xA5\xC1\x00" || $ext=='doc'){
	                    $format='doc';
	                }elseif (substr($str,0x200,2)=="\x09\x08" || $ext=='xls'){
	                    $format='xls';
	                }elseif (substr($str,0x200,4)=="\xFD\xFF\xFF\xFF" || $ext=='ppt'){
	                    $format='ppt';
	                }
	            }elseif (substr($str ,0, 2)=="7z"){
	                $format='7z';
	            }elseif (substr($str ,0, 4)=="PK\x03\x04"){
	                $format='zip';
	            }elseif (substr($str ,0, 4)=='Rar!' && $ext != 'txt'){
	                $format='rar';
	            }elseif (substr($str ,0, 4)=="\x25PDF"){
	                $format='pdf';
	            }elseif (substr($str ,0, 3)=="\x30\x82\x0A"){
	                $format='cert';
	            }elseif (substr($str ,0, 4)=='ITSF' && $ext != 'txt'){
	                $format='chm';
	            }elseif (substr($str ,0, 4)=="\x2ERMF"){
	                $format='rm';
	            }elseif ($ext=='sql'){
	                $format='sql';
	            }elseif ($ext=='txt'){
	                $format='txt';
	            }elseif ($ext=='htm'){
	                $format='htm';
	            }elseif ($ext=='html'){
	                $format='html';
	            }elseif (substr($str ,0, 3)=='FLV'){
	                $format='flv';
	            }else{
	                $format=$ext;
	            }
	        }
	    }
	    return $format;
	}
	function format_size($filesize){
	    if($filesize >= 1073741824){
			$filesize=round($filesize / 1073741824 * 100) / 100 . ' GB';
		}elseif ($filesize >= 1048576){
			$filesize=round($filesize / 1048576 * 100) / 100 . ' MB';
	    }elseif($filesize >= 1024){
			$filesize=round($filesize / 1024 * 100) / 100 . ' KB';
	    }else{
			$filesize=$filesize.' Bytes';
		}
	    return $filesize;
	}
	function pinyin($string, $utf8 = true) {
		$pinyinArr=pinyin_code();
		$string = ($utf8 === true) ? iconv('utf-8', 'gbk', $string) : $string;
		$num = strlen($string);
		$pinyin = '';
        for ($i=0; $i < $num; $i++) {
            $temp = ord(substr($string, $i, 1));
            if ($temp > 160) {
                $temp2 = ord(substr($string, ++$i, 1));
                $temp  = $temp * 256 + $temp2 - 65536;
            }
			if ($temp > 0 && $temp < 160) {
				$pinyin .= chr($temp);
			} elseif ($temp < -20319 || $temp > -10247){
				$pinyin	.= '';
			} else {
				$total =sizeof($pinyinArr) - 1;
				for ($j = $total; $j >= 0; $j--) {
					if ($pinyinArr[$j][1] <= $temp) {
						break;
					}
				}
				$pinyin .= $pinyinArr[$j][0];
			}
        }
        return ($utf8==true) ? iconv('gbk', 'utf-8', $pinyin) : $pinyin;
	}

	/**
	 * 拼音代码库
	 * @return array
	 */
	function pinyin_code() {
		return array(
		array("a",-20319),array("ai",-20317),array("an",-20304),array("ang",-20295),array("ao",-20292),array("ba",-20283),array("bai",-20265),array("ban",-20257),array("bang",-20242),array("bao",-20230),array("bei",-20051),array("ben",-20036),array("beng",-20032),array("bi",-20026),array("bian",-20002),array("biao",-19990),array("bie",-19986),array("bin",-19982),array("bing",-19976),array("bo",-19805),array("bu",-19784),array("ca",-19775),array("cai",-19774),array("can",-19763),array("cang",-19756),array("cao",-19751),array("ce",-19746),array("ceng",-19741),array("cha",-19739),array("chai",-19728),array("chan",-19725),array("chang",-19715),array("chao",-19540),array("che",-19531),array("chen",-19525),array("cheng",-19515),array("chi",-19500),array("chong",-19484),array("chou",-19479),array("chu",-19467),array("chuai",-19289),array("chuan",-19288),array("chuang",-19281),array("chui",-19275),array("chun",-19270),array("chuo",-19263),array("ci",-19261),array("cong",-19249),array("cou",-19243),array("cu",-19242),array("cuan",-19238),array("cui",-19235),array("cun",-19227),array("cuo",-19224),array("da",-19218),array("dai",-19212),array("dan",-19038),array("dang",-19023),array("dao",-19018),array("de",-19006),array("deng",-19003),array("di",-18996),array("dian",-18977),array("diao",-18961),array("die",-18952),array("ding",-18783),array("diu",-18774),array("dong",-18773),array("dou",-18763),array("du",-18756),array("duan",-18741),array("dui",-18735),array("dun",-18731),array("duo",-18722),array("e",-18710),array("en",-18697),array("er",-18696),array("fa",-18526),array("fan",-18518),array("fang",-18501),array("fei",-18490),array("fen",-18478),array("feng",-18463),array("fo",-18448),array("fou",-18447),array("fu",-18446),array("ga",-18239),array("gai",-18237),array("gan",-18231),array("gang",-18220),array("gao",-18211),array("ge",-18201),array("gei",-18184),array("gen",-18183),array("geng",-18181),array("gong",-18012),array("gou",-17997),array("gu",-17988),array("gua",-17970),array("guai",-17964),array("guan",-17961),array("guang",-17950),array("gui",-17947),array("gun",-17931),array("guo",-17928),array("ha",-17922),array("hai",-17759),array("han",-17752),array("hang",-17733),array("hao",-17730),array("he",-17721),array("hei",-17703),array("hen",-17701),array("heng",-17697),array("hong",-17692),array("hou",-17683),array("hu",-17676),array("hua",-17496),array("huai",-17487),array("huan",-17482),array("huang",-17468),array("hui",-17454),array("hun",-17433),array("huo",-17427),array("ji",-17417),array("jia",-17202),array("jian",-17185),array("jiang",-16983),array("jiao",-16970),array("jie",-16942),array("jin",-16915),array("jing",-16733),array("jiong",-16708),array("jiu",-16706),array("ju",-16689),array("juan",-16664),array("jue",-16657),array("jun",-16647),array("ka",-16474),array("kai",-16470),array("kan",-16465),array("kang",-16459),array("kao",-16452),array("ke",-16448),array("ken",-16433),array("keng",-16429),array("kong",-16427),array("kou",-16423),array("ku",-16419),array("kua",-16412),array("kuai",-16407),array("kuan",-16403),array("kuang",-16401),array("kui",-16393),array("kun",-16220),array("kuo",-16216),array("la",-16212),array("lai",-16205),array("lan",-16202),array("lang",-16187),array("lao",-16180),array("le",-16171),array("lei",-16169),array("leng",-16158),array("li",-16155),array("lia",-15959),array("lian",-15958),array("liang",-15944),array("liao",-15933),array("lie",-15920),array("lin",-15915),array("ling",-15903),array("liu",-15889),array("long",-15878),array("lou",-15707),array("lu",-15701),array("lv",-15681),array("luan",-15667),array("lue",-15661),array("lun",-15659),array("luo",-15652),array("ma",-15640),array("mai",-15631),array("man",-15625),array("mang",-15454),array("mao",-15448),array("me",-15436),array("mei",-15435),array("men",-15419),array("meng",-15416),array("mi",-15408),array("mian",-15394),array("miao",-15385),array("mie",-15377),array("min",-15375),array("ming",-15369),array("miu",-15363),array("mo",-15362),array("mou",-15183),array("mu",-15180),array("na",-15165),array("nai",-15158),array("nan",-15153),array("nang",-15150),array("nao",-15149),array("ne",-15144),array("nei",-15143),array("nen",-15141),array("neng",-15140),array("ni",-15139),array("nian",-15128),array("niang",-15121),array("niao",-15119),array("nie",-15117),array("nin",-15110),array("ning",-15109),array("niu",-14941),array("nong",-14937),array("nu",-14933),array("nv",-14930),array("nuan",-14929),array("nue",-14928),array("nuo",-14926),array("o",-14922),array("ou",-14921),array("pa",-14914),array("pai",-14908),array("pan",-14902),array("pang",-14894),array("pao",-14889),array("pei",-14882),array("pen",-14873),array("peng",-14871),array("pi",-14857),array("pian",-14678),array("piao",-14674),array("pie",-14670),array("pin",-14668),array("ping",-14663),array("po",-14654),array("pu",-14645),array("qi",-14630),array("qia",-14594),array("qian",-14429),array("qiang",-14407),array("qiao",-14399),array("qie",-14384),array("qin",-14379),array("qing",-14368),array("qiong",-14355),array("qiu",-14353),array("qu",-14345),array("quan",-14170),array("que",-14159),array("qun",-14151),array("ran",-14149),array("rang",-14145),array("rao",-14140),array("re",-14137),array("ren",-14135),array("reng",-14125),array("ri",-14123),array("rong",-14122),array("rou",-14112),array("ru",-14109),array("ruan",-14099),array("rui",-14097),array("run",-14094),array("ruo",-14092),array("sa",-14090),array("sai",-14087),array("san",-14083),array("sang",-13917),array("sao",-13914),array("se",-13910),array("sen",-13907),array("seng",-13906),array("sha",-13905),array("shai",-13896),array("shan",-13894),array("shang",-13878),array("shao",-13870),array("she",-13859),array("shen",-13847),array("sheng",-13831),array("shi",-13658),array("shou",-13611),array("shu",-13601),array("shua",-13406),array("shuai",-13404),array("shuan",-13400),array("shuang",-13398),array("shui",-13395),array("shun",-13391),array("shuo",-13387),array("si",-13383),array("song",-13367),array("sou",-13359),array("su",-13356),array("suan",-13343),array("sui",-13340),array("sun",-13329),array("suo",-13326),array("ta",-13318),array("tai",-13147),array("tan",-13138),array("tang",-13120),array("tao",-13107),array("te",-13096),array("teng",-13095),array("ti",-13091),array("tian",-13076),array("tiao",-13068),array("tie",-13063),array("ting",-13060),array("tong",-12888),array("tou",-12875),array("tu",-12871),array("tuan",-12860),array("tui",-12858),array("tun",-12852),array("tuo",-12849),array("wa",-12838),array("wai",-12831),array("wan",-12829),array("wang",-12812),array("wei",-12802),array("wen",-12607),array("weng",-12597),array("wo",-12594),array("wu",-12585),array("xi",-12556),array("xia",-12359),array("xian",-12346),array("xiang",-12320),array("xiao",-12300),array("xie",-12120),array("xin",-12099),array("xing",-12089),array("xiong",-12074),array("xiu",-12067),array("xu",-12058),array("xuan",-12039),array("xue",-11867),array("xun",-11861),array("ya",-11847),array("yan",-11831),array("yang",-11798),array("yao",-11781),array("ye",-11604),array("yi",-11589),array("yin",-11536),array("ying",-11358),array("yo",-11340),array("yo",-11340),array("yong",-11339),array("you",-11324),array("yu",-11303),array("yuan",-11097),array("yue",-11077),array("yun",-11067),array("za",-11055),array("zai",-11052),array("zan",-11045),array("zang",-11041),array("zao",-11038),array("ze",-11024),array("zei",-11020),array("zen",-11019),array("zeng",-11018),array("zha",-11014),array("zhai",-10838),array("zhan",-10832),array("zhang",-10815),array("zhao",-10800),array("zhe",-10790),array("zhen",-10780),array("zheng",-10764),array("zhi",-10587),array("zhong",-10544),array("zhou",-10533),array("zhu",-10519),array("zhua",-10331),array("zhuai",-10329),array("zhuan",-10328),array("zhuang",-10322),array("zhui",-10315),array("zhun",-10309),array("zhuo",-10307),array("zi",-10296),array("zong",-10281),array("zou",-10274),array("zu",-10270),array("zuan",-10262),array("zui",-10260),array("zun",-10256),array("zuo",-10254),
        );
	
	}
?>