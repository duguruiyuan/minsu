<?php 

namespace Admin\Controller;
use Think\Controller;

/**
* 关于公司
*/
class AboutController extends AuthController
{
	
	public function index()
	{
		if (IS_POST) {
			if ($_POST['aboutid']) {
				M('about')->where("aboutid={$_POST['aboutid']}")->save($_POST);
			}else{
				M('about')->add($_POST);
			}
			$this->success('编辑成功');
			
		}else{
			$data = M('about')->find();
			$this->assign('data',$data);
			$this->display();
		}
		
	}
}



 ?>