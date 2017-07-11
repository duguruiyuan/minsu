<?php

namespace Admin\Controller;
use Think\Controller;

/**
* 用户管理
*/
class UserController extends AuthController {
	public function owner() {
		$data = M('owner')->select();
		$this->assign('data',$data);
		$this->display();
	}

	public function guest() {
		$data = M('guest')->select();
		$this->assign('data',$data);
		$this->display();
	}
	
	public function editOwner($id) {
		if(IS_POST) {
			$z = M('owner')->where("id={$id}")->save($_POST);
			if ($z) {
				$this->success('修改房主信息成功',U('owner'));
			}else{
				$this->error('修改房主信息失败');
			}
		} else {
			$data=M('owner')->where("id={$id}")->find();
			$this->assign('data',$data);
			$this->display();
		}
	}

	public function dealOwner($id,$status) {
		$z = M('owner')->where("id='{$id}'")->save(array('status'=>$status));
		if ($z) {
			$this->success('处理成功');
		}else{
			$this->error('处理失败');
		}
	}

	public function delOwner($id)
	{
		$z = M('owner')->where("id={$id}")->delete();
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
			$sql='SELECT substring(createtime,1,7) AS date,count(*) as click FROM bk_owner GROUP BY substring(createtime,1,7)';
			$data=M()->query($sql);
			foreach ($data as $k => $v) {
				array_push($date,$v['date']);
				array_push($click,$v['click']);
			}
		} else {
			$sql='SELECT substring(createtime,1,7) AS date,count(*) as click FROM bk_guest GROUP BY substring(createtime,1,7)';
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