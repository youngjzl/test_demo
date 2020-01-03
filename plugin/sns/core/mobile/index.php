<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

require EWEI_SHOPV2_PLUGIN . 'sns/core/page_mobile.php';
class Index_EweiShopV2Page extends SnsMobilePage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$openid = $_W['openid'];
		$uniacid = $_W['uniacid'];
		$shop = m('common')->getSysset('shop');
		$advs = pdo_fetchall('select id,advname,link,thumb from ' . tablename('ewei_shop_sns_adv') . ' where uniacid=:uniacid and enabled=1 order by displayorder desc', array(':uniacid' => $uniacid));
		$credit = m('member')->getCredit($openid, 'credit1');
		$category = pdo_fetchall('select id,`name`,thumb,isrecommand from ' . tablename('ewei_shop_sns_category') . ' where uniacid=:uniacid and isrecommand = 1 and enabled=1 order by displayorder desc', array(':uniacid' => $uniacid));
		$recommands = pdo_fetchall('select sb.id,sb.title,sb.logo,sb.`desc`  from ' . tablename('ewei_shop_sns_board') . " as sb\r\n\t\t\t\t\t\tleft join " . tablename('ewei_shop_sns_category') . " as sc on sc.id = sb.cid\r\n\t\t\t\t\t\twhere sb.uniacid=:uniacid and sb.isrecommand=1 and sb.status=1 and sc.enabled = 1 order by sb.displayorder desc", array(':uniacid' => $uniacid));

		foreach ($recommands as &$row) {
			$row['postcount'] = $this->model->getPostCount($row['id']);
			$row['followcount'] = $this->model->getFollowCount($row['id']);
		}

		unset($row);
		$_W['shopshare'] = array('title' => $this->set['share_title'], 'imgUrl' => tomedia($this->set['share_icon']), 'link' => mobileUrl('sns', array(), true), 'desc' => $this->set['share_desc']);
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

		include $this->template();
	}
}

?>
