<?php
class SnsMobilePage extends PluginMobilePage
{
	public $islogin = 0;

	public function __construct()
	{
		parent::__construct();
		global $_W;
		global $_GPC;
		$this->islogin = empty($_W['openid']) ? 0 : 1;
		$this->model->checkMember();
		$merch_plugin = p("merch");
		$merch_data = m("common")->getPluginset("merch");
		if( $merch_plugin && $merch_data["is_openmerch"] )
		{
			//跟随首页的底部菜单改变种草的菜单
			$diypagedata = p("merch")->getSet("diypage",intval($_GPC["merchid"]));
			$where = " where uniacid=:uniacid and merch=:merchid and id=:id";
			$params = array(":uniacid" => $_W["uniacid"],":merchid"=>intval($_GPC["merchid"]),':id'=>$diypagedata['page']['home']);
			$page = pdo_fetch("select * from " . tablename("ewei_shop_diypage") . $where . " limit 1 ", $params);
			$this->page = $page;//---end
		}
	}
}

?>
