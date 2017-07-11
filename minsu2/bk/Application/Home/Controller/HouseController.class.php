<?php 

namespace Home\Controller;
use Think\Controller;

/**
* 详情控制器
*/
class HouseController extends Controller {
	public function index($id){
		$houseImg = M('houseimg')->WHERE("houseinfo_hid={$id}")->select();
		$this->assign('houseImg',$houseImg);
		$this->assign('total',count($houseImg));
		$today = date("Y-m-d");
		$sql="insert into bk_house_click (hid,date)values({$id},'{$today}') ON DUPLICATE KEY UPDATE click=click+1";
		M()->execute($sql);
		$result =M('houseinfo')->where("id={$id}")->find();
		$this->assign('result',$result);
		$this->assign('ad',M('ad')->find());
		$user_agent = $_SERVER['HTTP_USER_AGENT'];
		if (strpos($user_agent, 'MicroMessenger')) {
			$this->assign('isweixin',1);
		}
		$sql = "SELECT c.*,q.r_name FROM bk_h_comment AS c INNER JOIN bk_h_time_queue AS q ON c.qid=q.id WHERE c.hid={$id} ORDER BY c.updatetime DESC";
		$data = M()->query($sql);
		$this->assign('data',$data);
		$this->display();
	}
	
	public function home($r=0, $key=null, $tag=null, $jiage=null, $near=null) {
		$where['status'] = array('eq',2);
		if($tag!=null) {
			$_SESSION['tag']=$tag;
		}
		if($jiage!=null) {
			$_SESSION['jiage']=$jiage;
		}
		if($near!=null) {
			$_SESSION['near']=$near;
		}
		if($r==1) {
			unset($_SESSION['tag']);
		} else if($_SESSION['tag']!=null){
			$where['tags'] = array('like','%'.$_SESSION['tag'].'%');
		}
		if($_SESSION['province']==null || $_SESSION['province']=='') {
			$url='http://ip.taobao.com/service/getIpInfo.php?ip='.getIP();
			$dataJson = file_get_contents($url);
			$data=json_decode($dataJson);
			$_SESSION['province']=$data->data->region;
		}
		if($_SESSION['city']!=null) {
			$where['city'] = array('eq', $_SESSION['city']);
		}
		if($_SESSION['jiage']!=null) {
			if($_SESSION['jiage']=='300以下') {
				$where['price'] = array('LT', 300);
			} else if($_SESSION['jiage']=='300-600') {
				$where['price'] = array('between', '300,600');
			} else if($_SESSION['jiage']=='600以上') {
				$where['price'] = array('gt', 600);
			}
		}
		if($_SESSION['near']!=null) {
			if($_SESSION['near']=='由近及远') {
				$order = 'county';
			} else if($_SESSION['near']=='由远及近') {
				$order = 'county desc';
			} else {
				$order='id desc';
			}
		}
		if($key!=null && $key!='') {
			$keyLike='%'.$key.'%';
			$condition['city'] = array('like', $keyLike);
			$condition['county'] = array('like', $keyLike);
			$condition['address'] = array('like', $keyLike);
			$condition['name'] = array('like', $keyLike);
			$condition['tags'] = array('like', $keyLike);
			$condition['_logic'] = 'OR';
			$where['_complex'] = $condition;
		}
		$where['province'] = array('eq',$_SESSION['province']);
		$result = M('houseinfo')->WHERE($where)->order($order)->select();
//		var_dump(M('houseinfo')->WHERE($where)->order($order));
		$this->assign('data',$result);
		
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
        $this->assign('key',$key);
        if(!$_SESSION['begin']) {
        	$_SESSION['begin']=date("Y年m月d日").'(入住)';
        }
        if(!$_SESSION['end']) {
        	$_SESSION['end']=date("Y年m月d日",strtotime("+1 days")).'(退房)';;
        }
		$this->display();
	}
	
	public function beginSession($d) {
		$_SESSION['begin']=$d;
		$this->ajaxReturn($_SESSION['begin']);
	}
		
	public function endSession($d) {
		$_SESSION['end']=$d;
		$this->ajaxReturn($_SESSION['end']);
	}
	
	public function story($oid) {
		$data = M('owner')->where("id={$oid}")->find();
		$this->assign('data',$data);
		$this->display();
	}
	
	public function map($place) {
		$this->assign('place',$place);
		$this->display();
	}
	
    public function ajaxProvince($province,$u) {
        session('province',$province);
        session('city',null);
        setcookie(session_name(),session_id(),time() + 3600*24*7,'/');
        $data['code'] = 'ok';
        $data['province'] = $province;
        header('Location: '.$u);
    }

	public function city($id,$province){
		$city = M('s_city')->where("ProvinceID={$id}")->select();
		session('province',$province);
		$this->assign('city',$city);
		$this->display();
	}
	
    public function ajaxCity($city) {
        session('city',$city);
        setcookie(session_name(),session_id(),time() + 3600*24*7,'/');
        $data['code'] = 'ok';
        $data['city'] = $city;
        header('Location: http://mei.vshijie.cn/bk/index.php/Home/House/home');
    }
    
    public function search() {
    	$this->display();
    }
	
	public function scores($hid) {
		$sql = "SELECT c.*,q.r_name FROM bk_h_comment AS c INNER JOIN bk_h_time_queue AS q ON c.qid=q.id WHERE c.hid={$hid} ORDER BY c.updatetime DESC";
		$data = M()->query($sql);
		$this->assign('data',$data);
		$this->display();
	}
	
    public function search_page() {
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

		$data = M('houseinfo')->where($where)->order('createtime desc')->select();

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
}
 ?>
