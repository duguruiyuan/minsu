<?php 

namespace Home\Controller;
use Think\Controller;

/**
* 众筹
*/
class FundingController extends Controller
{
	
	public function index()
	{
		$this->assign('data',M('funding')->select());
		$this->display();
	}

	public function ajaxSign() {
		if(!IS_AJAX) return false;
		$z = M('fund_sign')->add($_POST);
		if ($z) {
			$data['status'] = 'ok';
			$data['id']=$z;
			$this->ajaxReturn($data);
		}else{
			$data['status'] = 'no';
			$this->ajaxReturn($data);
		}
	}
	
	public function ajaxAddNum($funid) {
		M('funding')->where("funid={$funid}")->setInc('fun_interested');
	}
	
		
	public function ajaxAppoint($u,$id) {
		$sign=array('paid'=>1,'fee'=>session('fee'));
		 M('fund_sign')->WHERE("id={$id}")->save($sign);
		 unset($_SESSION['fee']); 
		 header('Location: '.$u);
	}
}
 ?>