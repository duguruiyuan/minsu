<?php 

namespace Home\Controller;
use Think\Controller;

/**
* 详情控制器
*/
class GuestController extends Controller {
	public function index($id){
		$houseImg = M('houseimg')->WHERE("houseinfo_hid={$id}")->select();
		$this->assign('houseImg',$houseImg);
		$this->assign('total',count($houseImg));
		$today = date("Y-m-d");
		$click = M('house_click')->WHERE("hid={$id} AND date='{$today}'")->find();
		if($click['click']) {
			M('house_click')->save(array('click'=>$click['click']+1));
		} else {
			M('house_click')->add(array('hid'=>$id,'date'=>$today,'click'=>1));
		}
		$result =M('houseinfo')->where("id={$id}")->find();
		$this->assign('result',$result);
		$this->assign('ad',M('ad')->find());
		$this->display();
	}
	
	public function home() {
		$gid = session('gid');
		if(!$gid) {
			$openid=cookie('oid');
			if($openid) {
				$guest=M('guest')->where("openid='{$openid}'")->find();
				$gid=$guest['id'];
				session('gid',$gid);
			}
		}
		if($gid) {
			$guest=M('guest')->WHERE("id=${gid}")->find();
			$sql="SELECT t.hid as hid, t.id as id, h.name as name,t.r_fee as r_fee,t.end_time as end_time,h.list_pic as list_pic,t.status as status FROM bk_houseinfo AS h INNER JOIN bk_h_time_queue AS t ON h.id=t.hid WHERE t.r_id={$gid} ORDER BY t.id DESC";
			$data=M()->query($sql);
			foreach ($data as $key => $value) {
				if($value['status']==1 && strtotime($value['end_time'])<time()) {
					$this_id=$value['id'];
					M('h_time_queue')->WHERE("id={$this_id}")->setField('status',7);
					$value['status']=7;
				}
			}
			$this->assign('guest',$guest);
			$this->assign('data',$data);
		}
		$this->display();
	}
	
	public function comment($qid) {
		$this->assign('qid',$qid);
		$this->display();
	}
	
	public function ajaxComment() {
		if(!IS_AJAX) {
			$data['status'] = 'no';
		} else {
			$qid=$_POST['qid'];
			$house = M('h_time_queue')->WHERE("id={$qid}")->find();
			$gid = session('gid');
			if ($house && $gid) {
				$hid=$house['hid'];
				$_POST['hid']=$hid;
				$z = M('h_comment')->add($_POST);
				M('h_time_queue')->WHERE("id={$qid}")->setField('status',8);
				$sql="SELECT sum(rate)/count(qid) as a FROM bk_h_comment WHERE hid={$hid}";
				$data=M()->query($sql);
				$data = $data[0];
				M('houseinfo')->WHERE("id={$hid}")->setField('score', $data['a']);
				M('guest')->WHERE("id=${gid}")->setInc('score',$house['r_fee']);
			}
			$data['status'] = 'ok';
		}
		$this->ajaxReturn($data);
	}
	
	public function refund($qid) {
		$this->assign('qid',$qid);
		$this->display();
	}
	
	public function ajaxRefund() {
		if(!IS_AJAX) {
			$data['status'] = 'no';
		} else {
			$house = M('h_time_queue')->WHERE('id='.$_POST['qid'])->find();
			if ($house['status']==1) {
				$house['status']=3;
				$house['r_reason']=$_POST['reason'];
				
			}
			$data['status'] = 'ok';
		}
		$this->ajaxReturn($data);
	}

	//用户注册
	public function register() {
		$this->display();
	}

	public function login() {
		$this->display();
	}


	public function mlogin() {
		$this->display();
	}
	
	public function fpwd() {
		$this->display();
	}
	
	/** 
	 *  
	 * 验证码生成 
	 */  
	public function verify_c(){  
	    $Verify = new \Think\Verify();  
	    $Verify->fontSize = 18;  
	    $Verify->length   = 4;  
	    $Verify->useNoise = false;  
	    $Verify->codeSet = '0123456789';  
	    $Verify->imageW = 130;  
	    $Verify->imageH = 50;  
	    //$Verify->expire = 600;  
	    $Verify->entry();  
	}  

	
	//用户验证
	public function ajaxRegister() {
		$user = M('guest');
		$pass = I('post.pwd');
		$name = I('post.name');
		$phone = I('post.phone');
		$openid = I('post.openid');
		$password = md5($pass);
		$us = $user->where("phone='{$phone}'")->find();
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
		if (strlen($phone)<8 || strlen($phone)>11) {
			$data['status'] = 4;
			$this->ajaxReturn($data);
		}
		//注册时间
		$userData = array(
			'pwd' => $password,
			'name' => $name,
			'phone' => $phone,
			'createtime' => date('Y-m-d H:i:s'),
			'openid'=>$openid,
			'last_ip'=>getIP()
		);

		$z = $user->add($userData);
		if ($z) {
			$_SESSION['gid'] = $z;
			$data['status'] = 'ok';
			if($_SESSION['redirect']) {
				$data['redirect']=$_SESSION['redirect'];
			} else {
				$data['redirect']='http://mei.vshijie.cn/bk/index.php/Home/Guest/home';
			}
			$this->ajaxReturn($data);
		}else{
			$data['status'] = 'no';
			$this->ajaxReturn($data);
		}
	}

	//登录验证
	public function ajaxLogin() {
		if(!IS_AJAX) return;
		$user = M('guest');
		$phone = I('post.phone');
		$pass = I('post.pwd');
		$openid = I('post.openid');
		$password = md5($pass);

		$result = $user->where("phone='{$phone}'")->find();
		//用户或者密码不正确
		if(!$result){
			$data['status'] = 1;
			$this->ajaxReturn($data);
		}
		//用户或者密码不正确
		if($result['pwd'] != $password){
			$data['status'] = 2;
			$this->ajaxReturn($data);
		}
		if($openid!=null && strlen($result['openid'])<12) {
			$result['openid']=$openid;
			$user->where("id={$result['id']}")->save($result);
		}
		$_SESSION['gid'] = $result['id'];
		setcookie(session_name(),session_id(),time() + 3600*24*700,'/');
		$data['status'] = 'ok';
		if($_SESSION['redirect']) {
			$data['redirect']=$_SESSION['redirect'];
		} else {
			$data['redirect']='http://mei.vshijie.cn/bk/index.php/Home/Guest/home';
		}
		$this->ajaxReturn($data);
	}
	
	//登录验证
	public function ajaxCaptcha() {
		$phone = I('post.phone');
		$captcha = I('post.captcha');
		$user = M('guest');
		$result = $user->where("phone='{$phone}'")->find();
		//用户或者密码不正确
		if(!$result){
			$data['status'] = 1;
			$this->ajaxReturn($data);
		}
		$verify = new \Think\Verify();  
		//用户或者密码不正确
		if(!$verify->check($captcha)){
			$data['status'] = 2;
			$this->ajaxReturn($data);
		}
		$code = randomStr6();
		$text="【自在乡居】您的验证码是".$code;
		sendText($phone,$text);
		$_SESSION['mcode']=$code;
		$data['status'] = 'ok';
		$this->ajaxReturn($data);
	}


	//登录验证
	public function ajaxMlogin() {
		$user = M('guest');
		$phone = I('post.phone');
		$pass = I('post.pwd');
		$openid = I('post.openid');
		$result = $user->where("phone='{$phone}'")->find();
		//用户或者密码不正确
		if(!$result){
			$data['status'] = 1;
			$this->ajaxReturn($data);
		}

		//用户或者密码不正确
		if($_SESSION['mcode'] != $pass){
			$data['status'] = 2;
			$this->ajaxReturn($data);
		}
		if($openid!=null && strlen($result['openid'])<12) {
			$result['openid']=$openid;
			$user->where("id={$result['id']}")->save($result);
		}
		$_SESSION['gid'] = $result['id'];
		setcookie(session_name(),session_id(),time() + 3600*24*700,'/');
		$data['status'] = 'ok';
		if($_SESSION['redirect']) {
			$data['redirect']=$_SESSION['redirect'];
		} else {
			$data['redirect']='http://mei.vshijie.cn/bk/index.php/Home/Guest/home';
		}
		$this->ajaxReturn($data);
	}
	
	public function clear() {
		unset($_SESSION);
	}
	
	public function ajaxFpwd() {
		$user = M('guest');
		$phone = I('post.phone');
		$pass = I('post.pwd');
		$result = $user->where("phone='{$phone}'")->find();
		//用户或者密码不正确
		if(!$result){
			$data['status'] = 1;
			$this->ajaxReturn($data);
		}
		$password = md5($pass);
		$result['pwd'] = $password;
		$user->where("id={$result['id']}")->save($result);
		$data['status'] = 'ok';
		$this->ajaxReturn($data);
	}
}
 ?>
