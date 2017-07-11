<?php 

namespace Home\Controller;
use Think\Controller;

/**
* 填写房源
*/
class ReleaseController extends Controller
{
	
	public function index()
	{
		if (!$_SESSION['login_id'] && !$_SESSION['login_name']) {
			$this->redirect('Member/Login');
		}
		$this->display();
	}

	//添加房屋信息
	public function addHouse()
	{	
		if (IS_POST) {
			
			if ($_POST['s_county'] != '市、县级市') {
				$address = $_POST['s_province'] .','.$_POST['s_city'].','.$_POST['s_county'].','.$_POST['address'];
			}else{
				$address = $_POST['s_province'] . ',' . $_POST['s_city']. ',' .$_POST['address'];
			}
			$info = array(
				'address' => $address,
				'cont_name' => I('post.cont_name'),
				'contact' => I('post.contact'),
				'user_uid' => $_SESSION['login_id'],
			);
			$_SESSION['info'] = $info;

			$this->redirect('addHouseInfo');
		}else{
			$this->display();
		}
	}


	public function addHouseInfo()
	{
		if (IS_POST) {
			
			$info = $this->upload();
			$houseInfo = M('houseinfo');
			$userInfo = M('userinfo');
			//存入用户信息
			$uiid = $userInfo->add($_SESSION['info']);
			//保存时间
			$_POST['add_time'] = time();
			$_POST['address'] = $_SESSION['info']['address'];
			$_POST['list_pic'] = $info[0]['savepath'].$info[0]['savename'];
			$_POST['userinfo_uiid'] = $uiid;
			$z = $houseInfo->add($_POST);
			foreach ($info as $k => $v) {
				if ($k != 0) {
					$img = array(
						'pic' => $v['savepath'] . $v['savename'],
						'houseinfo_hid' => $z,
					);
					M('houseimg')->add($img);
				}
			}

			if ($z) {
				M('house_fee')->add(array('hid'=>$z, 'fee'=>$_POST['house_fee'], 'agent'=>$_SESSION['login_id']));
				$this->redirect('Release/success_house');
			}else{
				$this->error('发布失败');
			}
		}else{
			$this->display();
		}
		
	}

	//添加成功
	public function success_house()
	{
		$this->display();
	}
	//发布失败
	public function error_house()
	{
		$this->display();
	}

	public function upload(){    
		$upload = new \Think\Upload();// 实例化上传类    
		$upload->maxSize   =     0 ;// 设置附件上传大小    
		$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型    
		$upload->savePath  =      './Public/Uploads/House/'; // 设置附件上传目录
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