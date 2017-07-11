<?php 

namespace Admin\Controller;
use Think\Controller;

/**
* 关于公司
*/
class EntrustController extends AuthController
{
	
	public function index()
	{
		if (IS_POST) {
			if ($_POST['entrust_id']) {
				M('entrust')->where("entrust_id={$_POST['entrust_id']}")->save($_POST);
			}else{
				M('entrust')->add($_POST);
			}
			$this->success('编辑成功');
			
		}else{
			$data = M('entrust')->find();
			$this->assign('data',$data);
			$this->display();
		}
		
	}
}



 ?>