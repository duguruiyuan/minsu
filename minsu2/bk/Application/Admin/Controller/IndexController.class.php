<?php
namespace Admin\Controller;
use Think\Controller;

//首页控制器
class IndexController extends AuthController {
	// 页面展示
    public function index(){
		// 显示模板信息
    	$this->display();
    }


}
