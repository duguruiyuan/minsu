<?php 

namespace Home\Controller;
use Think\Controller;


/**
* 经纪人控制器
*/
class AgentController extends Controller
{
	
	//载入首页
	public function index()
	{


		$data = M('agent')->where('ag_audit=1')->select();
		$this->assign('data',$data);
		$this->display();
	}

	public function add()
	{
		if (IS_POST) {
			$info = $this->upload();

			$_POST['ag_pic'] = $info['ag_pic']['savepath'].$info['ag_pic']['savename'];

			$z = M('agent')->add($_POST);
			if ($z) {
				$this->redirect('Release/success_house');
			}else{
				$this->redirect('Release/error_house');
			}

		}else{
			$Agentdata = M('about')->find();
			$this->assign('Agentdata',$Agentdata);
			$this->display();
		}
		
	}


	public function upload(){    
		$upload = new \Think\Upload();// 实例化上传类    
		$upload->maxSize   =     0 ;// 设置附件上传大小    
		$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型    
		$upload->savePath  =      './Public/Uploads/Agent/'; // 设置附件上传目录
		// 上传文件     
		$info   =   $upload->upload();    
		if(!$info) {// 上传错误提示错误信息  
			$this->error($upload->getError());   
		 }else{// 上传成功       
			return $info; 
		};
	}


}

 ?>