<?php
# ================================================================
# 模板解析类
# @E-mail:3gii@sina.cn
# ================================================================
class template{
	public $template_dir='';
	public $compile_dir='';
	public $template_out='';
	public $overtime=1800;
	public $cache=false;
	private $template=array();
	private $_loop=array();
	private $_var=array();
	private $_foreach=array();
	private $_current_file='';
	private $_expires=0;
	private $_foreachmark='';
	private $_temp_key=array();
	private $_temp_val =array();
	private $_patchstack=array();
	public function __construct(){
		if($this->template_dir==''){
			$this->template_dir=ROOT.'theme';//定义模板文件存放路径
		}
		if($this->compile_dir==''){
			$this->compile_dir=ROOT.'upload/files';//定义解析之后模板文件存放路径
		}
	}
	#设置模板变量
	public function in($k,$v=''){//将变量写入数组$_var中
		if(is_array($k)){
			foreach ($k as $key=>$val){
				if ($key!='')$this->_var[$key]=$val;
			}
		}else{
			if ($k!='')$this->_var[$k]=$v;
		}
	}
	#输出模板到页面
	public function out($filename,$cache_id=''){
		error_reporting(E_ERROR | E_WARNING | E_PARSE);//错误控制
		$out=$this->fetch($filename,$cache_id);//获取模板解析结果
		$out=preg_replace_callback("/{dynamic\:([^\}]*)}/",array( &$this, 'prase_dynamic'),$out);
		echo $out;
	}
	private function prase_dynamic($matches){
		return $this->fetch($matches[1]);	
	}
	#解析模板返回结果
	public function fetch($filename, $cache_id=''){
		if (strncmp($filename,'string:',7) == 0){
			$out=$this->_eval($this->parse(substr($filename, 7)));
		}else{
			$filename=$this->template_dir.'/'.$filename;
			if ($cache_id && $this->cache){
				$out=$this->template_out;

			}else{
				if (!in_array($filename, $this->template)){
					$this->template[]=$filename;
				}
				$out=$this->make($filename);
				if ($cache_id){
					$cachename=md5(basename($filename, strrchr($filename, '.')).'_'.$cache_id);
					$data=serialize(array(
						'template'=>$this->template,
						'expires'=>$_SERVER['REQUEST_TIME'] + $this->overtime,
						'maketime'=>$_SERVER['REQUEST_TIME']
						));
					$out=str_replace("\r", '', $out);
					while (strpos($out, "\n\n") !== false){
						$out=str_replace("\n\n", "\n", $out);
					}
					$hash_dir=$this->compile_dir;
					if (!is_dir($hash_dir)){
						mkdir($hash_dir);
					}
					if (file_put_contents($hash_dir.'/'.$cachename.'.php', '<?php exit;?>'.$data.$out, LOCK_EX) === false){
						trigger_error('can\'t write:'.$hash_dir.'/'.$cachename.'.php');
					}
					$this->template=array();
				}
			}

		}
		return $out;
	}
	#解析模板标签
	private function parse($source){
		$file_type=strtolower(strrchr($this->_current_file,'.'));
		if(strpos($source,"\xEF\xBB\xBF")!==false)$source=str_replace("\xEF\xBB\xBF",'',$source);
		$source=str_replace('<?php exit?>','',$source);
		$pattern=array('/<!--[^>|\n]*?({.+?})[^<|{|\n]*?-->/','/<!--[^<|>|{|\n]*?-->/','/(href=["|\'])\.\.\/(.*?)(["|\'])/i','/([\'|"])\.\.\//is',);
		$replace=array('\1','','\1\2\3','\1');
		$source=preg_replace($pattern,$replace,$source);
		//return preg_replace("/{([^\}\{\n]*)}/e", "\$this->select('\\1');", $source);
		return preg_replace_callback("/{([^\}\{\n]*)}/",array( &$this, 'select'),$source);
	}
	#编译
	private function make($filename){
		$name=$this->compile_dir.'/'.md5($filename).'.php';
		if ($this->_expires){
			$expires=$this->_expires - $this->overtime;
		}else{
			$filestat=@stat($name);
			$expires =$filestat['mtime'];
		}
		$filestat=@stat($filename);
		if ($filestat['mtime'] <= $expires){
			if (file_exists($name)){
				$source=$this->_require($name);
				if($source=='')$expires=0;
			}else{
				$source='';
				$expires=0;
			}
		}
		if ($filestat['mtime'] > $expires){
			$this->_current_file=$filename;
			$source=$this->parse(file_get_contents($filename));
			if (file_put_contents($name, $source, LOCK_EX) === false){
				trigger_error('can\'t write:'.$name);
			}
			$source=$this->_eval($source);
		}
		return $source;
	}
	#是否缓存
	public function is_cached($filename, $cache_id=''){
		$cachename=md5(basename($filename, strrchr($filename, '.')).'_'.$cache_id);
		if ($this->cache == true){
			$hash_dir=$this->compile_dir;
			if ($data=@file_get_contents($hash_dir.'/'.$cachename.'.php')){
				$data=substr($data, 13);
				$pos =strpos($data, '<');
				$paradata=substr($data, 0, $pos);
				$para    =@unserialize($paradata);

				if ($para === false || $_SERVER['REQUEST_TIME'] > $para['expires']){
					//print_r($para);exit;
					$this->cache=false;
					return false;
				}
				$this->_expires=$para['expires'];
				$this->template_out=substr($data, $pos);
				foreach ($para['template'] AS $val){
					$stat=@stat($val);
					if ($para['maketime'] < $stat['mtime']){
						$this->cache=false;
						return false;
					}
				}
			}else{
				$this->cache=false;
				return false;
			}
			return true;
		}else{
			return false;
		}
	}

	#选择器
	private function select($matches){
		$tag=$matches[1];
		$tag=stripslashes(trim($tag));
		if (empty($tag)){
			return '{}';
		}elseif ($tag{0} == '*' && substr($tag, -1) == '*'){
			return '';
		}elseif ($tag{0} == '$'){
			return '<?php echo '.$this->get_val(substr($tag, 1)).'; ?>';
		}elseif ($tag{0} == '/'){
			switch (substr($tag, 1)){
				case 'if':
					return '<?php endif; ?>';
					break;
				case 'foreach':
					if ($this->_foreachmark == 'foreachelse'){
						$output='<?php endif; unset($_from); ?>';
					}else{
						array_pop($this->_patchstack);
						$output='<?php endforeach; endif; unset($_from); ?>';
					}
					$output .= "<?php \$this->pop_vars();?>";
					return $output;
					break;
				case 'loop':
					$output='<?php endforeach; endif; unset($_result); ?>';
					return $output;
				default:
					return '{'. $tag .'}';
					break;
			}
		}else{
			$tag_sel=array_shift(explode(' ', $tag));
			switch ($tag_sel){
				case 'if':
					return $this->make_if(substr($tag, 3));
					break;
				case 'else':
					return '<?php else: ?>';
					break;
				case 'elseif':
					return $this->make_if(substr($tag, 7), true);
					break;
				case 'foreachelse':
					$this->_foreachmark='foreachelse';
					return '<?php endforeach; else: ?>';
					break;
				case 'foreach':
					$this->_foreachmark='foreach';
					if(!isset($this->_patchstack)){
						$this->_patchstack=array();
					}
					return $this->make_foreach(substr($tag, 8));
					break;
				case 'loop':
					return $this->make_loop(substr($tag,5));
					break;
				case 'include':
					$t=$this->get_para(substr($tag, 8), 0);
					return '<?php echo $this->fetch('."'$t[file]'".');?>';
					break;
				case 'cycle' :
					$t=$this->get_para(substr($tag, 6), 0);
					return '<?php echo $this->cycle('.$this->make_array($t).');?>';
					break;
					/*
                case 'dynamic':
					$t=$this->get_para(substr($tag, 8), 0);
					return '<?php echo $this->fetch('."'$t[file]'".');?>';
                    break;*/
				default:
					return '{'.$tag.'}';
					break;
			}
		}
	}
	private function get_val_replace($matches){
		return str_replace('$','\$',$matches[1]);
	}
	private function get_val($val){
		if (strrpos($val, '[') !== false){
			//$val=preg_replace("/\[([^\[\]]*)\]/eis", "'.'.str_replace('$','\$','\\1')", $val);
			$val=preg_replace_callback("/\[([^\[\]]*)\]/is",array(&$this,'get_val_replace'),$val);
		}
		if (strrpos($val, '|') !== false){
			$moddb=explode('|', $val);
			$val=array_shift($moddb);
		}
		if (empty($val)){
			return '';
		}
		if (strpos($val, '.$') !== false){
			$all=explode('.$', $val);
			foreach ($all AS $key=>$val){
				$all[$key]=$key == 0 ? $this->make_var($val) : '['. $this->make_var($val).']';
			}
			$p=implode('', $all);
		}else{
			$p=$this->make_var($val);
		}

		if (!empty($moddb)){
			foreach ($moddb AS $key=>$mod){
				$s=explode(':', $mod);
				switch ($s[0]){
					case 'escape':
						$s[1]=trim($s[1], '"');
						if ($s[1] == 'html'){
							$p='htmlspecialchars('.$p.')';
						}elseif ($s[1] == 'urlencode'){
							$p='urlencode('.$p.')';
						}elseif ($s[1] == 'urldecode'){
							$p='urldecode('.$p.')';
						}elseif ($s[1] == 'addslashes'){
							$p='addslashes('.$p.')';
						}elseif ($s[1] == 'base64encode'){
							$p='base64_encode('.$p.')';
						}elseif ($s[1] == 'base64decode'){
							$p='base64_decode('.$p.')';
						}elseif ($s[1] == 'md5'){
							$p='md5('.$p.')';
						}elseif ($s[1] == 'number_format'){
							$p='number_format('.$p.')';
						}else{
							$p='htmlspecialchars('.$p.')';
						}
						break;
					case 'datetime'://datetime格式化
						$s[1]=trim($s[1],'"');
						$p="date('".$s[1]."',".strtotime($p).")";
						break;
					case 'timestamp'://时间截格式化
						$s[1]=trim($s[1], '"');
						$p="date('".$s[1]."',".$p.")";
						break;
					case 'nl2br':
						$p='nl2br('.$p.')';
						break;
					case 'count':
						$p='count('.$p.')';
						break;
					case 'default':
						$s[1]=$s[1]{0} == '$' ?  $this->get_val(substr($s[1], 1)) : "'$s[1]'";
						$p='empty('.$p.') ? '.$s[1].' : '.$p;
						break;
					case 'truncate':
						$p='truncate('.$p.",$s[1])";
						break;
					case 'strip_tags':
						$p='strip_tags('.$p.')';
						break;
					default:
						# code...
						break;
				}
			}
		}
		return $p;
	}
	#生成临时变量
	private function make_var($val){

		if (strrpos($val, '.') === false){
			if (isset($this->_var[$val]) && isset($this->_patchstack[$val])){
				$val=$this->_patchstack[$val];
			}
			$p='$this->_var[\''.$val.'\']';
		}else{
			$t=explode('.', $val);
			$_var_name=array_shift($t);
			if (isset($this->_var[$_var_name]) && isset($this->_patchstack[$_var_name])){
				$_var_name=$this->_patchstack[$_var_name];
			}
			if ($_var_name == 'template'){
				 $p=$this->make_template($t);
			}elseif($_var_name == 'loop'){
				$p='$'.$_var_name;
			}else{
				$p='$this->_var[\''.$_var_name.'\']';
			}
			foreach ($t AS $val){
				$p.= '[\''.$val.'\']';
			}
		}
		return $p;
	}
	private function get_para($val, $type=1){
		$pa=$this->str_trim($val);
		foreach ($pa AS $value){
			if (strrpos($value, '=')){
				list($a, $b)=explode('=', str_replace(array(' ', '"', "'", '&quot;'), '', $value));
				if ($b{0} == '$'){
					if ($type){
						eval('$para[\''.$a.'\']='.$this->get_val(substr($b, 1)).';');
					}else{
						$para[$a]=$this->get_val(substr($b, 1));
					}
				}else{
					$para[$a]=$b;
				}
			}
		}
		return $para;
	}
	private function make_if($tag_args, $elseif=false){
		preg_match_all('/\-?\d+[\.\d]+|\'[^\'|\s]*\'|"[^"|\s]*"|[\$\w\.]+|!==|===|==|!=|<>|<<|>>|<=|>=|&&|\|\||\(|\)|,|\!|\^|=|&|<|>|~|\||\%|\+|\-|\/|\*|\@|\S/', $tag_args, $match);
		$tokens=$match[0];
		$token_count=array_count_values($tokens);
		for ($i=0, $count=count($tokens); $i < $count; $i++){
			$token=&$tokens[$i];
			switch (strtolower($token)){
				case 'eq':
					$token='==';
					break;
				case 'ne':
				case 'neq':
					$token='!=';
					break;
				case 'lt':
					$token='<';
					break;
				case 'le':
				case 'lte':
					$token='<=';
					break;
				case 'gt':
					$token='>';
					break;
				case 'ge':
				case 'gte':
					$token='>=';
					break;
				case 'and':
					$token='&&';
					break;
				case 'or':
					$token='||';
					break;
				case 'not':
					$token='!';
					break;
				case 'mod':
					$token='%';
					break;
				default:
					if ($token[0] == '$'){
						$token=$this->get_val(substr($token, 1));
					}
					break;
			}
		}
		if ($elseif){
			return '<?php elseif ('.implode(' ', $tokens).'): ?>';
		}else{
			return '<?php if ('.implode(' ', $tokens).'): ?>';
		}
	}
	private function make_foreach($tag_args){
		$attrs=$this->get_para($tag_args, 0);
		$arg_list=array();
		$from=$attrs['from'];
		if(isset($this->_var[$attrs['item']]) && !isset($this->_patchstack[$attrs['item']])){
			$this->_patchstack[$attrs['item']]=$attrs['item'].'_'.str_replace(array(' ', '.'), '_', microtime());
			$attrs['item']=$this->_patchstack[$attrs['item']];
		}else{
			$this->_patchstack[$attrs['item']]=$attrs['item'];
		}
		$item=$this->get_val($attrs['item']);

		if (!empty($attrs['key'])){
			$key=$attrs['key'];
			$key_part=$this->get_val($key).'=>';
		}else{
			$key=null;
			$key_part='';
		}

		if (!empty($attrs['name'])){
			$name=$attrs['name'];
		}else{
			$name=null;
		}

		$output='<?php ';
		$output .= "\$_from=$from; if (!is_array(\$_from) && !is_object(\$_from)) { settype(\$_from, 'array'); }; \$this->push_vars('\$attrs[key]', '\$attrs[item]');";
		if (!empty($name)){
			$foreach_props="\$this->_foreach['$name']";
			$output .= "{$foreach_props}=array('total'=>count(\$_from), 'iteration'=>0);\n";
			$output .= "if ({$foreach_props}['total'] > 0):\n";
			$output .= "    foreach (\$_from AS $key_part$item):\n";
			$output .= "        {$foreach_props}['iteration']++;\n";
		}else{
			$output .= "if (count(\$_from)):\n";
			$output .= "    foreach (\$_from AS $key_part$item):\n";
		}
		return $output.'?>';
	}
	private function make_loop($tag_args){
		$attrs=$this->get_loop_para($tag_args);
		$table=!empty($attrs['table'])?trim($attrs['table']):"";
		$where=!empty($attrs['where'])?trim($attrs['where']):"";
		$limit=!empty($attrs['limit'])?trim($attrs['limit']):"";
		$orderby=!empty($attrs['orderby'])?trim($attrs['orderby']):"";
		$ok= '$sql="SELECT * FROM '.DB_PREFIX.$table.' '.(!empty($where)?" WHERE $where":"").' '.(!empty($orderby)?" ORDER BY $orderby":"").' '.(!empty($limit)?" LIMIT 0,$limit":"").'";';
		$output='<?php ';
		$output .="\n";
		$output .= '$sql="SELECT * FROM '.DB_PREFIX.$table.' '.(!empty($where)?" WHERE $where":"").' '.(!empty($orderby)?" ORDER BY $orderby":"").' '.(!empty($limit)?" LIMIT 0,$limit":"").'";';

		$output .= "\n\$_result=\$this->db->result(\$sql);";	
		$output .= "if (count(\$_result)):\n";
		$output .= "    foreach (\$_result AS \$loop):";
 
		return $output.'?>';
	}
	function get_loop_para($html){
		@preg_match_all("|([^\"].*?)=\"([^\"].*?)\"|",trim($html), $back);
		$tmp=array();
		foreach ($back[1] as $key => $value) {
			$tmp[trim($key)]=strtolower(trim($value));
		}
		$macth=array_combine($tmp,$back[2]);
		return $macth;	
	}
	#将 foreach 的 key, item 放入临时数组
	private function push_vars($key, $val){
        if (!empty($key)){
			@array_push($this->_temp_key,"\$this->_var['$key']='" .$this->_var[$key] . "';");
        }
        if (!empty($val)){
            @array_push($this->_temp_val,"\$this->_var['$val']='" .$this->_var[$val] ."';");
        }
	}

	#弹出临时数组的最后一个
	private function pop_vars(){
		$key=array_pop($this->_temp_key);
		$val=array_pop($this->_temp_val);
		if (!empty($key)){
			eval($key);
		}
	}
	private function make_template(&$indexes){
		$_ref=$indexes[0];
		switch ($_ref){
			case 'foreach':
				array_shift($indexes);
				$_var=$indexes[0];
				$_propname=$indexes[1];
				switch ($_propname){
					case 'index':
						array_shift($indexes);
						$compiled_ref="(\$this->_foreach['$_var']['iteration'] - 1)";
						break;
					case 'first':
						array_shift($indexes);
						$compiled_ref="(\$this->_foreach['$_var']['iteration'] <= 1)";
						break;
					case 'last':
						array_shift($indexes);
						$compiled_ref="(\$this->_foreach['$_var']['iteration'] == \$this->_foreach['$_var']['total'])";
						break;
					case 'show':
						array_shift($indexes);
						$compiled_ref="(\$this->_foreach['$_var']['total'] > 0)";
						break;
					default:
						$compiled_ref="\$this->_foreach['$_var']";
						break;
				}
				break;
			case 'loop':
				$compiled_ref="\$$_var";
			case 'get':
				$compiled_ref='$_GET';
				break;
			case 'post':
				$compiled_ref='$_POST';
				break;
			case 'cookies':
				$compiled_ref='$_COOKIE';
				break;
			case 'server':
				$compiled_ref='$_SERVER';
				break;
			case 'request':
				$compiled_ref='$_REQUEST';
				break;
			case 'session':
				$compiled_ref='$_SESSION';
				break;
			default:
				break;
		}
		array_shift($indexes);
		return $compiled_ref;
	}
	private function make_array($arr){
		$out='';
		foreach ($arr AS $key=>$val){
			if ($val{0} == '$'){
				$out .= $out ? ",'$key'=>$val" : "array('$key'=>$val";
			}else{
				$out .= $out ? ",'$key'=>'$val'" : "array('$key'=>'$val'";
			}
		}
		return $out.')';
	}
	private function str_trim($str){
		while (strpos($str, '= ') != 0){
			$str=str_replace('= ', '=', $str);
		}
		while (strpos($str, ' =') != 0){
			$str=str_replace(' =', '=', $str);
		}
		return explode(' ', trim($str));
	}
	private function _eval($content){
		ob_start();
		eval('?'.'>'.trim($content));
		$content=ob_get_contents();
		ob_end_clean();
		return $content;
	}
	private function _require($filename){
		ob_start();
		include $filename;
		$content=ob_get_contents();
		ob_end_clean();
		return $content;
	}
	private function cycle($arr){
		static $k, $old;
		$value=explode(',', $arr['values']);
		if ($old != $value){
			$old=$value;
			$k=0;
		}else{
			$k++;
			if (!isset($old[$k])){
				$k=0;
			}
		}
		echo $old[$k];
	}
}