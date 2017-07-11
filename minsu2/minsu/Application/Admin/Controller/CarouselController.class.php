<?php 

namespace Admin\Controller;
use Think\Controller;

/**
*  轮播图管理
*/
class CarouselController extends AuthController
{
	//首页
	public function index()
	{
		$data = M('carousel')->order('carousel_sort desc')->select();
		$this->assign('data',$data);
		$this->display();
	}
	
	// 添加页面
	public function add()
	{
		if (IS_POST) {
			$info = $this->uploadOne();
			if (!$info) {
				$this->error('上传失败');
			}
			$_POST['carousel_img'] = $info['savepath'] . $info['savename'];
			$z = M('carousel')->add($_POST);
			if ($z) {
				$this->success('添加成功');
			}else{
				$this->error('添加失败');
			}
		}else{
			$this->display();
		}
	}

	public function edit($carid)
	{
		if (IS_POST) {
			$info = $this->uploadOne();
			if ($info) {
				$_POST['carousel_img'] = $info['savepath'] . $info['savename'];
				$z = M('carousel')->where("carid={$carid}")->save($_POST);
			}else{
				$z = M('carousel')->where("carid={$carid}")->save($_POST);
			}
			if ($z) {
				$this->success('编辑成功',U('index'));
			}else{
				$this->error('编辑失败');
			}

		}else{
			$data = M('carousel')->where("carid={$carid}")->find();
			$this->assign('data',$data);
			$this->display();
		}
	}

	public function del($carid)
	{
		$result = M('carousel')->field('carousel_img')->where("carid={$carid}")->find();
		$pic = 'Uploads'.$result['carousel_img'];
		if(is_file($pic)) unlink($pic);
		$z = M('carousel')->where("carid={$carid}")->delete();
		if ($z) {
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}

		//上传
	private function uploadOne(){    
		$upload = new \Think\Upload();// 实例化上传类    
		$upload->maxSize   =     0 ;// 设置附件上传大小    
		$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型    
		$upload->savePath  =      './Public/Uploads/Carousel/'; // 设置附件上传目录
		// 上传文件     
		$info   =   $upload->upload();    
		return $info['carousel_img']; 
	}
}

 ?>