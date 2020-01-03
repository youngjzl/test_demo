<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}
require EWEI_SHOPV2_PLUGIN . 'merch/core/inc/page_merch.php';
class Plu_EweiShopV2Page extends PluginWebPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
		$pagetype = 'plu';
		$condition = '';
		if (!empty($_GPC['keyword'])) {
			$keyword = '%' . trim($_GPC['keyword']) . '%';
			$condition .= ' and name like \'' . $keyword . '\' ';
		}

		$limittype = $pagetype;

		if (!empty($_GPC['type'])) {
			$condition .= ' and type=' . intval($_GPC['type']) . ' ';
			$limittype = NULL;
		}
		$diypage_plugin = p('diypage');

		$result = $diypage_plugin->getPageList('plu', $condition, intval($_GPC['page']));
		extract($result);

		if (!empty($list)) {
			foreach ($list as $key => &$value) {
				$url = mobileUrl('diypage', array('id' => $value['id'], 'merchid' => $_W['merchid']), true);
				$value['qrcode'] = m('qrcode')->createQrcode($url);
			}
		}

		$diypagedata = m('common')->getPluginset('diypage');
		$diypagedata = $diypagedata['page'];
		include $this->template('diypage/page/list');
	}

	public function edit()
	{
		$this->post('edit');
	}

	public function add()
	{
		$this->post('add');
	}

	protected function post($do)
	{
		global $_W;
		global $_GPC;
		$diypage_plugin = p('diypage');
		$result = $diypage_plugin->verify($do, 'plu');
		extract($result);
		if ($template && $do == 'add') {
			$template['data'] = base64_decode($template['data']);
			$template['data'] = json_decode($template['data'], true);
			$page = $template;
		}

		$allpagetype =  $diypage_plugin->getPageType();
		$typename = $allpagetype[$type]['name'];
		$diymenu = pdo_fetchall('select id, `name` from ' . tablename('ewei_shop_diypage_menu') . ' where merch=:merch and uniacid=:uniacid  order by id desc', array(':merch' => intval($_W['merchid']), ':uniacid' => $_W['uniacid']));
		$diyadvs = pdo_fetchall('select id, `name` from ' . tablename('ewei_shop_diypage_plu') . ' where merch=:merch and `type`=1 and status=1 and uniacid=:uniacid  order by id desc', array(':merch' => intval($_W['merchid']), ':uniacid' => $_W['uniacid']));
		$category = pdo_fetchall('SELECT id, name FROM ' . tablename('ewei_shop_diypage_template_category') . ' WHERE merch=:merch and uniacid=:uniacid order by id desc ', array(':merch' => intval($_W['merchid']), ':uniacid' => $_W['uniacid']));

		if ($_W['ispost']) {
			$data = $_GPC['data'];
			$diypage_plugin->savePage($id, $data);
		}

		$hasplugins = json_encode(array('creditshop' => p('creditshop') ? 1 : 0, 'merch' => p('merch') ? 1 : 0, 'seckill' => p('seckill') ? 1 : 0, 'exchange' => p('exchange') ? 1 : 0));
		include $this->template('diypage/page/post');
	}

	public function delete()
	{
		global $_W;
		global $_GPC;
		$diypage_plugin = p('diypage');
		$id = intval($_GPC['id']);

		if (empty($id)) {
			$id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
		}

		$diypage_plugin->delPage($id);
	}

	public function savetemp()
	{
		global $_W;
		global $_GPC;
		$diypage_plugin = p('diypage');
		$temp = array('type' => intval($_GPC['type']), 'cate' => intval($_GPC['cate']), 'name' => trim($_GPC['name']), 'preview' => trim($_GPC['preview']), 'data' => $_GPC['data']);
		$diypage_plugin->saveTemp($temp);
	}
}

?>
