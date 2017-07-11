<?php 

namespace Admin\Controller;
use Think\Controller;
/**
 * 登入控制器
 */
class LoginController extends Controller{
    /**
     * 后台首页登入界面
     */
    public function index(){
    	// 如果用户没有提交就显示模板
    	if(!empty($_POST)){
    		// 获得post提交的用户名和密码
			$name = I('post.mg_name');
			// 接收post里面的password，并且给默认值，md5加密
			$pwd = I('post.mg_pwd','','md5');
			// 查询数据库数据(find是获得一条数据)
			$data = D('manager')->where("mg_name='{$name}'")->find();
			// 判断用户名是否存在
			if(!$data) $this->error('用户名不存在');
			// 判断密码是否存在
        	if($data['mg_pwd'] != $pwd) $this->error('密码不正确');
			if($data['is_lock'] == 1) $this->error('账号已被锁定不能登入');
			// 登录时的时间戳
        	$time = time();
			$add['mg_time'] = $time;
			// 修改数据库中的登入时间
			D('manager')->where("mg_id='{$data['mg_id']}'")->save($add);
			// 如果正确，存入session
	        session('aid',$data['mg_id']);
	        session('aname',$data['mg_name']);
	        session('time',$time);
			session('downid',$data['downid']);
			
			// 登入成功提示
        	$this->success('登录成功',U('Index/index'),0.2);
			die;
    	}
        $this->display(); 
    }
	
	/**
   * 安全退出
   */
  public function out(){
    session_unset('aid');
    //跳转到登入页面
    $this->success('退出成功',U('index'),0.2);
  }
	
 }



 ?>