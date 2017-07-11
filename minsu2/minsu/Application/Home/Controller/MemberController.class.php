<?php 

namespace Home\Controller;
use Think\Controller;

/**
* 个人中心管理
*/
class MemberController extends Controller
{
	
	//个人中心首页
	public function index()
	{	

		$this->display();
	}

	//用户注册
	public function register()
	{
		$this->display();
	}

	public function login()
	{
		$this->display();
	}

	//用户退出
	public function out()
	{
		$ip = $_SERVER["REMOTE_ADDR"];
		$time = time();
		$data = array(
			'last_time' => $time,
			'last_ip' => $ip
		);
		$uid = session('login_id');
		M('user')->where("uid={$uid}")->save($data);
		session(NULL);
		$this->redirect('Index/index');

	}

	//用户验证
	public function ajaxRegister() {
		if(!IS_AJAX) return;
		$user = M('user');
		$username = I('post.username');
		$pass = I('post.password');
		$nikename = I('post.nikename');
		$phone = I('post.phone');
		$address = I('post.address');
		$openid = I('post.openid');
		$password = md5($pass);

		$us = $user->where("username='{$username}' OR openid='{$openid}'")->find();
		//如果用户存在
		if ($us) {
			$data['status'] = 1;
			$this->ajaxReturn($data);
		}
		//如果字符长度小于6位或者大于15位
		if (strlen($pass)<6 || strlen($pass)>15) {
			$data['status'] = 2;
			$this->ajaxReturn($data);
		}
		//如果账号长度小于6位或者大于15位
		if (strlen($username)<6 || strlen($username)>15) {
			$data['status'] = 4;
			$this->ajaxReturn($data);
		}
		//注册时间
		$register_time = time();
		if(is_null($openid)) {
			$openid=$register_time;
		}
		$userData = array(
			'username' => $username,
			'password' => $password,
			'nikename' => $nikename,
			'phone' => $phone,
			'address' => $address,
			'register_time' => $register_time,
			'last_time' => $register_time,
			'last_ip' => $_SERVER["REMOTE_ADDR"],
			'createtime'=>date('Y-m-d H:i:s'),
			openid=>$openid
		);

		$z = $user->add($userData);
		if ($z) {
			$_SESSION['login_id'] = $z;
			$_SESSION['login_name'] = $username;
			$data['status'] = 'ok';
			$this->ajaxReturn($data);
		}else{
			$data['status'] = 'no';
			$this->ajaxReturn($data);
		}

	}

	//登录验证
	public function ajaxLogin()
	{
		if(!IS_AJAX) return;
		$user = M('user');
		$username = I('post.username');
		$pass = I('post.password');
		$openid = I('post.openid');
		$password = md5($pass);

		$result = $user->where("username='{$username}' OR phone='{$username}'")->find();
		//用户或者密码不正确
		if(!$result){
			$data['status'] = 1;
			$this->ajaxReturn($data);
		}
		//用户或者密码不正确
		if($result['password'] != $password){
			$data['status'] = 2;
			$this->ajaxReturn($data);
		}
		//用户或者密码不正确
		if($result['is_limit']==1){
			$data['status'] = 3;
			$this->ajaxReturn($data);
		}
		if($openid!=null && strlen($result['openid'])<12) {
			$result['openid']=$openid;
		}
		$result['last_time'] = time();
		$result['last_ip'] = $_SERVER["REMOTE_ADDR"];
		$user->where("uid={$result['uid']}")->save($result);
		$_SESSION['login_id'] = $result['uid'];
		$_SESSION['login_name'] = $username;
		setcookie(session_name(),session_id(),time() + 3600*24*7,'/');
		$data['status'] = 'ok';
		$this->ajaxReturn($data);

	}

	public function houseInfo() {
		$uid = $_SESSION['login_id'];

		$data = M('houseinfo')
			->join('ms_userinfo ON ms_userinfo.uiid = ms_houseinfo.userinfo_uiid')
			->where("user_uid={$uid}")
			->select();

		$this->assign('data',$data);

		$this->display();
	}

	//修改房屋
	public function editHouse()
	{
		
		if(IS_POST){
			$hid = I('post.hid',0);
			//上传图片
			$hidAll = M('houseimg')->field('hiid')->where("houseinfo_hid={$hid}")->select();
			//重组数组 取出id
			$temp = array();
			foreach ($hidAll as $k => $v) {
				$temp[] = $v['hiid'];
			}
			//获得用户不需要修改的图片 并且取出id
			$img_id = array();
			foreach ($_POST['temp_img'] as $k => $v) {
				$hiid = M('houseimg')->field('hiid')->where("pic='{$v}'")->find();
				$img_id[] = $hiid['hiid'];
			}
			$_POST['list_pic'] = $_POST['temp_img'][0];
			// 取出用户删除的图片的id 并执行删除方法
			$in = array_diff($temp, $img_id);
			if ($in) {
				M('houseimg')->where(array('hiid'=>array('IN',$in)))->delete();
			}
			//上传图片
			$info = $this->upload();
			if ($info) {
				foreach ($info as $k => $v) {
					$img = array(
						'pic' => $v['savepath'] . $v['savename'],
						'houseinfo_hid' => $hid
					);
					M('houseimg')->add($img);
				}
				$_POST['list_pic'] = $info[0]['savepath'].$info[0]['savename'];
			}
			//组合地址
			if ($_POST['s_county'] != '市、县级市') {
				$_POST['address'] = $_POST['s_province'] . ',' . $_POST['s_city'] .','.$_POST['s_county'].','.$_POST['address'];
			}else{
				$_POST['address'] = $_POST['s_province'] . ',' . $_POST['s_city']. ',' .$_POST['address'];
			}
			
			M('houseinfo')->where("hid={$hid}")->save($_POST);
			$fee=M('house_fee')->field('fee')->where('hid='.$hid)->find();
			$hfeeInfo = array('hid'=>$hid, 'fee'=>$_POST['house_fee'], 'ratio'=>$_POST['house_ratio'],'agent'=>$_SESSION['login_id']);
			if(is_null($fee['fee'])) {
				M('house_fee')->add($hfeeInfo);
			} else {
				M('house_fee')->where("hid={$hid}")->save($hfeeInfo);
			}
			$this->redirect('houseInfo');

		}else{
			$hid = I('get.hid',0,'int');
			$houseinfo = M('houseinfo')
				->join('ms_userinfo ON ms_userinfo.uiid = ms_houseinfo.userinfo_uiid')
				->where("hid={$hid}")
				->find();
			$address = explode(',',$houseinfo['address']);
			$this->assign('address',$address);
			$this->assign('houseinfo',$houseinfo);
			$fee=M('house_fee')->field('fee')->where('hid='.$hid)->find();
			if(!$fee['fee']) {
				$fee['fee']=0;
			}
			$this->assign('house_fee',$fee['fee']);
			$result_img = M('houseimg')->where("houseinfo_hid={$hid}")->select();
			$this->assign('result_img',$result_img);
			$this->display();
		}
	}

	public function upload(){    
		$upload = new \Think\Upload();// 实例化上传类    
		$upload->maxSize   =     0 ;// 设置附件上传大小    
		$upload->exts      =     array('jpg', 'gif', 'png', 'jpeg');// 设置附件上传类型    
		$upload->savePath  =      './Public/Uploads/House/'; // 设置附件上传目录
		// 上传文件     
		$info   =   $upload->upload();    
		return $info; 
	}

	public function delHouse($hid) {
		M('houseinfo')->WHERE('hid='.$hid)->delete();
		M('house_fee')->WHERE('hid='.$hid)->delete();
		$this->redirect('houseInfo');
	}
		
	public function appointment() {
		$uid=$_SESSION['login_id'];
		if($uid) {
			$data=M('house_sign')->WHERE('agent='.$uid)->select();
			$this->assign('data',$data);
		}
		$this->display();
	}
			
	public function feedback() {
		$uid=$_SESSION['login_id'];
		if($uid) {
			$sql="SELECT c.hid,c.rate,c.comment,c.updatetime,s.name,s.mail,s.phone FROM ms_house_comment as c left join ms_house_sign as s ON c.hid=s.hid AND c.openid=s.openid where c.agent={$uid}";
			$data=M()->query($sql);
			$this->assign('data',$data);
		}
		$this->display();
	}
}
 ?>