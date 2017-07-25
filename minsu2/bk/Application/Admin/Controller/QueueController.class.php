<?php 

namespace Admin\Controller;
use Think\Controller;

/**
* 房屋管理控制器
*/
class QueueController extends AuthController {
	//房屋添加首页
	public function index($hid) {	
		$result = M('houseinfo')->where("id={$hid}")->find();
		$this->assign('result',$result);
		$timeQueues = M('h_time_queue')->WHERE("hid={$hid} AND status=2")->select();
		$this->assign('timeQueues',$timeQueues);
		$this->display();
	}

	//添加房屋信息
	public function add($hid=null) {
		if (IS_POST) {
			$_POST['createtime'] = date('Y-m-d H:i:s');
			$id=M('h_time_queue')->add($_POST);
			if ($id) {
				$begin_time = strtotime(date($_POST['begin_time']));
				$end_time = strtotime(date($_POST['end_time']));
				$hid=$_POST['hid'];
				$amount=$_POST['amount'];
				while($begin_time<=$end_time) {
					$begin=date('Y-m-d',$begin_time);
					$sql="insert into bk_h_time (hid,date)values({$hid},'{$begin}') ON DUPLICATE KEY UPDATE used=used+{$amount}";
					M()->execute($sql);
					$begin_time=$begin_time+86400;
				}
				$hid = $_POST['hid'];
				$this->success('添加成功',U('index',array("hid"=>$hid)));
			}else{
				$this->error('添加失败');
			}
		}else{
			$this->display();
		}
	}

	public function reserve($hid=null,$r_id=null) {
		if(!is_null($hid)) {
			$where['hid'] = array('eq',$hid);
			$result = M('houseinfo')->where("id={$hid}")->find();
			$this->assign('house',$result);
		} else if(!is_null($r_id)) {
			$where['r_id'] = array('eq',$r_id);
			$result = M('guest')->where("id={$r_id}")->find();
			$this->assign('guest',$result);
		}
		$where['status'] = array('neq',2);
		$timeQueues = M('h_time_queue')->WHERE($where)->select();
		$this->assign('timeQueues',$timeQueues);
		session('url',$_SERVER["REQUEST_URI"]);
		$this->display();
	}

	public function addReserve($hid=null,$r_id=null) {
		if (IS_POST) {
			$_POST['createtime'] = date('Y-m-d H:i:s');
			$begin_time = strtotime(date($_POST['begin_time']));
			$end_time = strtotime(date($_POST['end_time']));
			$hid=$_POST['hid'];
			$amount=$_POST['amount'];
			while($begin_time<=$end_time) {
				$begin=date('Y-m-d',$begin_time);
				if($status==1||$status==2||$status==3||$status==6||$status==7||$status==8) {
					$sql="insert into bk_h_time (hid,date)values({$hid},'{$begin}') ON DUPLICATE KEY UPDATE used=used+{$amount}";
					M()->execute($sql);
				} else {
					M('h_time')->where("hid={$hid} AND date='{$begin}'")->setInc("used",-$amount);
				}
				$begin_time=$begin_time+86400;
			}
			$id=M('h_time_queue')->add($_POST);
			if ($id) {
				$this->success('添加成功',session('url'));
			}else{
				$this->error('添加失败');
			}
		}else{
			$this->display();
		}
	}

	//修改房屋信息
	public function editReserveTime($id) {	
		if (IS_POST) {
			$q=M('h_time_queue')->where("id={$id}")->find();
			if($q['begin_time']!=$_POST['begin_time'] || $q['end_time']!=$_POST['end_time']) {
				$begin_time = strtotime(date($q['begin_time']));
				$end_time = strtotime(date($q['end_time']));
				$hid=$q['hid'];
				while($begin_time<=$end_time) {
					$begin=date('Y-m-d',$begin_time);
					M('h_time')->where("hid={$hid} AND date='{$begin}'")->setInc("used",-1);
					$begin_time=$begin_time+86400;
				}
				$begin_time = strtotime(date($_POST['begin_time']));
				$end_time = strtotime(date($_POST['end_time']));
				while($begin_time<=$end_time) {
					$begin=date('Y-m-d',$begin_time);
					$sql="insert into bk_h_time (hid,date)values({$hid},'{$begin}') ON DUPLICATE KEY UPDATE used=used+1";
					M()->execute($sql);
					$begin_time=$begin_time+86400;
				}
			}
			M('h_time_queue')->where("id={$id}")->save($_POST);
			$this->success('编辑成功',session('url'));
		}else{
			$result=M('h_time_queue')->where("id={$id}")->find();
			$this->assign('result',$result);
			$this->assign('url',$url);
			$this->display();
		}
	}
	
	//修改房屋信息
	public function editReserveStatus($id) {	
		if (IS_POST) {
			$q=M('h_time_queue')->where("id={$id}")->find();
			$status=$q['status'];
			if($status==1||$status==2||$status==3||$status==6||$status==7||$status==8) {
				$status=1;
			} else {
				$status=0;
			}
			$p_status=$_POST['status'];
			if($p_status==1||$p_status==2||$p_status==3||$p_status==6||$p_status==7||$p_status==8) {
				$p_status=1;
			} else {
				$p_status=0;
			}
			if($p_status!=$status){
				$begin_time = strtotime(date($q['begin_time']));
				$end_time = strtotime(date($q['end_time']));
				$hid=$q['hid'];
				while($begin_time<=$end_time) {
					$begin=date('Y-m-d',$begin_time);
					if($p_status==1) {
						$sql="insert into bk_h_time (hid,date)values({$hid},'{$begin}') ON DUPLICATE KEY UPDATE used=used+1";
						M()->execute($sql);
					} else {
						M('h_time')->where("hid={$hid} AND date='{$begin}'")->setInc("used",-1);
					}
					$begin_time=$begin_time+86400;
				}
			}
			M('h_time_queue')->where("id={$id}")->save($_POST);
			$this->success('编辑成功',session('url'));
		}else{
			$result=M('h_time_queue')->where("id={$id}")->find();
			$this->assign('result',$result);
			$this->assign('url',$url);
			$this->display();
		}
	}
		//修改房屋信息
	public function editReserveInfo($id) {	
		if (IS_POST) {
			M('h_time_queue')->where("id={$id}")->save($_POST);
			$this->success('编辑成功',session('url'));
		}else{
			$result=M('h_time_queue')->where("id={$id}")->find();
			$this->assign('result',$result);
			$this->assign('url',$url);
			$this->display();
		}
	}
	
	//删除方法
	public function del($id) {
		$q = M('h_time_queue')->where("id={$id}")->find();
		$begin_time = strtotime(date($q['begin_time']));
		$end_time = strtotime(date($q['end_time']));
		$hid=$q['hid'];
		$amount=$q['amount'];
		$status=$q['status'];
		if($status==1||$status==2||$status==3||$status==6||$status==7||$status==8) {
			$amount=-$q['amount'];
		}
		while($begin_time<=$end_time) {
			$begin=date('Y-m-d',$begin_time);
			M('h_time')->where("hid={$hid} AND date='{$begin}'")->setInc("used",$amount);
			$begin_time=$begin_time+86400;
		}
		$z = M('h_time_queue')->where("id={$id}")->delete();
		if ($z) {
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}
	
	public function order($dt=10) {
		$this->assign('dt',$dt);
		$this->display();
	}
	
	public function ajaxOrder($dt=10) {
		$date= array();
		$click= array();
		if($dt==0) {
			$sql='SELECT substring(createtime,1,7) AS date,count(*) as click FROM bk_h_time_queue WHERE status>5 GROUP BY substring(createtime,1,7)';
			$data=M()->query($sql);
			foreach ($data as $k => $v) {
				array_push($date,$v['date']);
				array_push($click,$v['click']);
			}
		} elseif($dt==10) {
			$sql='SELECT substring(createtime,1,10) AS date,count(*) as click FROM bk_h_time_queue WHERE status>5 GROUP BY substring(createtime,1,10)';
			$data=M()->query($sql);
			foreach ($data as $k => $v) {
				array_push($date,$v['date']);
				array_push($click,$v['click']);
			}
		} elseif($dt==1) {
			$sql='SELECT substring(createtime,1,7) AS date,count(*) as click FROM bk_h_time_queue WHERE status>3 AND status<7 GROUP BY substring(createtime,1,7)';
			$data=M()->query($sql);
			foreach ($data as $k => $v) {
				array_push($date,$v['date']);
				array_push($click,$v['click']);
			}
		} elseif($dt==11) {
			$sql='SELECT substring(createtime,1,10) AS date,count(*) as click FROM bk_h_time_queue WHERE status>3 AND status<7 GROUP BY substring(createtime,1,10)';
			$data=M()->query($sql);
			foreach ($data as $k => $v) {
				array_push($date,$v['date']);
				array_push($click,$v['click']);
			}
		} elseif($dt==12) {
			$sql='SELECT substring(createtime,1,10) AS date,count(*) as click FROM bk_h_time_queue WHERE status=1 GROUP BY substring(createtime,1,10)';
			$data=M()->query($sql);
			foreach ($data as $k => $v) {
				array_push($date,$v['date']);
				array_push($click,$v['click']);
			}
		} else{
			$sql='SELECT substring(createtime,1,7) AS date,count(*) as click FROM bk_h_time_queue WHERE status=1 GROUP BY substring(createtime,1,7)';
			$data=M()->query($sql);
			foreach ($data as $k => $v) {
				array_push($date,$v['date']);
				array_push($click,$v['click']);
			}
		}
		$this->ajaxReturn(array('date'=>$date,'click'=>$click));
	}
	
	public function deal($dt=0) {
		$this->assign('dt',$dt);
		$this->display();
	}
	
	public function ajaxDeal($dt=0) {
		$date= array();
		$click= array();
		if($dt==0) {
			$sql='SELECT substring(createtime,1,10) AS date,sum(r_fee) as click FROM bk_h_time_queue WHERE status>5 GROUP BY substring(createtime,1,10)';
			$data=M()->query($sql);
			foreach ($data as $k => $v) {
				array_push($date,$v['date']);
				array_push($click,$v['click']);
			}	
		} else {
			$sql='SELECT substring(createtime,1,7) AS date,sum(r_fee) as click FROM bk_h_time_queue WHERE status>5 GROUP BY substring(createtime,1,7)';
			$data=M()->query($sql);
			foreach ($data as $k => $v) {
				array_push($date,$v['date']);
				array_push($click,$v['click']);
			}			
		}
		$this->ajaxReturn(array('date'=>$date,'click'=>$click));
	}
}
 ?>