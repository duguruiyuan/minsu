<?php 

namespace Home\Controller;
use Think\Controller;


/**
* 搜索控制器
*/
class SearchController extends Controller
{
	
	public function index($name=null)
	{
		$searchCity = session('city');
        if ($searchCity == '全国') {
            $searchCity = '';
        }
        $page = 1;
		if ($name!=null) {
			$where['address'] = array('like','%'.$name.'%');
			session('city',$name);
		}elseif ($searchCity) {
            $where['address'] = array('like','%'.$searchCity.'%');
        }
		$houseinfo = M('houseinfo');
		$where['status'] = array('eq',2);
		$data = $houseinfo->order('list_top desc,add_time desc')->page("{$page},5")->where($where)->select();
		foreach ($data as $k => $v) {
			$n = strrchr($v['address'], ',');
			$n = ltrim($n,',');
			$data[$k]['address_fine'] = $n;
		}
		$province = S('province');
		if(!$province){
			$province = M('s_province')->select();
			S('province',$province,0);
		}
		$this->assign('province',$province);
		
		$this->assign('data',$data);
//		p($data);
		$this->display();
	}


	public function goodHouse()
	{
		$searchCity = session('city');
        if ($searchCity == '全国') {
            $searchCity = '';
        }

		$sql="SELECT * FROM ms_houseinfo where status=2 and address like '%{$searchCity}%'  ORDER BY RAND() LIMIT 15";
		$data=M()->query($sql);

		foreach ($data as $k => $v) {
			$n = strrchr($v['address'], ',');
			$n = ltrim($n,',');
			$data[$k]['address_fine'] = $n;
		}
		$province = S('province');
		if(!$province){
			$province = M('s_province')->select();
			S('province',$province,0);
		}
		$this->assign('province',$province);
		
		$this->assign('data',$data);
		$this->display();

	}

	public function ajaxSearch()
	{
		if(!IS_AJAX) return;
		$search = I('post.search','');
		$num = I('post.num','');

		$searchCity = session('city');
        if ($searchCity == '全国') {
            $searchCity = '';
        }

		if ($searchCity) {
            $where['address'] = array('like','%'.$searchCity.'%');
        }

		if ($search) {
			$where['name'] = array('like','%'.$search.'%');
		}
		if($num){
			$where['serial_num'] = array('eq',$num);
		}
		
		$where['status'] = array('eq',2);

    	$data = M('houseinfo')->where($where)->order('add_time desc')->select();

    	foreach ($data as $k => $v) {
			$n = strrchr($v['address'], ',');
			$n = ltrim($n,',');
			$data[$k]['address_fine'] = $n;
			$data[$k]['url_lo'] = __ROOT__.'/index.php/Home/Pay/house?hid='.$v['hid'];
		}

    	$this->ajaxReturn($data);
	} 

	public function city($id)
	{
		$city = M('s_city')->where("ProvinceID={$id}")->select();
		$this->assign('city',$city);
		$this->display();
	}

	//上拉加载刷新
	//
	//
	public function ajaxUploadRefresh()
	{
		if(!IS_AJAX) return false;

		$page = I('post.page',1,'int');

		$searchCity = session('city');
        if ($searchCity == '全国') {
            $searchCity = '';
        }

		$name = I('get.name','');
		if ($name) {
			$where['address'] = array('like','%'.$name.'%');
		}elseif ($searchCity) {
            $where['address'] = array('like','%'.$searchCity.'%');
        }
		$houseinfo = M('houseinfo');
		$where['status'] = array('eq',2);
		$data = $houseinfo->order('add_time desc')->page("$page,5")->where($where)->select();

		if($data){
			foreach ($data as $k => $v) {
				$n = strrchr($v['address'], ',');
				$n = ltrim($n,',');
				$data[$k]['address_fine'] = $n;
				$data[$k]['url_lo'] = __ROOT__.'/index.php/Home/Pay/house?hid='.$v['hid'];
			}
		}else{
			$map = array(
				'code' => 1,
			);
			$this->ajaxReturn($map);die;
		}
		$this->ajaxReturn($data);

	}
}
 ?>