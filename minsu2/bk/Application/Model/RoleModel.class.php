<?php 
namespace Model;
use Think\Model;

/**
 * 角色表模型
 */
class RoleModel extends Model{
	// 设置数据表
    protected $tableName = 'Role';
	// 自定义的验证规则
	protected $_validate = array(
		array('role_name','require','角色名称不能为空',1),
		array('role_name','','角色名称已经存在',0,'unique',1),
		array('describe','require','备注不能为空',1),
	);
	
    
    
 }
 



 ?>