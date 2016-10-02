<?php
# ================================================================
# 程序数据缓存类，数据查询结果缓存
# @E-mail:3gii@sina.cn
# ================================================================
class dbCache {
	private $dir;
	public function  __construct(){
		$this->dir=ROOT.'upload/cache/';
	}
    public function read($key,$minutes=1800){
    	$filename=$this->filename($key);
    	if($datas = @file_get_contents($filename)){
    		$datas = unserialize($datas);
    		if(time() - $datas['time'] < $minutes*60){
    			return $datas['data'];
    		}
    	}
    	return false;
    }
    public function write($key,$data){
    	$filename=$this->filename($key);
    	if($handle = fopen($filename,'w+')){
    		$datas = array('data'=>$data,'time'=>time());
    		flock($handle,LOCK_EX);
    		$rs = fputs($handle,serialize($datas));
    		flock($handle,LOCK_UN);
    		fclose($handle);
    		if($rs!==false)return true;
    	}
    	return false;
    }
    public function delete_cache($key){
    	$filename=$this->filename($key);
    	if(file_exists($filename))@unlink($filename);
    }
    private function filename($key){
    	return $this->dir.$key.'_'.md5($key);
    }
    public function clear(){
        $file = glob($this->dir.'*');
        $result = array_map("unlink",$file);
        if($result !== false){
            return true;
        }else{
            exit('error');
            return false;
        }
    }
}
?>