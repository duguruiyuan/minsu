<?php 
namespace Home\Controller;
use Think\Controller;
/**
* 众筹
*/
class PayController extends Controller{
	public function land($id,$openid=null) {
		if($openid==null) {
			$openid=cookie('oid');
		} 
		
		$data = M('landinfo')->where("id={$id}")->find();
		$this->assign('img',M('land_pic')->where("lid={$id}")->select());
		$this->assign('result',$data);
		if($data['contact_charge']==1 && !is_null($openid)) {
			$status = M('unlock_contact')->field('status')->where("hid={$funid} AND openid='{$openid}'  AND business=2")->find();
			$this->assign('unlock_contact',$status['status']);
		} 
		$this->assign('ad',M('ad')->find());
		$this->display();
	}
	
	public function civilian($id,$openid=null) {
		if($openid==null) {
			$openid=cookie('oid');
		} 
		
		$data = M('civilianinfo')->where("id={$id}")->find();
		$this->assign('img',M('civilian_pic')->where("lid={$id}")->select());
		$this->assign('result',$data);
		if($data['contact_charge']==1 && !is_null($openid)) {
			$status = M('unlock_contact')->field('status')->where("hid={$id} AND openid='{$openid}'  AND business=-1")->find();
			$this->assign('unlock_contact',$status['status']);
		} 
		$this->assign('ad',M('ad')->find());
		$this->display();
	}	
		
	public function house($hid,$openid=null) {
		$num = mt_rand(3,4);
		$i = $num;
		$houseImg = M('houseimg')->WHERE("houseinfo_hid={$hid}")->select();
		$this->assign('houseImg',$houseImg);
		$this->assign('total',count($houseImg));

		$result = M('houseinfo')
					->JOIN("LEFT JOIN ms_userinfo ON ms_houseinfo.userinfo_uiid=ms_userinfo.uiid")
					->where("hid={$hid}")
					->find();
		M('houseinfo')->where("hid={$hid}")->save(array('house_click'=>$result['house_click'] + $i,'real_click'=>$result['real_click'] + 1));
		$today = date("Y-m-d");
		$click = M('house_click')->WHERE("date='{$today}'")->find();
		if($click['click']) {
			M('house_click')->save(array('date'=>$today,'click'=>$click['click']+1));
		} else {
			M('house_click')->add(array('date'=>$today,'click'=>1));
		}
		if($openid==null) {
			$openid=cookie('oid');
		} 
		if($result['contact_charge']=='1' && !is_null($openid)) {
			$status = M('unlock_contact')->field('status')->where("hid={$hid} AND openid='{$openid}'  AND business=0")->find();
			$this->assign('unlock_contact',$status['status']);
		}
		$this->assign('result',$result);
		$this->assign('ad',M('ad')->find());
		$fee = M('house_fee')->WHERE("hid={$hid}")->find();
		if($fee['fee']!=-1) {
			$this->assign('paid',M('house_sign')->WHERE("hid={$hid} AND openid='{$openid}'")->find());
		}
		$this->assign('fee',$fee);
		$this->display();
	}
	
	public function funding($funid,$openid=null) {
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
		
		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		if (strpos($user_agent, 'MicroMessenger')) {
			$this->assign('isweixin',1);
		}
		$this->display();
	}
	
	
	public function fund_sign($funid){
		$data = M('funding')->where("funid={$funid}")->find();
		$this->assign('sign_fee',$data['sign_fee']);
		$this->display();
	}
	
	public function sign($hid) {
		$fee=M('house_fee')->field('fee')->where('hid='.$hid)->find();
		if(is_null($fee['fee'])) {
			$fee=0;
		} else {
			$fee=$fee['fee'];
		}
		$this->assign('house_fee',$fee);
		$this->display();
	}

	public function ajaxSign() {
		if(IS_AJAX) {
			$house = M('house_fee')->WHERE('hid='.$_POST['hid'])->find();
			if ($house) {
				$_POST['agent']=$house['agent'];
				$z = M('house_sign')->WHERE('hid='.$_POST['hid'].' AND openid="'.$_POST['openid'].'"')->find();
				$_POST['fee']=$house['fee'];
				if(IS_NULL($z['paid'])) {
					if($house['fee']>0) {
						$_POST['paid']=1;
					}
					$z = M('house_sign')->add($_POST);
					$data['sid'] = $z;
				}else {
					$z = M('house_sign')->WHERE('id='.$z['id'])->save($_POST);
				}
				$data['status'] = 'ok';
				$this->ajaxReturn($data);
			}
		}
		$data['status'] = 'no';
		$this->ajaxReturn($data);
	}
	
	public function comment() {
		$this->display();
	}
	
	public function ajaxComment() {
		if(!IS_AJAX) {
			$data['status'] = 'no';
		} else {
			$house = M('house_fee')->WHERE('hid='.$_POST['hid'])->find();
			if ($house) {
				$_POST['agent']=$house['agent'];
				$z = M('house_comment')->add($_POST);
			}
			$data['status'] = 'ok';
		}
		$this->ajaxReturn($data);
	}
}
 ?>