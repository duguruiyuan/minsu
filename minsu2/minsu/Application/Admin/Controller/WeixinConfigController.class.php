<?php 
namespace Admin\Controller;
use Think\Controller;
/**
 * 登入控制器
 */
class WeixinConfigController extends Controller{

	public function msgReply() {	
		$result = M('wx_msg_reply')->select();
		$this->assign('result',$result);
		$this->display();
	}

	public function addMsgReply() {
		if (IS_POST) {
			$_POST['createtime'] = date('Y-m-d H:i:s');
			if(M('wx_msg_reply')->add($_POST)) {
				$this->success('添加成功',U('msgReply'));
			}else{
				$this->error('添加失败');
			}
		} else{
			$this->display();
		}
	}
	
	public function editMsgReply($id) {
		if (IS_POST) {
			if(M('wx_msg_reply')->where("id={$id}")->save($_POST)) {
				$this->success('修改成功',U('msgReply'));
			}else{
				$this->error('修改失败');
			}
		} else{
			$result=M('wx_msg_reply')->where("id={$id}")->find();
			$this->assign('result',$result);
			$this->display();
		}
	}
	
	public function delMsgReply($id) {
		if(M('wx_msg_reply')->where("id={$id}")->delete()) {
			$this->success('删除成功',U('msgReply'));
		}else{
			$this->error('删除失败');
		}
	}
 }
 ?>