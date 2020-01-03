<?php 
if( !defined("IN_IA") ) 
{
    exit( "Access Denied" );
}


class Index_EweiShopV2Page extends PluginWebPage
{
	public function main()
	{
        global $_W;
        global $_GPC;
        $pindex = max(1, intval($_GPC["page"]));
        $psize = 10;
		$condition = ' and uniacid=:uniacid';
		$params = array( ":uniacid" => $_W["uniacid"] );
		if( !empty($_GPC["keyword"]) ) 
		{
			$_GPC["keyword"] = trim($_GPC["keyword"]);
			$condition .= " and title like :keyword";
			$params[":keyword"] = "%" . $_GPC["keyword"] . "%";
		}
		$list = pdo_fetchall("SELECT * FROM " . tablename("ewei_shop_api_user") . " WHERE 1 " . $condition . "  ORDER BY id asc limit " . ($pindex - 1) * $psize . "," . $psize, $params);
		$total = pdo_fetchcolumn("SELECT count(*) FROM " . tablename("ewei_shop_api_user") . " WHERE 1 " . $condition, $params);
        $pager = pagination2($total, $pindex, $psize);
        include($this->template());
	}
	public function add() 
	{
		$this->post();
	}
	public function edit() 
	{
		$this->post();
	}
	protected function post() {
		global $_W;
		global $_GPC;
		$uniacid = intval($_W['uniacid']);
		$id = intval($_GPC["id"]);
		if( !empty($id) ) 
		{
			$item = pdo_fetch("SELECT * FROM " . tablename("ewei_shop_api_user") . " WHERE id=:id and uniacid=:uniacid ", array( ":id" => $_GPC["id"], ":uniacid" => $_W["uniacid"] ));
		}
		if( $_W["ispost"] ) 
		{
			$data = array(
				"uniacid" => $_W["uniacid"],
				"title" => trim($_GPC["title"]),
				"apikey" => $_GPC['apikey'],
				"apiurl" => $_GPC['apiurl'],
				"status" => intval($_GPC['status'])
			);
			
			$option = $_GPC['toolsgoods'];

			if (!empty($id)) {				
				pdo_update('ewei_shop_api_user', $data, array('id' => $id, 'uniacid' => $uniacid));
				
				plog("apiuser.edit", "编辑api授权 ID: " . $id . " 标题: " . $data["title"] . " ");
			}
			else 
			{

				$tools_insert = pdo_insert('ewei_shop_api_user', $data);

				if (!$tools_insert) {
					show_json(0, 'api授权添加失败！');
				}

				$id = pdo_insertid();

				plog("apiuser.add", "添加api授权 ID: " . $id . " 标题: " . $data["title"] . " ");
			}
			show_json(1, array( "url" => webUrl("apiuser") ));
		}
		include($this->template());
	}
	public function delete() 
	{
		global $_W;
		global $_GPC;
		$id = intval($_GPC["id"]);
		$item = pdo_fetch("SELECT id,title FROM " . tablename("ewei_shop_api_user") . " WHERE id =:id AND uniacid=:uniacid", array( ":id" => $id, ":uniacid" => $_W["uniacid"] ));
		if( !empty($item) ) 
		{
			pdo_delete("ewei_shop_api_user", array( "id" => $id, "uniacid" => $_W["uniacid"] ));
			plog("messages.delete", "删除api授权 ID: " . $item["id"] . " 标题: " . $item["title"] . " ");
		}
		show_json(1, array( "url" => referer() ));
	}
	public function query()
	{
		include $this->template();
	}
}
?>