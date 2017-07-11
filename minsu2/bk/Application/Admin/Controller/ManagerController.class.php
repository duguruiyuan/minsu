<?php
namespace Admin\Controller;
use Think\Controller;

/**
 * 管理员控制器
 */
class ManagerController extends AuthController{
    /**
     * 角色管理
     */
    public function index(){
    	// 共有几条数据统计
    	$number = D('Role')->count();
		$this->assign('number',$number);
    	// 获取角色数据
    	// 通过role_id查询到管理员  ->join("hj_manager ON hj_role.role_id = hj_manager.hj_role_role_id")
    	$info = D('Role')->select();
		// 分配变量
		$this->assign('info',$info);
    	// 展示模板信息
        $this->display('admin-role');
    }

	// 添加角色
	public function roleadd(){
		$role = new \Model\RoleModel();
		if(!empty($_POST)) {
			if(!$role->create()){
				// 如果创建失败 表示验证没有通过 输出错误提示信息
				$this->error($role->getError(),U('index'),0.2);
			}else{
				// 获取auth_ids
				// 去除重复的数字
				$_POST['auth_id'] = array_unique($_POST['auth_id']);
				$authids = implode(',', $_POST['auth_id']);
				// 根据选中的权限id信息，查询对应的权限记录，遍历并获取每个权限的 controller 和 action 信息
				$authinfo = D('Auth')->where("auth_id in ({$authids})")->select();
				if(!empty($_POST['auth_add'])){
					// 获得auth_add 数组中的id
					$auth_addids = implode(',', $_POST['auth_add']);
					// 根据选中的添加id，查询对应的权限记录，得到添加操作方法
					$auth_add = D('Auth')->where("auth_id in ({$auth_addids})")->select();
					// 定义一个空的添加字符串
					$add = '';
					foreach ($auth_add as $k => $v) {
						if(!empty($v['auth_c']) && !empty($v['auth_add']))
						$add .= $v['auth_c']. "-" . $v['auth_add'] . "," ;
					}
				}

				if(!empty($_POST['auth_edit'])){
					// 获得auth_edit 数组中的id
					$auth_editids = implode(',', $_POST['auth_edit']);
					// 根据选中的编辑id，查询对应的权限记录，得到编辑操作方法
					$auth_edit = D('Auth')->where("auth_id in ({$auth_editids})")->select();
					// 定义一个空的编辑字符串
					$edit = '';
					foreach ($auth_edit as $k => $v) {
						if(!empty($v['auth_c']) && !empty($v['auth_edit']))
						$edit .= $v['auth_c']. "-" . $v['auth_edit'] . "," ;
					}
				}

				if(!empty($_POST['auth_del'])){
					// 获得auth_del 数组中的id
					$auth_delids = implode(',', $_POST['auth_del']);
					// 根据选中的删除id，查询对应的权限记录，得到删除操作方法
					$auth_del = D('Auth')->where("auth_id in ($auth_delids)")->select();
					// 定义一个空的删除字符串
					$del = '';
					foreach ($auth_del as $k => $v) {
						if(!empty($v['auth_c']) && !empty($v['auth_del']))
						$del .= $v['auth_c']. "-" . $v['auth_del'] . "," ;
					}
				}

				//其他操作方法
				if(!empty($_POST['other_a'])){
					// 获得othar_name 数组中的id
					$other_aids = implode(',', $_POST['other_a']);
					// 根据选中的删除id，查询对应的权限记录，得到操作方法
					$other_name = D('Other')->where("hj_auth_auth_id in ($other_aids)")->select();
					// 定义一个空的删除字符串
					$other = '';
					foreach ($other_name as $k => $v) {
						if(!empty($v['other_a'])){
							$auth_pid_c = D('Auth')->where("auth_id = {$v['other_pid']}")->find();
							if(!empty($auth_pid_c['auth_c'])){
								$other .= $auth_pid_c['auth_c']. "-" . $v['other_a'] . "," ;
							}
						}

					}
				}
				// 定义一个空的控制器和操作方法字符串
				$s = '';
				foreach ($authinfo as $k => $v) {
					if(!empty($v['auth_c']) && !empty($v['auth_a']))
					$s .= $v['auth_c']. "-" . $v['auth_a'] . "," ;
				}
				$s = rtrim($s,',');
				// 重组数据以便写入数据库
				$data = array(
					'role_name' => $_POST['role_name'],
					'role_auth_ids' => $authids,
					'role_auth_ca' => $add . $edit . $del . $other .$s,
					'describe' =>$_POST['describe']
				);
				$zid = D('Role')->add($data);
				if($zid) {
					$this->success('添加角色成功','index',0.5);
				}else{
					$this->error('添加角色失败','index',0.5);
				}
			}
		}else{
			// 展示栏目
			$auth_infoA = D('Auth')->where("auth_level=0")->order("auth_sort")->select();//父级
	        $auth_infoB = D('Auth')->where("auth_level=1")->order("auth_sort")->select();//子级
	        $this -> assign('auth_infoA',$auth_infoA);
	        $this -> assign('auth_infoB',$auth_infoB);
			// 其他操作的方法
			$otherinfo = array();
			foreach ($auth_infoB as $k => $v) {
				$otherinfo[$v['auth_id']] = D("Other")->where("{$v['auth_id']} = hj_auth_auth_id")->select();
			}
			$this->assign('otherinfo',$otherinfo);
			// 展示模板信息
		    $this->display('admin-role-add');
		}
	}

	// 编辑角色
	public function roleedit(){
		if(!empty($_POST)){
			// 获取地址栏中的role_id
			$role_id = I('post.role_id',0,'int');
			// 去除重复的数字
			$_POST['auth_id'] = array_unique($_POST['auth_id']);
			// 获取role_auth_ids
			$authids = implode(',', $_POST['auth_id']);
			// 根据选中的权限id信息，查询对应的权限记录，遍历并获取每个权限的 controller 和 action 信息
			$authinfo = D('Auth')->where("auth_id in ({$authids})")->select();

			if(!empty($_POST['auth_add'])){
				// 获得auth_add 数组中的id
				$auth_addids = implode(',', $_POST['auth_add']);
				// 根据选中的添加id，查询对应的权限记录，得到添加操作方法
				$auth_add = D('Auth')->where("auth_id in ({$auth_addids})")->select();
				// 定义一个空的添加字符串
				$add = '';
				foreach ($auth_add as $k => $v) {
					if(!empty($v['auth_c']) && !empty($v['auth_add']))
					$add .= $v['auth_c']. "-" . $v['auth_add'] . "," ;
				}
			}

			if(!empty($_POST['auth_edit'])){
				// 获得auth_edit 数组中的id
				$auth_editids = implode(',', $_POST['auth_edit']);
				// 根据选中的编辑id，查询对应的权限记录，得到编辑操作方法
				$auth_edit = D('Auth')->where("auth_id in ({$auth_editids})")->select();
				// 定义一个空的编辑字符串
				$edit = '';
				foreach ($auth_edit as $k => $v) {
					if(!empty($v['auth_c']) && !empty($v['auth_edit']))
					$edit .= $v['auth_c']. "-" . $v['auth_edit'] . "," ;
				}
			}

			if(!empty($_POST['auth_del'])){
				// 获得auth_del 数组中的id
				$auth_delids = implode(',', $_POST['auth_del']);
				// 根据选中的删除id，查询对应的权限记录，得到删除操作方法
				$auth_del = D('Auth')->where("auth_id in ($auth_delids)")->select();
				// 定义一个空的删除字符串
				$del = '';
				foreach ($auth_del as $k => $v) {
					if(!empty($v['auth_c']) && !empty($v['auth_del']))
					$del .= $v['auth_c']. "-" . $v['auth_del'] . "," ;
				}
			}
			//其他操作方法
			if(!empty($_POST['other_a'])){
				// 获得othar_name 数组中的id
				$other_aids = implode(',', $_POST['other_a']);
				// 根据选中的删除id，查询对应的权限记录，得到操作方法
				$other_name = D('Other')->where("hj_auth_auth_id in ($other_aids)")->select();
				// 定义一个空的删除字符串
				$other = '';
				foreach ($other_name as $k => $v) {
					if(!empty($v['other_a'])){
						$auth_pid_c = D('Auth')->where("auth_id = {$v['other_pid']}")->find();
						if(!empty($auth_pid_c['auth_c'])){
							$other .= $auth_pid_c['auth_c']. "-" . $v['other_a'] . "," ;
						}
					}

				}
			}
			// 定义一个空的字符串
			$s = '';
			foreach ($authinfo as $k => $v) {
				if(!empty($v['auth_c']) && !empty($v['auth_a']))
				$s .= $v['auth_c']. "-" . $v['auth_a'] . "," ;
			}
			$s = rtrim($s,',');
			// 重组数据以便写入数据库
			$data = array(
				'role_id' => $role_id,
				'role_name' => $_POST['role_name'],
				'role_auth_ids' => $authids,
				'role_auth_ca' => $add . $edit . $del . $other .$s,
				'describe' =>$_POST['describe']
			);
			$zid = D('Role')->where("role_id = $role_id")->save($data);
			if($zid) {
				$this->success('编辑角色成功','index',0.5);
			}else{
				$this->error('编辑角色失败','index',0.5);
			}
		}else{
			// 获取地址栏中的role_id
			$role_id = I('get.role_id',0,'int');
			// 查询角色表
			$roleinfo = D('Role')->where("role_id = {$role_id}")->find();
			$roleinfo['role_auth_ids'] = explode(',', $roleinfo['role_auth_ids']);
			$ids = $roleinfo['role_auth_ids'];
			// 分配ids以便检测是不是在这几个当中,选中☑️
			$this->assign('ids',$ids);
			// 定义一个空的数组
			$shuzu = array();
			foreach ($ids as $k => $v) {
				// 查询权限表id并且等级是1的
				$info = D('Auth')->where("auth_id in ($v) and auth_level=1")->group('auth_id')->select();
				foreach ($info as $key => $vv) {
//					p($vv);
					if(!empty($vv)){
						// 压进空的数组中
						$shuzu[$vv['auth_id']] = $vv['auth_c']. "-" . $vv['auth_a']  . "," .$vv['auth_c']. "-" . $vv['auth_add'] . "," .$vv['auth_c']. "-" . $vv['auth_edit'] . "," . $vv['auth_c']. "-" . $vv['auth_del'] ;
					}
				}
			}
			// 查询得到原有选中的控制器和方法
			$ca = $roleinfo['role_auth_ca'];
			$verify = array();
			foreach ($shuzu as $k => $v) {
				$v = explode(',', $v);
				foreach ($v as $key => $vv) {
					if(stripos($ca,$vv)===false){
						$verify[$k][] = $key;
					}else{
						$verify[$k][] = $k;
					}
				}

			}
			// 查询原有选中的其他操作方法
			// 定义一个空的数组
			$kong = array();
			$other_name = array();
			foreach ($ids as $key => $va) {
				// 查询权限表id并且等级是1的
				$authinfo = D('Auth')->where("auth_id in ($va) and auth_level=1")->group('auth_id')->select();
				foreach ($authinfo as $k => $val) {
					if(!empty($val['auth_c'])) {
						$other_name = D("Other")->where("other_pid = {$val['auth_id']}")->select();
						foreach ($other_name as $key => $value) {
							if(!empty($value['other_a'])) {
								$kong[$val['auth_id']][] = $val['auth_c']. "-" . $value['other_a'];
							}
						}
					}
				}
			}
			$ox = array();
			foreach ($kong as $k => $v) {
				foreach ($v as $key => $value) {
					if(stripos($ca,$value)===false){
						$ox[$k][] = $key;
					}else{
						$ox[$k][$key+1] = $k;
					}
				}
			}
			$this->assign('ox',$ox);
			$this->assign('verify',$verify);
			// 分配变量
			$this->assign('roleinfo',$roleinfo);
			// 展示栏目
			$auth_infoA = D('Auth')->where("auth_level=0")->order("auth_sort")->select();//父级
	        $auth_infoB = D('Auth')->where("auth_level=1")->order("auth_sort")->select();//子级
	        $this -> assign('auth_infoA',$auth_infoA);
	        $this -> assign('auth_infoB',$auth_infoB);
			
			// 其他操作的方法
			$otherinfo = array();
			foreach ($auth_infoB as $k => $v) {
				$otherinfo[$v['auth_id']] = D("Other")->where("{$v['auth_id']} = hj_auth_auth_id")->select();
			}
			$this->assign('otherinfo',$otherinfo);
			// 展示模板信息
		    $this->display('admin-role-edit');
		}
	}

	// 异步删除操作
	public function deldata(){
		if(!IS_AJAX) return;
	    $ids = $_POST['role_id'];
		$result = D("Role")->where("role_id = {$ids}")->delete();
		if($result){
			$data['status'] = '1';
			$this->ajaxReturn($data);
		}else{
			$data['status'] = '0';
			$this->ajaxReturn($data);

		}
	}

	/**
	 * 异步检测管理员名字是否存在
	 */
	public function checkname(){
	    if(!IS_AJAX) return;
		$role_name = $_POST['role_name'];
		$result = D("Role")->where("role_name='{$role_name}'")->find();
		if($result){
			$data['status'] = '1';
			$this->ajaxReturn($data);
			$data['status'] = '0';
			$this->ajaxReturn($data);
		}
	}



 }


 ?>
