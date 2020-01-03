<?php

if (!defined('IN_IA')) {

	exit('Access Denied');

}

return array(
	'version' => '1.0',
	'id'      => 'apiuser',
	'name'    => 'api授权账户',
	'v3'      => true,
	'menu'    => array(
		'plugincom' => 1,
		'items'     => array(
			array('title' => '列表', 'route' => ''),
			array('title' => '新增', 'route' => 'add')
		)
	)
);

?>

