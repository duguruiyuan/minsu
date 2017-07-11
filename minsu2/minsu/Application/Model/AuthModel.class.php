<?php 
namespace Model;
use Think\Model;

/**
 * 权限模型
 */
class AuthModel extends Model{
	// 设置数据表
    protected $tableName = 'Auth';
	// 自定义的验证规则
	
	// 实现权限添加逻辑
    function saveData($data){
        //  当前方法的战略：根据已有信息生成一个新记录(字段内容不全面)
        //                  通过算法计算auth_path和auth_level
        //                  再通过一个update语句把path和level给更新到新记录里边
        //                  此时字段内容完整
        //1) 根据已有$data(name/pid/controller/action)数据生成一条新记录出来
        $newid = $this -> add($data);
        //2) 制作auth_path
        if($data['auth_pid']==0){
        //  ① 顶级权限 auth_path=====新记录主键id值
            $path = $newid;
			$add = '';
			$edit = '';
			$del = '';
			$c = '';
			$a = '';
			$icon = $data['auth_icon'];
			$sort = $data['auth_sort'];
        }else{
        //  ② 非顶级权限  根据pid获得父级权限信息，进而获得其“全路径”
        //     父级全路径-新记录主键id值
            $pinfo = $this -> find($data['auth_pid']);
            $path = $pinfo['auth_path']."-".$newid;
			$add = $data['auth_add'];
			$edit = $data['auth_edit'];
			$del = $data['auth_del'];
			$c = $data['auth_c'];
			$a = $data['auth_a'];
			$icon = '';
			$sort = $data['auth_sort'];
			// 添加其他操作方法
			if(!empty($data['other_name']) && !empty($data['other_a'])) {
				$on = explode('丨', $data['other_name']);
				$oa = explode('丨', $data['other_a']);
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
					$other_level = $path."-".$value;
					// 再次重组以便更新数据
					$otherData = array(
						'other_pid' => $newid,
						'other_level' => substr_count($other_level,'-'),
						'hj_auth_auth_id' => $newid,
					);
					D("Other")->where("other_id = {$value}")->save($otherData);
				}
			}
        }
        //3) 制作auth_level
        //   全路径里边"-"数量就是auth_level的值
        //   substr_count()计算一个字符串中出现的目标内容次数
        $level = substr_count($path,'-');
        $sql = "update ms_auth set auth_path='$path',auth_sort='$sort',auth_c='$c',auth_a='$a',auth_level='$level',auth_add='$add',auth_edit='$edit',auth_del='$del',auth_icon='$icon' where auth_id='$newid'";
        return $this -> execute($sql);
    }
	
	/**
	 * 获得所有的子节点
	 */
	public function getSon($auth_id){
		// 查询所有的数据
        $allData = $this->select();
        $auth_ids = $this->getSonAid($auth_id,$allData);
        $auth_ids[] = $auth_id;
        return $auth_ids;
    }
	
	/**
     * 获得子节点,不包括自己
     */
    private function getSonAid($auth_id,$allData){
        $temp = array();
	    foreach ($allData as $k => $v) {
	        if($v['auth_pid']==$auth_id){
	            $temp[] = $v['auth_id'];
	        }
        }
        return $temp;
    }
	
    
    
 }
 



 ?>