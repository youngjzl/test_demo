<?php
require './framework/bootstrap.inc.php';
$output = array();
$orderNo = $_GET['orderNo'] ? $_GET['orderNo'] : '';
$sessionID = $_GET['sessionID'] ? $_GET['sessionID'] : '';
$aserviceTime = $_GET['serviceTime'] ? $_GET['serviceTime'] : '';
if (empty($orderNo)) {
	$output = array('data'=>NULL, 'serviceTime'=>time(), 'message'=>'订单编号为空!', 'code'=>201);
	exit(json_encode($output));
}
if (empty($sessionID)) {
	$output = array('data'=>NULL, 'serviceTime'=>time(), 'message'=>'sessionID为空!', 'code'=>202);
	exit(json_encode($output));
}
if (empty($aserviceTime)) {
	$output = array('data'=>NULL, 'serviceTime'=>time(), 'message'=>'aserviceTime为空!', 'code'=>203);
	exit(json_encode($output));
}
if(!empty($orderNo)) {
	$item = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_order') . ' WHERE ordersn = :orderNo', array(':orderNo' => $orderNo));
	//检查订单
	if (empty($item)) {
		$output = array('data'=>NULL, 'serviceTime'=>time(), 'message'=>'抱歉，订单不存在!', 'code'=>401);
		exit(json_encode($output));
	}
	$order_goods = array();
	if (0 < $item['sendtype']) 
	{
		$order_goods = pdo_fetchall('SELECT orderid,goodsid,sendtype,expresssn,expresscom,express,sendtime FROM ' . tablename('ewei_shop_order_goods') . "\r\n" . '            WHERE orderid = ' . $item["id"] . ' and sendtime > 0 and sendtype > 0 group by sendtype order by sendtime desc ');
		foreach ($order_goods as $key => $value ) 
		{
			$order_goods[$key]['goods'] = pdo_fetchall('select g.id,g.title,g.thumb,og.sendtype,g.ispresell,og.realprice from ' . tablename('ewei_shop_order_goods') . ' og ' . ' left join ' . tablename('ewei_shop_goods') . ' g on g.id=og.goodsid ' . ' where og.orderid=:orderid and og.sendtype=' . $value['sendtype'] . ' ', array(':orderid' => $item["id"] ));
		}
		$item['sendtime'] = $order_goods[0]['sendtime'];
	}
	$goods = pdo_fetchall('SELECT op.specs,g.*, o.goodssn as option_goodssn, o.productsn as option_productsn,o.total,g.type,g.goodsbusinesstype,g.rate,g.rate1,g.rate2,g.rate3,g.rate4,g.rate5,o.optionname,o.optionid,o.price as orderprice,o.realprice,o.changeprice,o.oldprice,o.commission1,o.commission2,o.commission3,o.commissions,o.seckill,o.seckill_taskid,o.seckill_roomid' . $diyformfields . ' FROM ' . tablename('ewei_shop_order_goods') . ' o left join ' . tablename('ewei_shop_goods') . ' g on o.goodsid=g.id ' . ' left join ' . tablename('ewei_shop_goods_option') . ' op on o.optionid=op.id WHERE o.orderid=:orderid', array(':orderid' => $item["id"]));
	$is_merch = false;
	/*税费和商品小计订单商品循环*/
	$rate_price = 0;
	$goodsprice = 0;
	foreach ($goods as &$r )
	{
		/*$r['seckill_task'] = false;
		if ($r['seckill']) 
		{
			$r['seckill_task'] = plugin_run('seckill::getTaskInfo', $r['seckill_taskid']);
			$r['seckill_room'] = plugin_run('seckill::getRoomInfo', $r['seckill_taskid'], $r['seckill_roomid']);
		}
		if (!(empty($r['option_goodssn']))) 
		{
			$r['goodssn'] = $r['option_goodssn'];
		}
		if (!(empty($r['option_productsn']))) 
		{
			$r['productsn'] = $r['option_productsn'];
		}
		$r['marketprice'] = $r['orderprice'] / $r['total'];
		if (p('diyform')) 
		{
			$r['diyformfields'] = iunserializer($r['diyformfields']);
			$r['diyformdata'] = iunserializer($r['diyformdata']);
		}
		if (!(empty($r['merchid']))) 
		{
			$is_merch = true;
		}
		if (!(empty($r['diyformdata'])) && ($r['diyformdata'] != 'false') && !($showdiyform)) 
		{
			$showdiyform = true;
		}
		if (empty($r['optionname']) && !(empty($r['specs']))) 
		{
			$r['optionname'] = $this->option_title($id);
		}*/
		/*税费计算*/
		if($r["goodsbusinesstype"] = 3){//判断是否是海外商品
			$rateprice = round(floatval($r["realprice"]) / (100 + floatval($r["rate5"])) * 100, 2);
			$rate_price += (floatval($r["realprice"]) - $rateprice);
			$goodsprice += floatval($rateprice);
		}else{//非海外商品
			$goodsprice += floatval($r["realprice"]);
		}
	}
	/*税费和商品小计*/
	$item["goodsprice"] = $goodsprice;
	$item["rateprice"] = $rate_price;
	unset($r);
	$item['goods'] = $goods;
 
	//输出数据
	$output = array(
		'data' => $item, 
		'serviceTime'=>time(),
		'message' => 'OK', //消息提示，客户端常会用此作为给弹窗信息。
		'code' => 10000, //成功与失败的代码，一般都是正数或者负数
	);
	exit(json_encode($output));
}