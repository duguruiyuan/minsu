<?php
namespace Home\Controller;
use Think\Controller;

// 首页控制器
class IndexController extends Controller {
	// 页面展示
    public function index()
    {
        $firstCity = $_SESSION['city'];
        if (!$firstCity) {
            session('city','北京市');
        }

        $searchCity = session('city');
        if ($searchCity == '全国') {
            $searchCity = '';
        }

    	// 活动列表推荐***
    	$sql="SELECT * FROM ms_houseinfo where status=2 and placed_top=0 and address like '%{$searchCity}%'  ORDER BY RAND() LIMIT 15";
		$data=M()->query($sql);
		
        $where = array();
        if ($searchCity) $where['address'] = array('like','%'.$searchCity.'%');
        $where['placed_top'] = array('eq',1);

        $result = M('houseinfo')->where($where)->select();

        foreach ($data as $key => $value) {
            $result[] = $value;
        }

		foreach ($result as $k => $v) {
			$n = strrchr($v['address'], ',');
			$n = ltrim($n,',');
			$result[$k]['address_fine'] = $n;
		}	
		//轮播图添加
		$carousel_data = M('carousel')->order('carousel_sort desc')->select();
		$this->assign('carousel_data',$carousel_data);

        //城市选择
        $province = S('province');
        if(!$province){
            $province = M('s_province')->select();
            S('province',$province,0);
        }

        //根据ip获得地址
        /*$ip = get_client_ip();
        
        $Ip = new \Org\Net\IpLocation('UTFWry.dat'); // 实例化类 参数表示IP地址库文件
        $area = $Ip->getlocation(); // 获取某个IP地址所在的位置
        p($area);*/


        $this->assign('province',$province);
		
    	$this->assign('data',$result);
// p($result);
    	$this->display();
    }

    public function ajaxCity($city,$u) {
        session('city',$city);
        setcookie(session_name(),session_id(),time() + 3600*24*7,'/');
        $data['code'] = 'ok';
        $data['city'] = $city;
        header('Location: '.$u);
    }


    //商务合作
    public function problem()
    {
        $this->assign('data',M('about')->find());
    	$this->display();
    }

    //委托发布
    public function entrust()
    {
        $this->assign('data',M('entrust')->find());
        $this->display();
    }

    //留言管理
    public function message() {
    	if (IS_POST) {
    		$z = M('message')->add($_POST);
    		if ($z) {
    			$this->redirect('Release/success_house');
    		}else{
    			$this->redirect('Release/error_house');
    		}
    	}else{
    		$openid=cookie('oid');
    		if($openid==null || strlen($openid)<12) {
	    		$user_agent = $_SERVER['HTTP_USER_AGENT'];
	    		if (strpos($user_agent, 'MicroMessenger')) {
	    			$url ='http://mei.vshijie.cn/minsu/index.php/Home/Weixin/callOpenid?u='.urlencode('http://mei.vshijie.cn/minsu/index.php/Home/Index/message');
					header('Location: '.$url);
					return;
	    		}
    		}
    		$this->assign('openid',$openid);
    		$this->display();
    	}
    	
    }

    public function search()
    {
    	$this->display();
    }

    public function search_page()
    {
		$search = I('post.search');
        $region = I('post.region','');
        $num = I('post.num','');
        $where['status'] = array('eq',2);

        $searchCity = session('city');
        if ($searchCity == '全国') {
            $searchCity = '';
        }

        if ($search) $where['name'] = array('like','%'.$search.'%');
        if ($region){
            $where['address'] = array('like','%'.$region.'%');
            session('city',$region);
        }elseif ($searchCity) {
            $where['address'] = array('like','%'.$searchCity.'%');
        } 
        if ($num) $where['serial_num'] = array('eq',$num);

		$data = M('houseinfo')->where($where)->order('add_time desc')->select();

		foreach ($data as $k => $v) {
			$n = strrchr($v['address'], ',');
			$n = ltrim($n,',');
			$data[$k]['address_fine'] = $n;
		}
		$this->assign('data',$data);

        $province = S('province');
        if(!$province){
            $province = M('s_province')->select();
            S('province',$province,0);
        }
        $this->assign('province',$province);
		$this->display();
    	
    }

	public function coop() {
		$c = M('coop')->where("type=0")->find();
		$this->assign('coop',$c);
		$this->display();
	}
}