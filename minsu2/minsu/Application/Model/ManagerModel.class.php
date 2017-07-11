<?php 
namespace Model;
use Think\Model;

/**
 * 管理员表模型
 */
class ManagerModel extends Model{
	// 设置数据表
    protected $tableName = 'Manager';
	// 是否批处理验证  如果是true的话输出的错误信息是一个array数组
//	protected $patchValidate = true;
	// 自动验证定义
	// 格式说明：array(验证字段，验证规则，错误提示，验证条件，附加规则，验证时间)
	protected $_validate = array(
	    // 1)用户名验证，不能为空（唯一）
	    array('mg_name','require','用户名不能为空',1),
	    // 2)密码不能为空
	    array('mg_pwd','require','密码不能为空'),
	    array('mg_pwd','5,30','密码长度不正确',0,'length'), // 验证密码是否在指定长度范围
	    array('mg_pwd2','require','密码不能为空'),
	    array('mg_pwd2','5,30','密码长度不正确',0,'length'), // 验证密码是否在指定长度范围
	    array('mg_pwd2','mg_pwd','您两次输入的密码不一致',0,'confirm'),
	    // 3)角色必须选中一个
	    array('hj_role_role_id','check_id','至少选择一项',1,'callback'),
	);
	// 验证有没有选择角色
	// 参数id代表被验证项目的value值
	public function check_id($id) {
		if($id<0){
			// 会自动输出验证的错误信息
			return false;
		}
		return true;
	}
    
    
 }
 



 ?>