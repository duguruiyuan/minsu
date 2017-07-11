<?php 

namespace Home\Controller;
use Think\Controller;

/**
* 众筹
*/
class LandController extends Controller
{
	
	public function index() {
		$this->assign('data',M('landinfo')->order('is_top desc,id desc')->select());
		$this->assign('ad',M('ad')->find());
		$this->display();
	}

	public function interested($lid) {
		$this->display();
	}

	public function ajaxInterested() {
		if(!IS_AJAX) return false;
		$data = M('landinfo')->where("id=" . $_POST['lid'])->field('interested')->find();
		M('landinfo')->where("id=" . $_POST['lid'])->save(array('interested'=>$data['interested'] + 1));
		$z = M('landmessage')->add($_POST);
		if ($z) {
			$data['status'] = 'ok';
			$this->ajaxReturn($data);
		}else{
			$data['status'] = 'no';
			$this->ajaxReturn($data);
		}
	}
		
	public function coop() {
		$c = M('coop')->where("type=0")->find();
		$this->assign('coop',$c);
		$this->display();
	}
}
 ?>