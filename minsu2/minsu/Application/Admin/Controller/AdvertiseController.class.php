<?php 

namespace Admin\Controller;
use Think\Controller;

/**
*  轮播图管理
*/
class AdvertiseController extends AuthController
{
	//首页
	public function index()
	{
		$data = M('ad')->select();
		$this->assign('data',$data);
		$this->display();
	}
	
	// 添加页面
	public function add()
	{
		if (IS_POST) {
			$info = $this->upload();
			if (!$info) {
				$this->error('上传失败');
			}
			$_POST['pic'] = $info['pic']['savepath'].$info['pic']['savename'];
			$z = M('ad')->add($_POST);
			if ($z) {
				$this->success('添加成功');
			}else{
				$this->error('添加失败');
			}
		}else{
			$this->display();
		}
	}

	public function edit($id)
	{
		if (IS_POST) {
			$info = $this->upload();
			if ($info) {
				$_POST['pic'] = $info['pic']['savepath'].$info['pic']['savename'];
				$z = M('ad')->where("id={$id}")->save($_POST);
			}else{
				$z = M('ad')->where("id={$id}")->setField('link',$_POST['link']);
			}
			if ($z) {
				$this->success('编辑成功',U('index'));
			}else{
				$this->error('编辑失败');
			}

		}else{
			$data = M('ad')->where("id={$id}")->find();
			$this->assign('data',$data);
			$this->display();
		}
	}

	public function del($id)
	{
		$result = M('ad')->field('pic')->where("id={$id}")->find();
		$pic = 'Uploads'.$result['pic'];
		if(is_file($pic)) unlink($pic);
		$z = M('ad')->where("id={$id}")->delete();
		if ($z) {
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}

	//上传
	private function upload(){    
		$upload = new \Think\Upload();// 实例化上传类    
		$upload->maxSize   =     0 ;// 设置附件上传大小    
		$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型    
		$upload->savePath  =      './Public/Uploads/Ad/'; // 设置附件上传目录
		// 上传文件     
		$info   =   $upload->upload();   
		return $info; 
	}
}
 ?>