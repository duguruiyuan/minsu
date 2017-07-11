<?php 
namespace Home\Controller;
use Think\Controller;
/**
* 众筹
*/
class PayController extends Controller{
	const  APP_ID='wx8abf422f863923b3';
	const  SECRET='7976d4dfdaee9f2db765c5f3cc48ffb9';
	const  PAY_KEY='EF52668B6FFF6DBE9AD16488E756E22E';
	const  TOKEN_CACHE_KEY = 'weixin_token';
	const  TICKET_CACHE_KEY = 'weixin_ticket';
	const  MCH_ID=1406051302;	
	public function sign($hid) {
		$gid = session('gid');
		if(!$gid) {
			$redirect='http://mei.vshijie.cn/bk/index.php/Home/Pay/Sign?hid='.$hid;
			$_SESSION['redirect']=$redirect;
			header('Location:http://mei.vshijie.cn/bk/index.php/Home/Guest/home');
		} else {
			$result = M('houseinfo')->WHERE("id={$hid}")->find();
			if(!$_SESSION['begin']) {
	        	$_SESSION['begin']=date("Y年m月d日").'(入住)';
	        }
	        if(!$_SESSION['end']) {
	        	$_SESSION['end']=date("Y年m月d日",strtotime("+1 days")).'(退房)';;
	        }
	        $begin=$_SESSION['begin'];
	        $beginYear=((int)substr($begin,0,4));//年
    		$beginMonth=((int)substr($begin,7,2));//月
    		$beginDay=((int)substr($begin,12,2));//天
    		$beginTime = mktime(0,0,0,$beginMonth,$beginDay,$beginYear);
    		$end=$_SESSION['end'];
	        $endYear=((int)substr($end,0,4));//年
    		$endMonth=((int)substr($end,7,2));//月
    		$endDay=((int)substr($end,12,2));//天
    		$endTime = mktime(0,0,0,$endMonth,$endDay,$endYear);
    		$total = ($endTime-$beginTime)/3600/24;
    		if($total<=0) {
    			$total=1;
    		}
    		$r_fee=$total*$result['price'];
    		$this->assign('total',$total);
			$this->assign('result',$result);
			$this->assign('r_fee',$r_fee);
			$this->display();
		}
	}

	public function ajaxSign() {
		if(IS_AJAX) {
			$begin=$_SESSION['begin'];
	        $beginYear=((int)substr($begin,0,4));//年
    		$beginMonth=((int)substr($begin,7,2));//月
    		$beginDay=((int)substr($begin,12,2));//天
    		$beginTime = mktime(0,0,0,$beginMonth,$beginDay,$beginYear);
    		$end=$_SESSION['end'];
	        $endYear=((int)substr($end,0,4));//年
    		$endMonth=((int)substr($end,7,2));//月
    		$endDay=((int)substr($end,12,2));//天
    		$endTime = mktime(0,0,0,$endMonth,$endDay,$endYear);
	    	$total = ($endTime-$beginTime)/3600/24;
	    	if($total<=0) {
    			$total=1;
    		}
    		$_POST['begin_time']=$beginYear.'-'.$beginMonth.'-'.$beginDay;
	    	$_POST['end_time']=$endYear.'-'.$endMonth.'-'.$endDay;
    		$beginTime =$_POST['begin_time'];
			$endTime = $_POST['end_time'];
			$hid=$_POST['hid'];
			$houseinfo = M('houseinfo')->WHERE("id={$hid}")->find();
			$r_fee=$total*$houseinfo['price'];
			$maxUsed = M('h_time')->FIELD('MAX(used) as used')->WHERE("date>='{$beginTime}' AND date<='{$endTime}' AND hid={$hid}")->find();
			if(is_null($maxUsed['used']) || $maxUsed['used']<$houseinfo['total']) {
				$_POST['r_fee']=$r_fee;
				session('b_time_queue', $_POST);
				$openid=$_POST['openid'];
				$data = $this->payConfig($openid,$hid,$r_fee);
				$this->ajaxReturn($data);
			}
		}
		$data['status'] = 'no';
		$this->ajaxReturn($data);
	}
	
	public function payConfig($openid,$hid,$r_fee) {
		$body='美丽新乡村-预订民宿支付';
		$unifiedorder['total_fee']=100*$r_fee;
		$unifiedorder['appid']=self::APP_ID;
		$unifiedorder['mch_id']=self::MCH_ID;
		$unifiedorder['nonce_str']='suijizifuchuanhao';
		$unifiedorder['body']=$body;
		$unifiedorder['out_trade_no']=time();
		$unifiedorder['spbill_create_ip']=getIP();
		$unifiedorder['notify_url']='http://mei.vshijie.cn/minsu/index.php/Home/Weixin/payResult';
		$unifiedorder['trade_type']='JSAPI';
		$unifiedorder['openid']=$openid;
		ksort($unifiedorder);
		$sign="";
		foreach ($unifiedorder as $key => $val) {
		    $sign=($sign.$key.'='.$val.'&');
		}
		$sign=$sign.'key='.self::PAY_KEY;
		$sign=md5($sign);
		$unifiedorder['sign']=strtoupper($sign);
		$prepayIdXml = send_post('https://api.mch.weixin.qq.com/pay/unifiedorder', arrayToXml($unifiedorder));
//		var_dump($r_fee.'---'.htmlentities($prepayIdXml));
		if(strpos($prepayIdXml,'prepay_id')) {
			$prepayIdObj = simplexml_load_string($prepayIdXml, 'SimpleXMLElement', LIBXML_NOCDATA);
			$prepayId=$prepayIdObj->xpath('prepay_id');
			if(!is_null($prepayId)) {
				$data['appId']=self::APP_ID;
				$data['nonceStr']='signRandom';
				$data['timeStamp']=time(true);
				$data['package']='prepay_id='.$prepayId[0];
				$data['signType']='MD5';
				ksort($data);
				$paySign="";
				foreach ($data as $key => $val) {
				    $paySign=($paySign.$key.'='.$val.'&');
				}
				$paySign=$paySign.'key='.self::PAY_KEY;
				$data['paySign']=strtoupper(md5($paySign));
				$data['status']=1;
				return $data;
				
			}
		}
		$data['status']=0;
		return $data;
	}
	
	public function ajaxAppoint($u,$oid) {
		$bTimeQueue = session('b_time_queue');
		if($bTimeQueue) {
			$guest = M('guest')->WHERE("openid='{$oid}'")->find();
			$r_id=$guest['id'];
			$r_name=$bTimeQueue['r_name'];
			$r_phone=$bTimeQueue['r_phone'];
			$r_sex=$bTimeQueue['r_sex'];
			$createtime=date('Y-m-d H:i:s');
			$bTimeQueue['createtime']=$createtime;
			if(is_null($r_id)) {
				$r_id=M('guest')->add(array('openid'=>$oid,'name'=>$r_name,'phone'=>$r_phone,'sex'=>$r_sex,'createtime'=>$createtime));
			}
			$bTimeQueue['status']=1;
			$bTimeQueue['amount']=1;
			$bTimeQueue['r_id']=$r_id;
			M('h_time_queue')->add($bTimeQueue);
			$begin_time = strtotime(date($bTimeQueue['begin_time']));
			$end_time = strtotime(date($bTimeQueue['end_time']));
			$hid=$bTimeQueue['hid'];
			while($begin_time<=$end_time) {
				$begin=date('Y-m-d',$begin_time);
				$sql="insert into bk_h_time (hid,date)values({$hid},'{$begin}') ON DUPLICATE KEY UPDATE used=used+1";
				M()->execute($sql);
				$begin_time=$begin_time+86400;
			}
			unset($_SESSION['b_time_queue']); 
			sendText($r_phone,'【自在乡居】您好，感谢预定！关注微信公众号(美丽新乡村)随时随地查看订单状态。');
		}
		header('Location: '.$u);
	}
}
 ?>