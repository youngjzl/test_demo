<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Index_EweiShopV2Page extends MobilePage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		//记录用户点击了分类几次20191126
		$cate_cnxh=intval($_GPC['cate']);
		if (!empty($cate_cnxh)){
			m('cnxhdata')->setclickcat($cate_cnxh);
		}//--记录用户点击了几次end
		$allcategory = m('shop')->getCategory();
		$catlevel = intval($_W['shopset']['category']['level']);
		$opencategory = true;
		$plugin_commission = p('commission');
		if ($plugin_commission && 0 < intval($_W['shopset']['commission']['level'])) {
			$mid = intval($_GPC['mid']);
			if (!empty($mid) && empty($_W['shopset']['commission']['closemyshop']) && !empty($_W['shopset']['commission']['select_goods'])) {
				$shop = p('commission')->getShop($mid);
				if (empty($shop['selectcategory']) && !empty($shop['selectgoods'])) {
					$opencategory = false;
				}
			}
		}
		//跟随首页的底部菜单改变购物车的菜单
		$diypagedata = p("merch")->getSet("diypage",intval($_GPC["merchid"]));
		$where = " where uniacid=:uniacid and merch=:merchid and id=:id";
		$params = array(":uniacid" => $_W["uniacid"],":merchid"=>intval($_GPC["merchid"]),':id'=>$diypagedata['page']['home']);
		$page = pdo_fetch("select * from " . tablename("ewei_shop_diypage") . $where . " limit 1 ", $params);

		$this->page = $page;//---end

		include $this->template();
	}

	public function gift()
	{
		global $_W;
		global $_GPC;
		$uniacid = $_W['uniacid'];
		$giftid = intval($_GPC['id']);
		$gift = pdo_fetch('select * from ' . tablename('ewei_shop_gift') . ' where uniacid = ' . $uniacid . ' and id = ' . $giftid . ' and starttime <= ' . time() . ' and endtime >= ' . time() . ' and status = 1 ');
		$giftgoodsid = explode(',', $gift['giftgoodsid']);
		$giftgoods = array();

		if (!empty($giftgoodsid)) {
			foreach ($giftgoodsid as $key => $value) {
				$giftgoods[$key] = pdo_fetch('select id,status,title,thumb,marketprice from ' . tablename('ewei_shop_goods') . ' where uniacid = ' . $uniacid . ' and deleted = 0 and total > 0 and id = ' . $value . ' and status = 2 ');
			}

			$giftgoods = array_filter($giftgoods);
		}

		include $this->template();
	}

	public function get_list()
	{
		global $_GPC;
		global $_W;
		//记录用户搜索商品关键词20191126
		$word_cnxh=trim($_GPC['keywords']);
        if (!empty($word_cnxh)){
			m('cnxhdata')->sethistorykeywords($word_cnxh);
		}//--记录用户关键词end
		$args = array('pagesize' => 10, 'page' => intval($_GPC['page']), 'isnew' => trim($_GPC['isnew']), 'ishot' => trim($_GPC['ishot']), 'isrecommand' => trim($_GPC['isrecommand']), 'isdiscount' => trim($_GPC['isdiscount']), 'istime' => trim($_GPC['istime']), 'issendfree' => trim($_GPC['issendfree']), 'keywords' => trim($_GPC['keywords']), 'cate' => trim($_GPC['cate']), 'order' => trim($_GPC['order']), 'by' => trim($_GPC['by']));
		$plugin_commission = p('commission');
		if ($plugin_commission && 0 < intval($_W['shopset']['commission']['level']) && empty($_W['shopset']['commission']['closemyshop']) && !empty($_W['shopset']['commission']['select_goods'])) {
			$frommyshop = intval($_GPC['frommyshop']);
			$mid = intval($_GPC['mid']);
			if (!empty($mid) && !empty($frommyshop)) {
				$shop = p('commission')->getShop($mid);
				if (!empty($shop['selectgoods'])) {
					$args['ids'] = $shop['goodsids'];
				}
			}
		}
		$this->_condition($args);
	}
	//搜索智能提示2019-8-21
	public function smarttips(){
		global $_GPC;
		$likevalue=$_GPC['valuesearchs'];
		if (!empty($likevalue)){
			$lists = pdo_fetchall("SELECT title,keywords FROM ".tablename('ewei_shop_goods')." WHERE status=1 and deleted=0 and merchid=0 and CONCAT(`title`,`shorttitle`,`description`) LIKE '%{$likevalue}%' ORDER BY salesreal LIMIT 10");
			//组装数组
			foreach ($lists as $listss){
				$res_list[]=preg_split('/(；|\|)/',$listss['keywords']);
			}

			foreach ($res_list as $res_lists){
				foreach ($res_lists as $conunt){
					$count[]=$conunt;
				}
			}
			//排序商品关键词用的最多的一种
			$listconunt=array_count_values($count);
			foreach ($listconunt as $k=>$listconunts){
				$res[]=array('title'=>$k,'conunt'=>$listconunts);
			}
			array_multisort(array_column($res, 'conunt'),SORT_DESC,$res);
			foreach ($res as $k=>$ress){
				unset($res[$k]['conunt']);
			}
			show_json(1,array('namesearch'=>array_slice($res,0,9)));
		}
	}
	//关闭订单提醒
	public function isopdd(){
		global $_W;
		global $_GPC;
		$isopdd = intval($_GPC['isopp']);
		$openid = $_W["openid"];
		pdo_update("ewei_shop_order", array( "is_opdd" => 2 ), array( "id" =>$isopdd , "openid" => $openid ));
		show_json(1,'1');
	}
	//关闭全部订单提醒（定时任务）
	public function isopddall(){
		pdo_query("UPDATE ".tablename('ewei_shop_order')." SET is_opdd = :is_opdd", array(':is_opdd' => '1'));
		exit();
	}
	//商品自动过期时间（定时任务）
	public function changeisnewall(){
		$isnewtimes=[];//id
		$lists = pdo_fetchall("SELECT id,isnew_time FROM ".tablename('ewei_shop_goods')." WHERE status=1 and deleted=0 and merchid=0 and isnew_time is not null");
		foreach ($lists as $isnewtime){
			if (time()>$isnewtime['isnew_time']){
				$isnewtimes[]=$isnewtime['id'];
			}
		}
		$goodsid_change=implode(',',$isnewtimes);
		pdo_query("UPDATE ".tablename('ewei_shop_goods')." SET isnew = :isnew where id in($goodsid_change) ", array(':isnew' => 0));
		exit();
	}
	//打开商城记录次数（定时任务）
	public function chageopenmall(){
		pdo_query("UPDATE ".tablename('ewei_shop_cnxh_data')." SET open_mall_num_status = :open_mall_num_status ", array(':open_mall_num_status' => 1));
		exit();
	}

	public function query()
	{
		global $_GPC;
		global $_W;
		$args = array('pagesize' => 10, 'page' => intval($_GPC['page']), 'isnew' => trim($_GPC['isnew']), 'ishot' => trim($_GPC['ishot']), 'isrecommand' => trim($_GPC['isrecommand']), 'isdiscount' => trim($_GPC['isdiscount']), 'istime' => trim($_GPC['istime']), 'keywords' => trim($_GPC['keywords']), 'cate' => trim($_GPC['cate']), 'order' => trim($_GPC['order']), 'by' => trim($_GPC['by']));
		$this->_condition($args);
	}

	private function _condition($args)
	{
		global $_GPC;
		$merch_plugin = p('merch');
		$merch_data = m('common')->getPluginset('merch');
		if ($merch_plugin && $merch_data['is_openmerch']) {
			$args['merchid'] = intval($_GPC['merchid']);
		}

		if (isset($_GPC['nocommission'])) {
			$args['nocommission'] = intval($_GPC['nocommission']);
		}

		$goods = m('goods')->getList($args);

		show_json(1, array('list' => $goods['list'], 'total' => $goods['total'], 'pagesize' => $args['pagesize']));
	}
}

?>
