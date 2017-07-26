<?php 

namespace Admin\Controller;
use Think\Controller;

/**
* 房屋管理控制器
*/
class HouseController extends AuthController {
	//房屋添加首页
	public function index()
	{	
		$result = M('houseinfo')->order('createtime desc')->SELECT();
		$this->assign('result',$result);
		$this->display();
	}

	//添加房屋信息
	public function add() {
		if (IS_POST) {
			$info = $this->upload();
			if (!$info) {
                $this->error('上传失败');
            }
			$houseInfo = M('houseinfo');
			$houseImg = M('houseimg');
			$_POST['list_pic'] = $info[0]['savepath'].$info[0]['savename'];
			$_POST['createtime'] = date('Y-m-d H:i:s');
			//存入房屋信息
			$hiid = $houseInfo->add($_POST);
			foreach ($info as $k => $v) {
				$img = array(
					'pic' => $v['savepath'] . $v['savename'],
					'houseinfo_hid' => $hiid,
				);
				$houseImg->add($img);
			}
			if ($hiid) {
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

	//修改房屋信息
	public function edit($id) {	
		$houseinfo = M('houseinfo');
		$houseimg = M('houseimg');
		if (IS_POST) {
			$hidAll = $houseimg->field('hiid')->where("houseinfo_hid={$id}")->select();
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
						'houseinfo_hid' => $id
					);
					$houseimg->add($img);
				}
				$_POST['list_pic'] = $info[0]['savepath'].$info[0]['savename'];
			}
			$houseinfo->where("id={$id}")->save($_POST);
			$this->success('编辑成功',U('index'));
		}else{
			$result = $houseinfo->where("id={$id}")->find();
			$result_img = $houseimg->where("houseinfo_hid={$id}")->select();
			$this->assign('result_img',$result_img);
			$this->assign('result',$result);
			$this->display();
		}
		
	}

	//房屋添加首页
	public function reconcile($tradeNo=null, $begin=null, $createtime=null) {	
		$sql='SELECT t.*, h.name as hname FROM bk_h_time_queue as t inner join bk_houseinfo as h on t.hid=h.id ';
		if($tradeNo) {
			$sql=$sql.' where t.r_trade_no='.$tradeNo;
		} else if($begin) {
			$sql=$sql.' where t.begin_time="'.$begin.'"';
		} else if($createtime) {
			$sql=$sql.' where substring(t.createtime,1,10)="'.$createtime.'"';
		}
		$sql=$sql.' order by t.createtime desc';
		$timeQueues=M()->query($sql);
		$this->assign('timeQueues',$timeQueues);
		$this->display();
	}
	
	//删除方法
	public function del($id) {
		$uiid = M('houseinfo')->field('uid,list_pic')->where("id={$id}")->find();
		// 删除列表图
		$list_pic = 'Uploads'.$uiid['list_pic'];
		if(is_file($list_pic)) unlink($list_pic);
		$house_pic = M('houseimg')->field('pic')->where("houseinfo_hid={$id}")->select();
		foreach ($house_pic as $k => $v) {
			$img = 'Uploads'.$v['pic'];
			if(is_file($img)) unlink($img);
		}
		M('houseimg')->where("houseinfo_hid={$id}")->delete();
		$z = M('houseinfo')->where("id={$id}")->delete();
		if ($z) {
			$this->success('删除成功');
		}else{
			$this->error('删除失败');
		}
	}
	
	public function editStory($id) {
		if(IS_POST) {
			$z = M('houseinfo')->where("id={$id}")->save($_POST);
			if ($z) {
				$this->success('修改房主故事成功',U('index'));
			}else{
				$this->error('修改房主故事失败');
			}
		} else {
			$data=M('houseinfo')->where("id={$id}")->find();
			$this->assign('data',$data);
			$this->display();
		}
	}
	
	//房屋审核管理
	public function houseAudit() {
		$status = I('get.status','');
		if (!is_null($status)) {
			$where['status'] = array('eq',$status);
			$this->assign('status',$status);
		}
		$result = M('houseinfo')
					->where($where)
					->order('createtime desc')
					->SELECT();
		$this->assign('result',$result);
		$this->display();
	}

	//房屋审核
	public function auditAction($status,$id) {
		$houseinfo = M('houseinfo');
		$ids = explode(",",$id,100);
		$idArr=array();
		foreach ($ids as $k => $v) {
			if($v) {
				array_push($idArr,$v);
			}
		}
		$where['id']=array('in',$idArr);
		$z = $houseinfo->where($where)->save(array('status'=>$status));
		if ($z) {
			$this->success('操作成功',U('houseAudit'));
		}
	}

	//审核未通过理由
	public function auditReason(){
		if(IS_POST){
			$state = I('post.status');
			$id = I('post.id');
			$z = M('houseinfo')->where("id={$id}")->save(array('status'=>3,'reason'=>$_POST['reason']));
			if($z){
				$this->success('操作成功',U('houseAudit'));
			}else{
				$this->error('操作失败');
			}
		}else{
			$this->display();
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
	
	public function ajaxStatistics($dt=0) {
		$date= array();
		$click= array();
		if($dt==0) {
			$sql='SELECT date,SUM(click) as click FROM bk_house_click GROUP BY date';
			$data=M()->query($sql);
			foreach ($data as $k => $v) {
				array_push($date,$v['date']);
				array_push($click,$v['click']);
			}
		} else if($dt==1) {
			$weekSql='SELECT WEEK(now(),1)as week';
			$week=(M()->query($weekSql));
			$week=$week[0]['week'];
			$sql='SELECT WEEK(date,1)as date,SUM(click) as click FROM bk_house_click GROUP BY WEEK(date,1)';
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
			$sql='SELECT substring(date,1,7) AS date, sum(click) as click FROM bk_house_click GROUP BY substring(date,1,7)';
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
			$sql='SELECT province as area,count(id) as click FROM bk_houseinfo GROUP BY area';
			$data=M()->query($sql);
			foreach ($data as $k => $v) {
				array_push($date,$v['area']);
				array_push($click,$v['click']);
			}
		} else {
			$sql='SELECT substring(createtime,1,7) AS date,count(id) as click FROM bk_houseinfo GROUP BY substring(createtime,1,7)';
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
		
	public function withdraw() {
		$status = I('get.status');
		if (!is_null($status)) {
			$where['w.status'] = array('eq',$status);
			$this->assign("status",$status);
		}
		$data=M('withdraw as w')->JOIN("INNER JOIN bk_owner as o ON w.uid=o.id")->
			field("w.id,w.uid,w.name,w.card_type,w.card_no,w.amount,w.status,w.createtime,o.nickname,o.phone,w.reason")->WHERE($where)->SELECT();
		$this->assign("data",$data);
		$this->display();
	}
	
			
	public function doWithdraw() {
		$id = I('get.id');
		$status = I('get.status');
		$c = M('withdraw')->where("id={$id}")->setField("status",$status);
		if ($c) {
			$this->success('处理成功');
		}else{
			$this->error('处理失败');
		}
	}
	
	public function rejectWithdraw() {
		if(IS_POST) {
			$id = $_POST['id'];
			$c = M('withdraw')->where("id={$id}")->save(array('status'=>2,'reason'=>$_POST['reason']));
			if ($c) {
				$this->success('处理成功',U('withdraw'));
			}else{
				$this->error('处理失败');
			}
		} else {
			$this->display();
		}
	}
}
 ?>