<?php 

namespace Admin\Controller;
use Think\Controller;

/**
* 经纪人模块
*/
class AgentController extends AuthController
{
	
	
	public function index()
	{
		$data = M('agent')->order('agid desc')->select();
		$this->assign('data',$data);
		$this->display();
	}

	public function audit($agid)
	{
		$z = M('agent')->where("agid={$agid}")->save(array('ag_audit'=>1));
		if ($z) {
			$this->success('操作成功');
		}else{
			$this->error('操作成功');
		}

	}

	public function audit_no($agid)
	{
		$z = M('agent')->where("agid={$agid}")->save(array('ag_audit'=>0));
		if ($z) {
			$this->success('操作成功');
		}else{
			$this->error('操作成功');
		}

	}

	public function del($agid)
	{
		$z = M('agent')->where("agid={$agid}")->delete();
		if ($z) {
			$this->success('操作成功');
		}else{
			$this->error('操作成功');
		}
	}


//添加说明22222
	public function addShuo()
	{
		if (IS_POST) {
			if ($_POST['aboutid']) {
				M('about')->where("aboutid={$_POST['aboutid']}")->save($_POST);
			}else{
				M('about')->add($_POST);
			}
			$this->success('编辑成功',U('index'));
			
		}else{
			$data = M('about')->find();
			$this->assign('data',$data);
			$this->display();
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
			$sql='SELECT DATE(createtime) as date, count(*) as click FROM ms_agent GROUP BY DATE(createtime)';
			$data=M()->query($sql);
			foreach ($data as $k => $v) {
				array_push($date,$v['date']);
				array_push($click,$v['click']);
			}
		} else if($dt==1) {
			$weekSql='SELECT WEEK(now(),1)as week';
			$week=(M()->query($weekSql));
			$week=$week[0]['week'];
			$sql="SELECT WEEK(createtime,1)as date, count(*) as click FROM ms_agent WHERE DATE_FORMAT(createtime,'%y')=DATE_FORMAT(now(),'%y') GROUP BY WEEK(createtime,1)";
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
			$sql='SELECT substring(createtime,1,7) AS date,count(*) as click FROM ms_agent GROUP BY substring(createtime,1,7)';
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