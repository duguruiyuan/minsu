<?php
namespace Admin\Controller;
use Think\Controller;

/**
 * 权限管理控制器
 */
class AuthsController extends AuthController{
	/**
	 * 权限管理
	 */
	public function index(){
		// 统计数量
		$number = D("Auth")->count();
		$this->assign('number',$number);
		// 获取全部信息并展示给模板
		// 按照'auth_path'排序获得数据，以便信息按照'上下级'形式输出
		$info = D("Auth")->order("auth_path")->select();
		$this->assign('info',$info);
		// 展示模板
	    $this->display('admin-permission');
	}

	/**
	 * 添加权限节点
	 */
	public function addData(){
		$auth = new \Model\AuthModel();
		// 自动验证规则
		$rules = array(
			array('auth_name','require','权限名称必须填写!'),
		);
		if(!empty($_POST)){
			if (!$auth->validate($rules)->create()){
				// 如果创建失败 表示验证没有通过 输出错误提示信息
				$this->error($auth->getError(),U('Auths/index'),0.2);
			}else{
				if($_POST['auth_pid'] == 0){
					// 收集信息(name pid controller action)
					$z = $auth->saveData($_POST);
					if($z){
						$this->success('添加权限节点成功',U('Auths/index'),0.2);
					}else{
						$this->error('添加权限节点失败',U('Auths/index'),0.2);
					}
				}else{
					$auth_c = $_POST['auth_c'];
					$auth_a = $_POST['auth_a'];
					$result = D("Auth")->where("auth_c='{$auth_c}' and auth_a='{$auth_a}'")->find();
					if($result){
						$this->error('添加权限节点失败',U('Auths/index'),0.2);
					}else{
						// 收集信息(name pid controller action)
						$z = $auth->saveData($_POST);
						if($z){
							$this->success('添加权限节点成功',U('Auths/index'),0.2);
						}else{
							$this->error('添加权限节点失败',U('Auths/index'),0.2);
						}
					}
				}

			}
		}else{
			// 获得父级权限信息
			$father = D("Auth")->where("auth_level = 0")->select();
			$this->assign('father',$father);
			// 展示模板
		    $this->display('admin-add');
		}
	}

	/**
	 * 编辑权限节点
	 * $auth_id 是从地址栏中读取到的,以便更方便的查询到这条数据
	 */
	public function editData($auth_id){
		$auth = new \Model\AuthModel();
		if(!empty($_POST)){
			$pid = D("Auth")->where("auth_id={$auth_id}")->find();
			if($pid['auth_pid'] == 0){
				if(is_null($_POST['auth_pid'])) {
					$_POST['auth_pid']=0;
				}
				$data = array(
					'auth_name' => $_POST['auth_name'],
					'auth_pid' => $_POST['auth_pid'],
					'auth_icon' => $_POST['auth_icon'],
					'auth_path' => $auth_id,
					'auth_level' => 0,
					'auth_c' => '',
					'auth_a' => '',
					'auth_add' => '',
					'auth_edit' => '',
					'auth_del' => '',
					'auth_sort' => $_POST['auth_sort'],
				);
				$z = D("Auth")->where("auth_id={$auth_id}")->save($data);
				if($z){
					$this->success('编辑权限节点成功',U('Auths/index'),0.2);
				}else{
					$this->error('编辑权限节点失败',U('Auths/index'),0.2);
				}
			}else{
				$pinfo = D("Auth")->where("auth_id={$auth_id}")->find();
        		$path = $pinfo['auth_pid']."-".$auth_id;
				$level = substr_count($path,'-');
				$data = array(
					'auth_name' => $_POST['auth_name'],
					'auth_pid' => $_POST['auth_pid'],
					'auth_path' => $path,
					'auth_level' => $level,
					'auth_c' => $_POST['auth_c'],
					'auth_a' => $_POST['auth_a'],
					'auth_add' => $_POST['auth_add'],
					'auth_edit' => $_POST['auth_edit'],
					'auth_del' => $_POST['auth_del'],
					'auth_icon' => '',
					'auth_sort' => $_POST['auth_sort'],
				);
				//先删除 后添加
				M('other')->where("hj_auth_auth_id={$auth_id}")->delete();
				// 添加其他操作方法
				if(!empty($_POST['other_name']) && !empty($_POST['other_a'])) {
					// 先查询数据库中有没有相关联的数据,没有就添加，有就修改
					$otherInfo = D("Other")->where("hj_auth_auth_id={$auth_id}")->select();
					if($otherInfo){
						$onn = explode('丨', $_POST['other_name']);
						$oaa = explode('丨', $_POST['other_a']);
						// 定义一个空的数字存放自增id返回的值
						$ids = array();
						foreach ($otherInfo as $k => $v) {
							$da['other_name'] = $onn[$k];
							$da['other_a'] = $oaa[$k];
							$zid = D("Other")->where("other_id = {$v['other_id']}")->save($da);
							$ids[] = $zid;
						}

					}else{
						$on = explode('丨', $_POST['other_name']);
						$oa = explode('丨', $_POST['other_a']);
						// 定义一个空的数字存放自增id返回的值
						$ids = array();
						foreach ($on as $k => $v) {
							// 重组数组
							$other = array(
								'other_name' => $v,
								'other_a' => $oa[$k],
							);
							$otherid = D("Other")->add($other);
							$ids[] = $otherid;
						}
						// 循环自增id以便更新数据
						foreach ($ids as $key => $value) {
							// 等级划分
							$other_level = $pid['auth_path']."-".$value;
							// 再次重组以便更新数据
							$otherData = array(
								'other_pid' => $auth_id,
								'other_level' => substr_count($other_level,'-'),
								'hj_auth_auth_id' => $auth_id,
							);
							$ids = D("Other")->where("other_id = {$value}")->save($otherData);
						}
					}

				}
				$z = D("Auth")->where("auth_id={$auth_id}")->save($data);
				if($z || $ids){
					$this->success('编辑权限节点成功',U('Auths/index'),0.2);
				}else{
					$this->error('编辑权限节点失败',U('Auths/index'),0.2);
				}
			}

		}else{
			$info = D("Auth")->where("auth_id={$auth_id}")->find();
			$this->assign('info',$info);
			// 获得父级权限信息
			$father = D("Auth")->where("auth_level = 0")->select();
			$this->assign('father',$father);
			// 获得其他操作方法
			$otherInfo = D("Other")->where("hj_auth_auth_id={$auth_id}")->select();
			$on = '';
			$oa = '';
			foreach ($otherInfo as $k => $v) {
				$on .= $v['other_name'] . "丨" ;
				$oa .= $v['other_a'] . "丨" ;
			}
			$on = rtrim($on,'丨');
			$oa = rtrim($oa,'丨');
			$this->assign('on',$on);
			$this->assign('oa',$oa);
			// 展示模板
		    $this->display('admin-edit');
		}
	}

	/**
	 * 异步操作 检测权限名称是否存在
	 */
	public function checkname(){
	    if(!IS_AJAX) return;
		$auth_name = $_POST['auth_name'];
		$result = D("Auth")->where("auth_name='{$auth_name}'")->find();
		if($result){
			$data['status'] = '1';
			$this->ajaxReturn($data);
		}else{
			$data['status'] = '0';
			$this->ajaxReturn($data);
		}
	}

	/**
	 * 异步操作 判断权限节点
	 */
	public function admin_check(){
	    if(!IS_AJAX) return;
		$auth = new \Model\AuthModel();
		// 接受post传递的auth_id
		$auth_id = $_POST['auth_id'];
		$result = D("Auth")->where("auth_id='{$auth_id}'")->find();
		// 判断是父级权限
		if($result['auth_pid'] == 0){
			$data['status'] = '1';
			$this->ajaxReturn($data);
		}else{
			$data['status'] = '0';
			$this->ajaxReturn($data);
		}
	}

	/**
	 * 异步操作 删除权限节点
	 */
	public function admin_del(){
	    if(!IS_AJAX) return;
		$auth = new \Model\AuthModel();
		// 接受post传递的auth_id
		$auth_id = $_POST['auth_id'];
		$result = D("Auth")->where("auth_id='{$auth_id}'")->find();
		// 判断是父级权限节点的时候 删除父级节点以及子节点，否则就删除子节点
		if($result['auth_pid'] == 0){
			// 查询到所有的权限节点数据
			$allData = D("Auth")->select();
			$auth_ids = $auth->getSon($auth_id);
			// 转成字符
			$auth_ids = implode(',', $auth_ids);
			$num = strpos($auth_ids,',');
			$z = D("Auth")->where("auth_id in ({$auth_ids})")->delete();
			// 删除其他操作方法
			$s = D("Other")->where("hj_auth_auth_id in ({$auth_ids})")->delete();
			if($z && $s){
				$data['del'] = '1';
				$data['num'] = $num;
				$this->ajaxReturn($data);
			}else{
				$data['del'] = '0';
				$this->ajaxReturn($data);
			}
		}else{
			// 删除子节点
			$z = D("Auth")->where("auth_id='{$auth_id}'")->delete();
			// 删除其他操作方法
			$s = D("Other")->where("hj_auth_auth_id='{$auth_id}'")->delete();
			if($z && $s){
				$data['del'] = '1';
				$this->ajaxReturn($data);
				$data['del'] = '0';
				$this->ajaxReturn($data);

			}

		}
	}
}

?>
