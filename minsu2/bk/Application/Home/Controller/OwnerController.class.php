<?php 

namespace Home\Controller;
use Think\Controller;

/**
* 个人中心管理
*/
class OwnerController extends Controller {
	
	//个人中心首页
	public function index() {

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
		$data = array(
			'last_ip' => $ip
		);
		$uid = session('owner_id');
		M('owner')->where("id={$uid}")->save($data);
		session(NULL);
		$this->redirect('Index/index');

	}

	//用户验证
	public function ajaxRegister() {
		if(!IS_AJAX) return;
		$user = M('owner');
		$pass = I('post.pwd');
		$nickname = I('post.nickname');
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
			'nickname' => $nickname,
			'phone' => $phone,
			'createtime' => date('Y-m-d H:i:s'),
			'openid'=>$openid,
			'last_ip'=>getIP()
		);

		$z = $user->add($userData);
		if ($z) {
			$_SESSION['owner_id'] = $z;
			$_SESSION['owner_name'] = $nickname;
			$data['status'] = 'ok';
			$this->ajaxReturn($data);
		}else{
			$data['status'] = 'no';
			$this->ajaxReturn($data);
		}
	}

	//登录验证
	public function ajaxLogin() {
		if(!IS_AJAX) return;
		$user = M('owner');
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
		$_SESSION['owner_id'] = $result['id'];
		$_SESSION['owner_name'] = $result['nickname'];
		setcookie(session_name(),session_id(),time() + 3600*24*700,'/');
		$data['status'] = 'ok';
		$this->ajaxReturn($data);

	}

	public function houseInfo()
	{
		$uid = $_SESSION['owner_id'];

		$data = M('houseinfo')
			->where("uid={$uid}")
			->select();

		$this->assign('data',$data);

		$this->display();
	}

	//修改房屋
	public function editHouse($id) {
		$houseinfo = M('houseinfo');
		$houseimg = M('houseimg');
		if (IS_POST) {
			$hidAll = $houseimg->field('hiid')->where("houseinfo_hid={$id}")->select();
			//重组数组 取出id
			$temp = array();
			foreach ($hidAll as $k => $v) {
				$temp[] = $v['hiid'];
			}
			//获得用户不需要修改的图片 并且取出id
			$img_id = array();
			foreach ($_POST['temp_img'] as $k => $v) {
				$hiid = $houseimg->field('hiid')->where("pic='{$v}'")->find();
				$img_id[] = $hiid['hiid'];
			}
			$_POST['list_pic'] = $_POST['temp_img'][0];
			// 取出用户删除的图片的id 并执行删除方法
			$in = array_diff($temp, $img_id);
			if ($in) {
				$houseimg->where(array('hiid'=>array('IN',$in)))->delete();
			}
			//上传图片
			$info = $this->upload();
			if ($info) {
				foreach ($info as $k => $v) {
					$img = array(
						'pic' => $v['savepath'] . $v['savename'],
						'houseinfo_hid' => $id
					);
					$houseimg->add($img);
				}
				$_POST['list_pic'] = $info[0]['savepath'].$info[0]['savename'];
			}
			$_POST['uid']=$_SESSION['owner_id'];
			$houseinfo->where("id={$id}")->save($_POST);
			$this->success('编辑成功',U('index'));
		}else{
			$result = $houseinfo->where("id={$id}")->find();
			$result_img = $houseimg->where("houseinfo_hid={$id}")->select();
			$this->assign('result_img',$result_img);
			$this->assign('result',$result);
			$this->display();
		}
	}

	public function editStory() {
		$id=$_SESSION['owner_id'];
		if (IS_POST) {
			M('owner')->where("id={$id}")->setField('stitle', $_POST['stitle']);
			M('owner')->where("id={$id}")->setField('story', $_POST['story']);
			$this->success('修改房主故事成功',U('index'));
		} else {
			$data=M('owner')->where("id={$id}")->find();
			$this->assign('data',$data);
			$this->display();
		}
	}
	
	//添加房屋信息
	public function addHouse() {
		if (IS_POST) {
			$info = $this->upload();
			if (!$info) {
                $this->error('上传失败');
            }
			$houseInfo = M('houseinfo');
			$houseImg = M('houseimg');
			$_POST['list_pic'] = $info[0]['savepath'].$info[0]['savename'];
			$_POST['createtime'] = date('Y-m-d H:i:s');
			$_POST['uid']=$_SESSION['owner_id'];
			//存入房屋信息
			$hiid = $houseInfo->add($_POST);
			foreach ($info as $k => $v) {
				$img = array(
					'pic' => $v['savepath'] . $v['savename'],
					'houseinfo_hid' => $hiid,
				);
				$houseImg->add($img);
			}
			if ($hiid) {
				$this->success('添加成功',U('index'));
			}else{
				$this->error('添加失败');
			}
		}else{
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

	public function delHouse($id) {
		M('houseinfo')->WHERE('id='.$id)->delete();
		$this->redirect('houseInfo');
	}
	
	public function house() {
		$uid=$_SESSION['owner_id'];
		$result = M('houseinfo')->WHERE("uid=${uid}")->order('id desc')->select();
		$this->assign('data',$result);
		$this->display();
	}

	public function order() {
		$uid=$_SESSION['owner_id'];
		$sql="SELECT t.hid as hid, t.id as id, h.name as name,t.r_fee as r_fee,t.end_time as end_time," .
				"h.list_pic as list_pic,t.status as status,t.begin_time as begin_time, t.updatetime as updatetime," .
				"t.r_name as r_name, t.r_phone as r_phone, t.createtime as createtime " .
				"FROM bk_houseinfo AS h INNER JOIN bk_h_time_queue AS t ON h.id=t.hid WHERE h.uid={$uid} AND t.status>0 AND r_fee is not null ORDER BY t.id DESC";
		$data=M()->query($sql);
		foreach ($data as $key => $value) {
			if($value['status']==1 && strtotime($value['end_time'])<time()) {
				$this_id=$value['id'];
				$line = M('h_time_queue')->WHERE("id={$this_id}")->find();
				M('h_time_queue')->WHERE("id={$this_id}")->setField('status',7);
				$value['status']=7;
				$gid=$line['r_id'];
				if($gid) {
					M('guest')->WHERE("id=${gid}")->setField("score",$guest['score']+$value['r_fee']);
				}
			}
		}
		$this->assign('data',$data);
		$this->display();
	}
	
	public function queue() {
		$uid=$_SESSION['owner_id'];
		$sql="SELECT t.hid as hid, t.id as id, h.name as name,t.r_fee as r_fee,t.end_time as end_time," .
				"h.list_pic as list_pic,t.status as status,t.begin_time as begin_time, t.updatetime as updatetime," .
				"t.r_name as r_name, t.r_phone as r_phone, t.createtime as createtime, t.amount as amount " .
				"FROM bk_houseinfo AS h INNER JOIN bk_h_time_queue AS t ON h.id=t.hid WHERE h.uid={$uid} AND t.status>0 AND r_fee is null ORDER BY t.id DESC";
		$data=M()->query($sql);
		foreach ($data as $key => $value) {
			if($value['status']==1 && strtotime($value['end_time'])<time()) {
				$this_id=$value['id'];
				$line = M('h_time_queue')->WHERE("id={$this_id}")-find();
				M('h_time_queue')->WHERE("id={$this_id}")->setField('status',7);
				$value['status']=7;
				$gid=$line['r_id'];
				if($gid) {
					M('guest')->WHERE("id=${gid}")->setField("score",$guest['score']+$value['r_fee']);
				}
			}
		}
		$this->assign('data',$data);
		$this->display();
	}
	
		//添加房屋信息
	public function addQueue() {
		$uid=$_SESSION['owner_id'];
		if (IS_POST) {
			$hid = $_POST['hid'];
			$_POST['status'] = 2;
			$beginTime =$_POST['begin_time'];
			$endTime = $_POST['end_time'];
			$maxUsed = M('h_time')->FIELD('MAX(used) as used')->WHERE("date>='{$beginTime}' AND date<='{$endTime}' AND hid={$hid}")->find();
			if(is_null($maxUsed['used']) || $maxUsed['used']<$houseinfo['total']) {
				$_POST['createtime'] = date('Y-m-d H:i:s');
				$id=M('h_time_queue')->add($_POST);
				$begin_time = strtotime(date($beginTime));
				$end_time = strtotime(date($endTime));
				while($begin_time<=$end_time) {
					$begin=date('Y-m-d',$begin_time);
					$sql="insert into bk_h_time (hid,date)values({$hid},'{$begin}') ON DUPLICATE KEY UPDATE used=used+1";
					M()->execute($sql);
					$begin_time=$begin_time+86400;
				}
				if ($id) {
					$data['status']=1;
				}else{
					$data['status']=2;
				}
				$this->ajaxReturn($data);
			}
		}else{
			$result = M('houseinfo')->WHERE("uid=${uid}")->order('id desc')->select();
			$this->assign('house',$result);
			$this->display();
		}
	}

	//删除方法
	public function delQueue($id) {
		$data = M('h_time_queue')->WHERE("id={$id}")->find();
		$beginTime=$data['begin_time'];
		$endTime=$data['end_time'];
		$hid=$data['hid'];
		$begin_time = strtotime(date($beginTime));
		$end_time = strtotime(date($endTime));
		while($begin_time<=$end_time) {
			$begin=date('Y-m-d',$begin_time);
			M('h_time')->WHERE("hid={$hid} AND date={$begin}")->setInc("used",-1);
			$begin_time=$begin_time+86400;
		}
		$z = M('h_time_queue')->where("id={$id}")->delete();
		if ($z) {
			$this->success('恢复成功');
		}else{
			$this->error('恢复失败');
		}
	}
	
	public function balance() {
		$uid=$_SESSION['owner_id'];
		$totalSql="SELECT sum(r_fee) as f FROM bk_h_time_queue WHERE end_time<'".date('Y-m-d')."' AND (status=1 OR status=7 OR status=8) AND hid IN(SELECT id FROM bk_houseinfo WHERE uid={$uid})";
		$total=M()->query($totalSql);
		$getatableSql="SELECT sum(r_fee) as f FROM bk_h_time_queue WHERE end_time<'".date('Y-m-d')."' AND (status=1 OR status=7 OR status=8) AND hid IN(SELECT id FROM bk_houseinfo WHERE uid={$uid}) AND is_withdraw=0";
		$getatable=M()->query($getatableSql);
		$this->assign('total',$total);
		$this->assign('getatable',$getatable);
		$this->display();
	}
		
	public function withdraw() {
		if (IS_POST) {
			$uid=$_SESSION['owner_id'];
			$getatableSql="SELECT id,r_fee FROM bk_h_time_queue WHERE end_time<'".date('Y-m-d')."' AND (status=1 OR status=7 OR status=8) AND hid IN(SELECT id FROM bk_houseinfo WHERE uid={$uid}) AND is_withdraw=0";
			$getatable=M()->query($getatableSql);
			$amount=0;
			foreach ($getatable as $key => $value) {
				$amount=$amount+$value['r_fee'];
				M('h_time_queue')->WHERE("id=".$value['id'])->setField('is_withdraw',1);
			}
			if($amount>0) {
				$_POST['amount']=$amount;
				$_POST['createtime'] = date('Y-m-d H:i:s');
				$_POST['uid']=$uid;
				 M('withdraw')->add($_POST);
				 $this->success('提现成功',U('index'));
			}
		}
		$this->error('提现失败',U('index'));
	}
}
 ?>