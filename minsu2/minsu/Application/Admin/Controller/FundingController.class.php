<?php 

namespace Admin\Controller;
use Think\Controller;


/**
* 众筹
*/
class FundingController extends AuthController
{
	

	public function index()
	{
		$result = M('funding')->select();
		$this->assign('result',$result);
		$this->assign('dataNum',sizeof($result));
		$this->display();
	}



	public function add()
	{
		if (IS_POST) {

			$info = $this->upload();
			if (!$info) {
                $this->error('上传失败');
            }

            $_POST['fun_list_pic'] = $info[0]['savepath'].$info[0]['savename'];

            $pic = '';
            foreach ($info as $k => $v) {
				if ($k != 0) {
					$pic .= $v['savepath'] . $v['savename'] . ',';
				}
			}
			$_POST['fun_pic'] = rtrim($pic, ",");

			$z = M('funding')->add($_POST);

			if ($z) {
				$this->success('添加成功',U('index'));
			}else{
				$this->error('添加失败');
			}

		}else{
			$this->display();
		}
		
	}


	//编辑信息
	public function edit($funid){
		$funid = I('get.funid','');
		if (IS_POST) {
			if ($_POST['t_pic'] && $_POST['c_pic']) {
				M('funding')->where("funid={$funid}")->save($_POST);
			}elseif(!$_POST['t_pic'] && $_POST['c_pic']){
				$info = $this->upload();
				$_POST['fun_list_pic'] = $info[0]['savepath'].$info[0]['savename'];
				M('funding')->where("funid={$funid}")->save($_POST);
			}elseif ($_POST['t_pic'] && !$_POST['c_pic']) {
				$info = $this->upload();
				$pic = '';
            	foreach ($info as $k => $v) {
					$pic .= $v['savepath'] . $v['savename'] . ',';
				}
				$_POST['fun_pic'] = rtrim($pic, ",");
				M('funding')->where("funid={$funid}")->save($_POST);
				
			}elseif (!$_POST['t_pic'] && !$_POST['c_pic']) {
				$info = $this->upload();

		        $_POST['fun_list_pic'] = $info[0]['savepath'].$info[0]['savename'];

		        $pic = '';
		        foreach ($info as $k => $v) {
					if ($k != 0) {
						$pic .= $v['savepath'] . $v['savename'] . ',';
					}
				}
				$_POST['fun_pic'] = rtrim($pic, ",");
				M('funding')->where("funid={$funid}")->save($_POST);
			}

			$this->success('编辑成功',U('index'));
			
		}else{
			
			$data = M('funding')->where("funid={$funid}")->find();
			$this->assign('data',$data);
			$this->assign('data_img',explode(',',$data['fun_pic']));
			
			$this->display();
		}
		
	}

	public function interestedList($funid){
		$result = M('fund_sign')->where("funid={$funid}")->select();
		$this->assign('result',$result);
		$this->assign('dataNum',sizeof($result));
		$this->display();
	}

	//删除方法
	public function del($funid)
	{
		$data = M('funding')->field('fun_list_pic,fun_pic')->where("funid={$funid}")->find();
		M('fund_sign')->where("funid=" . $funid)->delete();
		
		// 删除列表图
		$list_pic = 'Uploads'.$data['fun_list_pic'];
		if(is_file($list_pic)) unlink($list_pic);
		
		$house_pic = explode(',',$data['fun_pic']);
		foreach ($house_pic as $k => $v) {
			$img = 'Uploads'.$v;
			if(is_file($img)) unlink($img);
		}

		$z = M('funding')->where("funid={$funid}")->delete();
		if ($z) {
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}


	public function upload(){    
		$upload = new \Think\Upload();// 实例化上传类    
		$upload->maxSize   =     0 ;// 设置附件上传大小    
		$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型    
		$upload->savePath  =      './Public/Uploads/Funding/'; // 设置附件上传目录
		// 上传文件     
		$info   =   $upload->upload();    
		return $info; 
	}

	public function charge($funid) {
		$c = M('funding')->where("funid={$funid}")->setField('contact_charge',1);
		if ($c) {
			$this->success('查看联系方式收费成功');
		}else{
			$this->error('查看联系方式收费失败');
		}
	}
	
	public function uncharge($funid) {
		$c = M('funding')->where("funid={$funid}")->setField('contact_charge',0);
		if ($c) {
			$this->success('取消查看联系方式收费成功');
		}else{
			$this->error('取消查看联系方式收费失败');
		}
	}
	
	public function tag($hid, $tag) {
		$c = M('funding')->where("funid={$hid}")->setField('tag',$tag);
		if ($c) {
			$this->success('标记成功');
		}else{
			$this->error('标记失败');
		}
	}
}
 ?>