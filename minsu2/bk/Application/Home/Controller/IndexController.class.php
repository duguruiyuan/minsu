<?php 
namespace Home\Controller;
use Think\Controller;
/**
* 众筹
*/
class IndexController extends Controller{
	public function index() {
		header('Location:http://mei.vshijie.cn/bk/index.php/Home/House/home');
	}
	
	public function crons() { 
		file_put_contents("erdangjiade.txt", date("Y-m-d H:i:s") . "\r\n<br>", FILE_APPEND); 
	}
}
 ?>