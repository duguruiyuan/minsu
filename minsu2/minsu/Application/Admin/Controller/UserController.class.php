<?php

namespace Admin\Controller;
use Think\Controller;

/**
* 用户管理
*/
class UserController extends AuthController
{
	public function index()
	{
		$data = M('user')->select();
		$this->assign('data',$data);
		$this->display();
	}

	public function lock($uid)
	{
		$z = M('user')->where("uid='{$uid}'")->save(array('is_limit'=>1));
		if ($z) {
			$this->success('锁定成功');
		}else{
			$this->error('锁定失败');
		}
	}

	public function j_lock($uid)
	{
		$z = M('user')->where("uid='{$uid}'")->save(array('is_limit'=>0));
		if ($z) {
			$this->success('解锁成功');
		}else{
			$this->error('解锁失败');
		}
	}
	
	public function del($uid)
	{
		$z = M('user')->where("uid={$uid}")->delete();
		if ($z) {
			$this->success('删除成功');
		}else{
			$this->error("删除失败");
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
			$sql='SELECT DATE(createtime) as date, count(*) as click FROM ms_user GROUP BY DATE(createtime)';
			$data=M()->query($sql);
			foreach ($data as $k => $v) {
				array_push($date,$v['date']);
				array_push($click,$v['click']);
			}
		} else if($dt==1) {
			$weekSql='SELECT WEEK(now(),1)as week';
			$week=(M()->query($weekSql));
			$week=$week[0]['week'];
			$sql="SELECT WEEK(createtime,1)as date, count(*) as click FROM ms_user WHERE DATE_FORMAT(createtime,'%y')=DATE_FORMAT(now(),'%y') GROUP BY WEEK(createtime,1)";
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
			$sql='SELECT substring(createtime,1,7) AS date,count(*) as click FROM ms_user GROUP BY substring(createtime,1,7)';
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