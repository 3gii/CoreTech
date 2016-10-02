<?php 
//示例
class Tech extends Core{
  public function index()
	{
	  $s="hello world";
	  $arr=array(1,2,3,4,5,6);
	  $user=array(
		  'user1'=>
		  array('id'=>'2011','name'=>'tom','age'=>'65'),
		  'user2'=>
		  array('id'=>'2014','name'=>'sun','age'=>'55'),
		  'user3'=>
		  array('id'=>'2013','name'=>'jam','age'=>'45')
		  );
	  $this->template->in('title','示例页面');
	  $this->template->in('s',$s);
	  $this->template->in('arr',$arr);
	  $this->template->in('User',$user);
	  $this->template->out('Tech.php');




    }
}
?> 