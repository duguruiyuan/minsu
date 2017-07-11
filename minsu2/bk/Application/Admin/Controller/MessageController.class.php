<?php 

namespace Admin\Controller;
use Think\Controller;

/**
* 留言管理
*/
class MessageController extends AuthController
{
	
	public function index()
	{	
		$data = M('message')->order('meid desc')->select();
		$this->assign('data',$data);
		$this->display();
	}

	public function adopt($meid)
	{
		$z = M('message')->where("meid={$meid}")->save(array('is_adopt'=>1));
		if ($z) {
			$this->success('采纳成功');
		}else{
			$this->error('采纳失败');
		}
	}

	public function del($meid)
	{
		$z = M('message')->where("meid={$meid}")->delete();
		if ($z) {
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}


}


 ?>