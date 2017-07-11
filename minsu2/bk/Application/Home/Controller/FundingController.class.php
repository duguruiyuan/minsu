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

	public function details($funid,$openid=null) {
		if($openid==null) {
			$openid=cookie('oid');
		} 
		$data = M('funding')->where("funid={$funid}")->find();
		$this->assign('img',explode(',', $data['fun_pic']));
		$this->assign('result',$data);
		if($data['contact_charge']==1 && !is_null($openid)) {
			$status = M('unlock_contact')->field('status')->where("hid={$funid} AND openid='{$openid}'  AND business=1")->find();
			$this->assign('unlock_contact',$status['status']);
		} 
		$this->assign('ad',M('ad')->find());
		$this->display();
	}

	public function sign($funid)
	{
		$this->display();
	}

	public function ajaxSign() {
		if(!IS_AJAX) return false;
		$z = M('fund_sign')->add($_POST);
		if ($z) {
			$data['status'] = 'ok';
			$this->ajaxReturn($data);
		}else{
			$data['status'] = 'no';
			$this->ajaxReturn($data);
		}
	}
	
	public function ajaxAddNum($funid) {
		M('funding')->where("funid={$funid}")->setInc('fun_interested');
	}
}
 ?>