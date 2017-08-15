<?php 

namespace Admin\Controller;
use Think\Controller;

/**
* 房屋管理控制器
*/
class HouseController extends AuthController
{
	//房屋添加首页
	public function index()
	{	
		$result = M('houseinfo')
					->JOIN("LEFT JOIN ms_userinfo ON ms_houseinfo.userinfo_uiid=ms_userinfo.uiid")
					->order('add_time desc')
					->SELECT();
		$this->assign('result',$result);
		$this->display();
	}

	//添加房屋信息
	public function addHouseInfo()
	{
		if (IS_POST) {
			if($_POST['s_province']=='省份') {
				$this->error('请选择省份');
			}
			if($_POST['s_city']=='地级市') {
				$this->error('请选择地级市');
			}
			if($_POST['s_county']=='市、县级市') {
				$this->error('请选择省份');
			}
			if($_POST['details']=='') {
				$this->error('请填写房屋详情');
			}
			$info = $this->upload();
			if (!$info) {
                $this->error('请上传房屋列表图');
            }
			$houseInfo = M('houseinfo');
			$userInfo = M('userinfo');
			$houseImg = M('houseimg');
			//存入用户信息
			$userInfo_add = array(
				'cont_name' => I('post.cont_name'),
				'contact' => I('post.contact'),
				'mail' => I('post.mail'),
			);
			$uiid = $userInfo->add($userInfo_add);
			$_POST['userinfo_uiid'] = $uiid;
			//组合地址
			if ($_POST['s_county'] != '市、县级市') {
				$_POST['address'] = $_POST['s_province'] . ',' . $_POST['s_city'] .','.$_POST['s_county'].','.$_POST['address'];
			}else{
				$_POST['address'] = $_POST['s_province'] . ',' . $_POST['s_city']. ',' .$_POST['address'];
			}
			$_POST['list_pic'] = $info[0]['savepath'].$info[0]['savename'];
			$_POST['add_time'] = time();
			$serial_num = 100000 + $uiid;
			//echo $serial_num;
			$_POST['serial_num'] = $serial_num;
			
			//存入房屋信息
			$hiid = $houseInfo->add($_POST);

			foreach ($info as $k => $v) {
				if ($k != 0) {
					$img = array(
						'pic' => $v['savepath'] . $v['savename'],
						'houseinfo_hid' => $hiid,
					);
					$houseImg->add($img);
				}
			}

			if ($hiid) {
				$hfeeInfo = array('hid'=>$hiid, 'fee'=>$_POST['house_fee'], 'ratio'=>$_POST['house_ratio'],'agent'=>$_POST['house_agent']);
				M('house_fee')->add($hfeeInfo);
				unset($_SESSION["userInfo"]);
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


	//编辑用户
	public function editUser($uiid)
	{	
		$userinfo = M('userinfo');
		if (IS_POST) {
			if ($_FILES['list_pic']['error']!=4 || $_FILES['list_pic']['error']==0) {
				$info = $this->upload();
			}
			if ($info) {
				$_POST['list_pic'] = $info['list_pic']['savepath'].$info['list_pic']['savename'];
				M('houseinfo')->where("userinfo_uiid={$uiid}")->save($_POST);
			}
			$userinfo->where("uiid={$uiid}")->save($_POST);
			$this->success('修改成功',U('index'));
			
		}else{
			$userData = $userinfo->where("uiid={$uiid}")->find();
			$list_pic = M('houseinfo')->field('list_pic')->where("userinfo_uiid={$uiid}")->find();
			$this->assign('list_pic',$list_pic);
			$this->assign('userData',$userData);
			$this->display();
		}
		
	}

	//修改房屋信息
	public function editHouse($hid)
	{	
		$houseinfo = M('houseinfo');
		$houseimg = M('houseimg');
		if (IS_POST) {

			$hidAll = $houseimg->field('hiid')->where("houseinfo_hid={$hid}")->select();
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
						'houseinfo_hid' => $hid
					);
					$houseimg->add($img);
				}
				$_POST['list_pic'] = $info[0]['savepath'].$info[0]['savename'];
			}
			//组合地址
			if ($_POST['s_county'] != '市、县级市') {
				$_POST['address'] = $_POST['s_province'] . ',' . $_POST['s_city'] .','.$_POST['s_county'].','.$_POST['address'];
			}else{
				$_POST['address'] = $_POST['s_province'] . ',' . $_POST['s_city']. ',' .$_POST['address'];
			}
			$houseinfo->where("hid={$hid}")->save($_POST);
			$fee=M('house_fee')->field('fee')->where('hid='.$hid)->find();
			$hfeeInfo = array('hid'=>$hid, 'fee'=>$_POST['house_fee'], 'ratio'=>$_POST['house_ratio'],'agent'=>$_POST['house_agent']);
			if(is_null($fee['fee'])) {
				M('house_fee')->add($hfeeInfo);
			} else {
				M('house_fee')->where("hid={$hid}")->save($hfeeInfo);
			}
			$this->success('编辑成功',U('index'));
			
		}else{
			
			$result = $houseinfo->where("hid={$hid}")->find();
			$result_img = $houseimg->where("houseinfo_hid={$hid}")->select();
			$this->assign('result_img',$result_img);
			//重组地址
			$address = explode(',',$result['address']);
			$this->assign('address',$address);
			$this->assign('result',$result);
			$fee=M('house_fee')->where('hid='.$hid)->find();
			$this->assign('fee',$fee);
			$this->display();
		}
		
	}

	//删除方法
	public function del($hid)
	{
		$uiid = M('houseinfo')->field('userinfo_uiid,list_pic')->where("hid={$hid}")->find();
		$frist = M('userinfo')->where("uiid={$uiid['userinfo_uiid']}")->delete();
		if (!$frist) {
			$this->error('删除失败');
		}
		// 删除列表图
		$list_pic = 'Uploads'.$uiid['list_pic'];
		if(is_file($list_pic)) unlink($list_pic);
		
		$house_pic = M('houseimg')->field('pic')->where("houseinfo_hid={$hid}")->select();

		foreach ($house_pic as $k => $v) {
			$img = 'Uploads'.$v['pic'];
			if(is_file($img)) unlink($img);
		}

		M('houseimg')->where("houseinfo_hid={$hid}")->delete();

		$z = M('houseinfo')->where("hid={$hid}")->delete();
		if ($z) {
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}
	

	//房屋审核管理
	public function houseAudit()
	{
		$status = I('get.status','');
		if ($status) {
			$where['status'] = array('eq',$status);
		}
		$result = M('houseinfo')
					->JOIN("LEFT JOIN ms_userinfo ON ms_houseinfo.userinfo_uiid=ms_userinfo.uiid")
					->where($where)
					->order('add_time desc')
					->SELECT();
		$this->assign('result',$result);
		$this->display();
	}

	//房屋审核
	public function auditAction($status,$hid)
	{
		$houseinfo = M('houseinfo');

		switch ($status) {
			case 1:
				$z = $houseinfo->where("hid={$hid}")->save(array('status'=>1));
				break;
			case 2:
				$z = $houseinfo->where("hid={$hid}")->save(array('status'=>2));
				break;
		}
		if ($z) {
			$this->success('操作成功',U('houseAudit'));
		}
	}

	//房屋顶置
	public function placedTop($hid)
	{
		$c = M('houseinfo')->where("hid={$hid}")->save(array('placed_top'=>1));
		if ($c) {
			$this->success('置顶成功');
		}
	}

	public function placedTopDel($hid)
	{
		$c = M('houseinfo')->where("hid={$hid}")->save(array('placed_top'=>0));
		if ($c) {
			$this->success('成功取消');
		}
	}

	//LISTTOP
	public function listTop($hid)
	{
		$c = M('houseinfo')->where("hid={$hid}")->save(array('list_top'=>1));
		if ($c) {
			$this->success('置顶成功');
		}
	}

	public function listTopDel($hid)
	{
		$c = M('houseinfo')->where("hid={$hid}")->save(array('list_top'=>0));
		if ($c) {
			$this->success('成功取消');
		}
	}

	public function charge($hid) {
		$c = M('houseinfo')->where("hid={$hid}")->setField('contact_charge',1);
		if ($c) {
			$this->success('查看联系方式收费成功');
		}else{
			$this->error('查看联系方式收费失败');
		}
	}
	
	public function uncharge($hid) {
		$c = M('houseinfo')->where("hid={$hid}")->setField('contact_charge',0);
		if ($c) {
			$this->success('取消查看联系方式收费成功');
		}else{
			$this->error('取消查看联系方式收费失败');
		}
	}
	
	public function tag($hid, $tag) {
		$c = M('houseinfo')->where("hid={$hid}")->setField('tag',$tag);
		if ($c) {
			$this->success('标记成功');
		}else{
			$this->error('标记失败');
		}
	}
	public function statistics($dt=0) {
		$this->assign('dt',$dt);
		$this->display();
	}
	
	//审核未通过理由
	public function auditReason()
	{
		if(IS_POST){
			$state = I('post.status');
			$hid = I('post.hid');
			$houseinfo = M('houseinfo');
			$z = $houseinfo->where("hid={$hid}")->save(array('status'=>3,'not_reason'=>$_POST['not_reason']));
			if($z){
				$this->success('操作成功',U('houseAudit'));
			}
		}else{
			$this->display();
		}
		
	}
	
	public function ajaxStatistics($dt=0) {
		$date= array();
		$click= array();
		if($dt==0) {
			$sql='SELECT * FROM ms_house_click  ORDER BY date desc LIMIT 53';
			$data=M()->query($sql);
			foreach ($data as $k => $v) {
				array_push($date,$v['date']);
				array_push($click,$v['click']);
			}
		} else if($dt==1) {
			$weekSql='SELECT WEEK(now(),1)as week';
			$week=(M()->query($weekSql));
			$week=$week[0]['week'];
			$sql='SELECT WEEK(date,1)as date,SUM(click) as click FROM ms_house_click GROUP BY WEEK(date,1) ORDER BY date desc';
			$data=M()->query($sql);
			foreach ($data as $k => $v) {
				$dateDisplay =$week-$v['date'];
				if($dateDisplay==0) {
					$dateDisplay='本周';
				} else if($dateDisplay==1) {
					$dateDisplay='上周';
				} else {
					$dateDisplay=$dateDisplay.'周前';
				}
				array_push($date,$dateDisplay);
				array_push($click,$v['click']);
			}
		} else {
			$sql='SELECT substring(date,1,7) AS date, sum(click) as click FROM ms_house_click GROUP BY substring(date,1,7) ORDER BY date desc';
			$data=M()->query($sql);
			foreach ($data as $k => $v) {
				array_push($date,$v['date']);
				array_push($click,$v['click']);
			}
		}
		
		$this->ajaxReturn(array('date'=>$date,'click'=>$click));
	}
	
		
	public function housestats($dt=0) {
		$this->assign('dt',$dt);
		$this->display();
	}
	
	public function ajaxHousestats($dt=0) {
		$date= array();
		$click= array();
		if($dt==0) {
			$sql='SELECT DATE(createtime) as date, count(*) as click FROM ms_houseinfo GROUP BY DATE(createtime)';
			$data=M()->query($sql);
			foreach ($data as $k => $v) {
				array_push($date,$v['date']);
				array_push($click,$v['click']);
			}
		} else if($dt==1) {
			$weekSql='SELECT WEEK(now(),1)as week';
			$week=(M()->query($weekSql));
			$week=$week[0]['week'];
			$sql="SELECT WEEK(createtime,1)as date, count(*) as click FROM ms_houseinfo WHERE DATE_FORMAT(createtime,'%y')=DATE_FORMAT(now(),'%y') GROUP BY WEEK(createtime,1)";
			$data=M()->query($sql);
			foreach ($data as $k => $v) {
				$dateDisplay =$week-$v['date'];
				if($dateDisplay==0) {
					$dateDisplay='本周';
				} else if($dateDisplay==1) {
					$dateDisplay='上周';
				} else {
					$dateDisplay=$dateDisplay.'周前';
				}
				array_push($date,$dateDisplay);
				array_push($click,$v['click']);
			}
		} else {
			$sql='SELECT substring(createtime,1,7) AS date,count(*) as click FROM ms_houseinfo GROUP BY substring(createtime,1,7)';
			$data=M()->query($sql);
			foreach ($data as $k => $v) {
				array_push($date,$v['date']);
				array_push($click,$v['click']);
			}
		}
		
		$this->ajaxReturn(array('date'=>$date,'click'=>$click));
	}
	
	public function coop() {
		$c = M('coop')->where("type=1")->find();
		$this->assign('coop',$c);
		$this->display();
	}
	
	public function editCoop($coop) {
		$c = M('coop')->where("type=1")->setField('content', $coop);
		if ($c) {
			$this->success('修改成功');
		}else{
			$this->error('修改失败');
		}
	}
}
 ?>