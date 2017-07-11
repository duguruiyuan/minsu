<?php 
namespace Admin\Controller;
use Think\Controller;

/**
 * 管理员列表控制器
 */
class AdminController extends AuthController{
    /**
     * 管理员列表
     */
    public function index(){
    	// 数据的条数
    	$number = D("Manager")->count();
		$this->assign('number',$number);
		
        // 展示模板信息
	    $this->display('admin-list');
    }
	
	/**
	 * 添加管理员
	 */
	public function addData(){
		$M = new \Model\ManagerModel();
		if(!empty($_POST)){
			// 收集表单信息[$_POST]并返回，同时触发表单自动验证，过滤非法字段
			// 返回真实有效的数据的时候才进行添加
			if(!$M->create()){
				// 输出错误的验证信息
				$this->error($M->getError(),'Index/index',0.2);
			}else{
				// 获取post提交的用户名和密码
				$name = $_POST['mg_name'];
				$password = md5($_POST['mg_pwd']);
				$id = $_POST['hj_role_role_id'];
				$downid = $_POST['downid'];
				if(!empty($downid)){
					$data = array(
					'mg_name' => $name,
					'mg_pwd' => $password,
					'hj_role_role_id' => $id,
					'downid' => $downid
					);
				}else{
					// 重组数据以便提交
					$data = array(
						'mg_name' => $name,
						'mg_pwd' => $password,
						'hj_role_role_id' => $id,
						'downid' => 0
					);
				}
				// 返回自增id
				$zid = D("Manager")->add($data);
				if($zid){
					$this->success('添加管理员成功','Index/index',0.2);
				}else{
					$this->error('添加管理员失败','Index/index',0.2);
				}
			}
		}else{
			// 角色信息
			$info = D("Role")->select();
			$this->assign('info',$info);
			// 下游信息表,公司id和名称
			// $downinfo = M("downinfo")->field("downid,down_name")->select();
			// $this->assign('downinfo',$downinfo);
			// 展示模板信息
		    $this->display('admin-add');
		}
	}
	
	/**
	 * 编辑管理员
	 */
	public function editData(){
		$M = new \Model\ManagerModel();
		if(!empty($_POST)){
			if(!$M->create()){
				// 输出错误的验证信息
				$this->error($M->getError(),'Index/index',0.2);
			}else{
				// 获取隐藏域中的id，以便查询数据库
				$mg_id = I('post.mg_id',0,'int');
				// 获取post提交的用户名和密码
				$name = $_POST['mg_name'];
				$password = md5($_POST['mg_pwd']);
				$id = $_POST['hj_role_role_id'];
				// 重组数据以便提交
				$data = array(
					'mg_name' => $name,
					'mg_pwd' => $password,
					'hj_role_role_id' => $id,
				);
				// 返回自增id
				$zid = D("Manager")->where("mg_id={$mg_id}")->save($data);
				if($zid){
					$this->success('编辑管理员成功','Index/index',0.2);
				}else{
					$this->error('编辑管理员失败','Index/index',0.2);
				}
			}
		}else{
			// 获取地址栏中的id，以便查询数据库
			$mg_id = I('get.mg_id',0,'int');
			$mgInfo = D("Manager")->where("mg_id={$mg_id}")->find();
			$this->assign('mgInfo',$mgInfo);
			// 角色信息
			$info = D("Role")->select();
			$this->assign('info',$info);
			// 展示模板信息
		    $this->display('admin-edit');
		}
	}

	
	/**
	 * 异步操作 管理员-停用
	 */
	public function admin_stop(){
	    if(!IS_AJAX) return;
	    $mg_id = $_POST['mg_id'];
		$data['is_lock'] = 1;
		$result = D("Manager")->where("mg_id = {$mg_id}")->save($data);
		if($result){
			$data['status'] = '1';
			$this->ajaxReturn($data);
		}else{
			$data['status'] = '0';
			$this->ajaxReturn($data);
		}
	}
	
	/**
	 * 异步操作 管理员-启用
	 */
	public function admin_start(){
	    if(!IS_AJAX) return;
	    $mg_id = $_POST['mg_id'];
		$data['is_lock'] = 0;
		$result = D("Manager")->where("mg_id = {$mg_id}")->save($data);
		if($result){
			$data['status'] = '1';
			$this->ajaxReturn($data);
		}else{
			$data['status'] = '0';
			$this->ajaxReturn($data);
		}
	}
	
	/**
	 * 异步操作 管理员-删除
	 */
	public function admin_del(){
	    if(!IS_AJAX) return;
	    $mg_id = $_POST['mg_id'];
		$result = D("Manager")->where("mg_id = {$mg_id}")->delete();
		if($result){
			$data['status'] = '1';
			$this->ajaxReturn($data);
		}else{
			$data['status'] = '0';
			$this->ajaxReturn($data);
		}
	}
	
	/**
	 * 异步操作 检测用户名是否存在
	 */
	public function checkname(){
	    if(!IS_AJAX) return;
		$mg_name = $_POST['mg_name'];
		$result = D("Manager")->where("mg_name='{$mg_name}'")->find();
		if($result){
			$data['status'] = '1';
			$this->ajaxReturn($data);
		}else{
			$data['status'] = '0';
			$this->ajaxReturn($data);
		}
	}
 }
 ?>