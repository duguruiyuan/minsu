<?php

namespace Admin\Controller;
use Think\Controller;
// 页面编码
header("Content-type:text/html;charset=utf-8");
/**
 * 登录验证控制器
 */
class AuthController extends Controller{
    /**
     *初始化方法(自动实行)
     */
    public function  __construct(){
      parent:: __construct();
      // 如果aid或者aname不存在，就认为没有登录
      if(!session('aid') || !session('aname')){
        // 直接跳转到后台登陆界面
		$this->redirect('Login/index', array(), 1, '页面跳转中...');
      }

	  // 获得当前用户访问的'控制器/操作方法'权限信息
	  $ca = CONTROLLER_NAME . '-' . ACTION_NAME;
	  // 获得当前用户访问的'允许'访问的权限信息
	  // admin_id   role   auth

	  // 获得当前登陆系统管理员信息，进而获得角色id
	  $admin_id = session('aid');
	  $admin_name = session('aname');
	  $manager_info = D("Manager")->where("mg_id = {$admin_id}")->find();
	  // 角色id
	  $roleid = $manager_info['hj_role_role_id'];
	  // 根据$roleid 获得角色信息
	  $roleinfo = D("Role")->where("role_id = {$roleid}")->find();
	  // 获得角色对应权限'控制器-操作方法'信息
	  $auth_ca = $roleinfo['role_auth_ca'];

	  // 默认允许大家都可以访问的权限
	  $allow_ca = "Index-index,Login-index,Login-out,Public-index";
	  // strpos() 判断一个小的字符串在一个大的字符串中'第一次'出现的位置
	  // 访问过滤判断
	  // 1)当前访问的权限没有出现在其'拥有'的列表里面
	  // 2)当前访问的权限也没有出现在'默认允许访问'里面
	  // 3)访问者还不是admin超级管理员
	  if(strpos($auth_ca,$ca)===false && strpos($allow_ca,$ca)===false && $admin_name!=='admin'){
	  	$this->redirect('Index/index', array(), 1, '没有访问权限...');
	  }

    	// 根据管理员获得其角色，进而获得角色对应的权限
        // 根据管理员id信息获得其本身记录信息
        $admin_id   = session('aid');
        $admin_name = session('aname');
        $manager_info = D('Manager')->where("mg_id=$admin_id")->find();
    	// 查询到角色id
        $role_id = $manager_info['hj_role_role_id'];

    	// 根据$role_id获得本身记录信息
        $role_info = D('Role')->where("role_id=$role_id")->find();
        $auth_ids = $role_info['role_auth_ids'];

    	// 根据$auth_ids 获得具体权限  ps  超级管理员只能admin 
        if($admin_name === 'admin'){
            //admin超级管理员显示全部权限
            $auth_infoA = D('Auth')->where("auth_level=0")->order("auth_sort")->select();//父级
            $auth_infoB = D('Auth')->where("auth_level=1")->order("auth_sort")->select();//子级
        } else{
            $auth_infoA = D('Auth')->where("auth_level=0 and auth_id in($auth_ids)")->order("auth_sort")->select();//父级
            $auth_infoB = D('Auth')->where("auth_level=1 and auth_id in($auth_ids)")->order("auth_sort")->select();//子级
        }

        //根据权限分配只显示那些内容
        $this -> assign('auth_infoA',$auth_infoA);
        $this -> assign('auth_infoB',$auth_infoB);

    }
}












 ?>
