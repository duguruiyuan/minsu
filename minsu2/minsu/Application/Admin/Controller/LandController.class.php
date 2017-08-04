<?php 

namespace Admin\Controller;
use Think\Controller;

/**
* 房屋管理控制器
*/
class LandController extends AuthController {
	//房屋添加首页
	public function index()
	{	
		$result = M('landinfo')->SELECT();
		$this->assign('result',$result);
		$this->display();
	}

	//添加房屋信息
	public function add() {
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
				$this->error('请填写土地详情');
			}
			$info = $this->upload();
			if (!$info) {
                $this->error('请上传列表图');
            }
			$landinfo = M('landinfo');
			$landimg = M('land_pic');
			//组合地址
			if ($_POST['s_county'] != '市、县级市') {
				$_POST['address'] = $_POST['s_province'] . ',' . $_POST['s_city'] .','.$_POST['s_county'].','.$_POST['address'];
			}else{
				$_POST['address'] = $_POST['s_province'] . ',' . $_POST['s_city']. ',' .$_POST['address'];
			}
			$_POST['list_pic'] = $info[0]['savepath'].$info[0]['savename'];
			$_POST['createtime'] = date('Y-m-d H:i:s');
			//存入房屋信息
			$id= $landinfo->add($_POST);

			foreach ($info as $k => $v) {
				$img = array(
					'pic' => $v['savepath'] . $v['savename'],
					'lid' => $id,
				);
				$landimg->add($img);
			}
			$this->success('添加成功',U('index'));
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

	//修改房屋信息
	public function edit($id) {	
		$landinfo = M('landinfo');
		$landimg = M('land_pic');
		if (IS_POST) {
			$lidAll = $landimg->field('id')->where("lid={$id}")->select();
			//重组数组 取出id
			$temp = array();
			foreach ($lidAll as $k => $v) {
				$temp[] = $v['id'];
			}
			//获得用户不需要修改的图片 并且取出id
			$img_id = array();
			foreach ($_POST['temp_img'] as $k => $v) {
				$hiid = $landimg->field('id')->where("pic='{$v}'")->find();
				$img_id[] = $hiid['id'];
			}
			$_POST['list_pic'] = $_POST['temp_img'][0];
			// 取出用户删除的图片的id 并执行删除方法
			$in = array_diff($temp, $img_id);
			if ($in) {
				$landimg->where(array('lid'=>array('IN',$in)))->delete();
			}
			//上传图片
			$info = $this->upload();
			if ($info) {
				foreach ($info as $k => $v) {
					$img = array(
						'pic' => $v['savepath'] . $v['savename'],
						'lid' => $id
					);
					$landimg->add($img);
				}
				$_POST['list_pic'] = $info[0]['savepath'].$info[0]['savename'];
			}
			//组合地址
			if ($_POST['s_county'] != '市、县级市') {
				$_POST['address'] = $_POST['s_province'] . ',' . $_POST['s_city'] .','.$_POST['s_county'].','.$_POST['address'];
			}else{
				$_POST['address'] = $_POST['s_province'] . ',' . $_POST['s_city']. ',' .$_POST['address'];
			}
			$landinfo->where("id={$id}")->save($_POST);
			$this->success('编辑成功',U('index'));
		}else{
			$result = $landinfo->where("id={$id}")->find();
			$result_img = $landimg->where("lid={$id}")->select();
			$this->assign('result_img',$result_img);
			//重组地址
			$address = explode(',',$result['address']);
			$this->assign('address',$address);
			$this->assign('result',$result);
			$this->display();
		}
	}

	//删除方法
	public function del($id)
	{
		$uiid = M('landinfo')->field('uid,list_pic')->where("id={$id}")->find();
		$frist = M('userinfo')->where("uiid={$uiid['uid']}")->delete();
		if (!$frist) {
			$this->error('删除失败');
		}

		$z = M('landinfo')->where("id={$id}")->delete();
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
		$result = M('landinfo')
					->JOIN("LEFT JOIN ms_userinfo ON ms_landinfo.userinfo_uiid=ms_userinfo.uiid")
					->where($where)
					->order('add_time desc')
					->SELECT();
		$this->assign('result',$result);
		$this->display();
	}

	//房屋审核
	public function auditAction($status,$hid)
	{
		$landinfo = M('landinfo');

		switch ($status) {
			case 1:
				$z = $landinfo->where("hid={$hid}")->save(array('status'=>1));
				break;
			case 2:
				$z = $landinfo->where("hid={$hid}")->save(array('status'=>2));
				break;
		}
		if ($z) {
			$this->success('操作成功',U('houseAudit'));
		}
	}

	//审核未通过理由
	public function auditReason()
	{
		if(IS_POST){
			$state = I('post.status');
			$hid = I('post.hid');
			$landinfo = M('landinfo');
			$z = $landinfo->where("hid={$hid}")->save(array('status'=>3,'not_reason'=>$_POST['not_reason']));
			if($z){
				$this->success('操作成功',U('houseAudit'));
			}
		}else{
			$this->display();
		}
		
	}

	public function top($id)
	{
		$c = M('landinfo')->where("id={$id}")->save(array('is_top'=>1));
		if ($c) {
			$this->success('置顶成功');
		}else{
			$this->error('置顶失败');
		}
	}

	public function untop($id)
	{
		$c = M('landinfo')->where("id={$id}")->save(array('is_top'=>0));
		if ($c) {
			$this->success('成功取消');
		}else{
			$this->error('取消失败');
		}
	}

	public function charge($id) {
		$c = M('landinfo')->where("id={$id}")->setField('contact_charge',1);
		if ($c) {
			$this->success('查看联系方式收费成功');
		}else{
			$this->error('查看联系方式收费失败');
		}
	}
	
	public function uncharge($id) {
		$c = M('landinfo')->where("id={$id}")->setField('contact_charge',0);
		if ($c) {
			$this->success('取消查看联系方式收费成功');
		}else{
			$this->error('取消查看联系方式收费失败');
		}
	}
	
	public function tag($id, $tag) {
		$c = M('landinfo')->where("id={$id}")->setField('tag',$tag);
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
	
	public function ajaxStatistics($dt=0) {
		$date= array();
		$click= array();
		if($dt==0) {
			$sql='SELECT * FROM ms_house_click LIMIT 53';
			$data=M()->query($sql);
			foreach ($data as $k => $v) {
				array_push($date,$v['date']);
				array_push($click,$v['click']);
			}
		} else if($dt==1) {
			$weekSql='SELECT WEEK(now(),1)as week';
			$week=(M()->query($weekSql));
			$week=$week[0]['week'];
			$sql='SELECT WEEK(date,1)as date,SUM(click) as click FROM ms_house_click GROUP BY WEEK(date,1)';
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
			$sql='SELECT substring(date,1,7) AS date, sum(click) as click FROM ms_house_click GROUP BY substring(date,1,7)';
			$data=M()->query($sql);
			foreach ($data as $k => $v) {
				array_push($date,$v['date']);
				array_push($click,$v['click']);
			}
		}
		
		$this->ajaxReturn(array('date'=>$date,'click'=>$click));
	}

	public function interestedList($id)
	{
		$this->assign('result',M('landmessage')->where("lid={$id}")->select());
		$this->display();
	}
	
	public function housestats($dt=0) {
		$this->assign('dt',$dt);
		$this->display();
	}
	
	public function ajaxHousestats($dt=0) {
		$date= array();
		$click= array();
		if($dt==0) {
			$sql='SELECT DATE(createtime) as date, count(*) as click FROM ms_landinfo GROUP BY DATE(createtime)';
			$data=M()->query($sql);
			foreach ($data as $k => $v) {
				array_push($date,$v['date']);
				array_push($click,$v['click']);
			}
		} else if($dt==1) {
			$weekSql='SELECT WEEK(now(),1)as week';
			$week=(M()->query($weekSql));
			$week=$week[0]['week'];
			$sql="SELECT WEEK(createtime,1)as date, count(*) as click FROM ms_landinfo WHERE DATE_FORMAT(createtime,'%y')=DATE_FORMAT(now(),'%y') GROUP BY WEEK(createtime,1)";
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
			$sql='SELECT substring(createtime,1,7) AS date,count(*) as click FROM ms_landinfo GROUP BY substring(createtime,1,7)';
			$data=M()->query($sql);
			foreach ($data as $k => $v) {
				array_push($date,$v['date']);
				array_push($click,$v['click']);
			}
		}
		
		$this->ajaxReturn(array('date'=>$date,'click'=>$click));
	}
	
	public function coop() {
		$c = M('coop')->where("type=0")->find();
		$this->assign('coop',$c);
		$this->display();
	}
	
	public function editCoop($coop) {
		$c = M('coop')->where("type=0")->setField('content', $coop);
		if ($c) {
			$this->success('修改成功');
		}else{
			$this->error('修改失败');
		}
	}
}
 ?>