<?php

/**
 * 功能：合利宝-微信公众号支付接入
 */

require_once("SignService.php");
/**************************请求参数 -- 此处写死，需根据商户业务参数再做动态变更**************************/

$SignService = SignService::instance(); //实例化签名类
global $_W;
global $_GPC;
$orderid = intval($_GPC["id"]);
$uniacid = $_W["uniacid"];
$openid = $_W["openid"];
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <title>合利宝微信公众号支付</title>
</head>
<body>
<script>
	function goPay(payData) {
        if (isWx()) {
            if (typeof WeixinJSBridge == "undefined") {
                if (document.addEventListener) {
                    document.addEventListener("WeixinJSBridgeReady",onBridgeReady(payData), false);
                } else if (document.attachEvent) {
                    document.attachEvent("WeixinJSBridgeReady", onBridgeReady(payData));
                    document.attachEvent("onWeixinJSBridgeReady", onBridgeReady(payData));
                }
            } else {
                onBridgeReady(payData);
            }
        }
    }

    function isWx() {
        var ua = window.navigator.userAgent.toLowerCase();
        if (ua.match(/MicroMessenger/i) == "micromessenger") {
            return true;
        } else {
            return false;
        }
    }
	
    function onBridgeReady(data){
        WeixinJSBridge.invoke(
            "getBrandWCPayRequest", {
                "appId"     : data.appId,
                "timeStamp" : data.timeStamp,
                "nonceStr"  : data.nonceStr,
                "package"   : data.package,
                "signType"  : data.signType,
                "paySign"   : data.paySign
            },
            function(res){
                if(res.err_msg == "get_brand_wcpay_request:ok" ) {
                    alert("恭喜，您已支付成功！");
					window.location.href="<?php echo mobileUrl('order/pay/hsuccess') ?>&id=<?php echo $orderid ?>";
                } else if (res.err_msg == "get_brand_wcpay_request:cancel") {
                    alert("取消支付！");
                } else {
                    alert("启动微信支付失败,详细错误为: " + res.err_msg);
                }
            }
        );
    }
</script>
<?php
$order = pdo_fetch("select * from " . tablename("ewei_shop_order") . " where id=:id and uniacid=:uniacid and openid=:openid limit 1", array( ":id" => $orderid, ":uniacid" => $uniacid, ":openid" => $openid ));
		if(empty($order) ) {
			if( $_W["ispost"] ) {
				show_json(0, "订单未找到");
			}else {
				$this->message("订单未找到", mobileUrl("order"));
			}
		}
		$address = pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_member_address') . ' WHERE id = :id and uniacid=:uniacid', array(':id' => $order['addressid'], ':uniacid' => $_W['uniacid']));
		$member = m("member")->getMember($openid, true);

$sec = m("common")->getSec();
$sec = iunserializer($sec["sec"]);

$post_data = array(
    "productCode" => "WXPAYOA",
    "merchantNo" => $sec["helipay"]["merchid"],
    "orderNo" => $order["ordersn"],
    'orderAmount' => $order["price"],
    "goodsName" => 'testgoods',
    "orderIp" => $SignService->get_client_ip(),
    'memberName' => $address['realname'],
    'memberID' => $member["idnumber"],
    'memberMobile' => $address['mobile'],
    "bizType" => 'AppPayPublic',
    "appId" => $sec["helipay"]["appid"],
    "appType" => 'WXPAY',
    "payType" => 'PUBLIC',
    "openId" => $openid,
    "subscribeAppId" => $sec["helipay"]["appid"]
);
$aesKey = "qtdTmqVTk/FGvVme4sjp8g==";
$sha256Key = "4uQfSq5ScO7NchF0";

//sha256加密
$sign = $SignService->sign_sha256hex($post_data, $sha256Key);

//aes加密json字串
$aes_json_app = $SignService->mcryptEncrypt(json_encode($post_data), base64_decode($aesKey));

//两次加密后再次组成新的数组
$request_params = array(
    "content" => $aes_json_app,
    "merchantNo" => $post_data["merchantNo"],
    "orderNo" => $post_data["orderNo"],
    "productCode" => $post_data["productCode"],
    "sign" => $sign
);

//生成请求json字串
$json_request_arr = json_encode($request_params);

//请求的url地址
$url = 'https://cbptrx.helipay.com/cbtrx/rest/pay/weChat/woa';

//设置header头部信息
$header = array(
    "Content-type:application/json;charset='utf-8'",
);

$ret = $SignService->http_post_function($url, $json_request_arr, $header);

pdo_update("haiguan_data",  array( "initalResponse" => $this->get, "payTransactionId"=>$this->get["transaction_id"], "tradingTime"=>date("yyyyMMddHHmmss", time()), "update_time" => time()), array("orderNo" => $log["tid"] ));

//请求结果转换回数组
$arr_postReturn = json_decode($ret, true);

//aes解密返回结果中的"content"内容得到json字串
$postReturnre_result = $SignService->mcryptDecrypt($arr_postReturn["content"], base64_decode($aesKey));
$postReturnre_result = json_decode($postReturnre_result, true);

//获取微信jsapi所需要参数（json）
//{"appId":"wx481ee5b68bc903ca","timeStamp":"1551951854","nonceStr":"b66fb4c755144f09927b327216d56a44","package":"prepay_id=wx0717441420364019fd19786c1131255489","signType":"RSA","paySign":"a3p9N7THEI/Lp/fQALV3JgXnDzm9ZSauw9KiJEq2SKWE5C+1fDitEZQY5woMINpLi3n7769PJwA6yj5fumyVPQs7daqfoi8NBqkB/WJW/eAzl2wiIoGq+k0cFBkGl7G5uBb0Xtw6o7jX5g0D00ecXZTe8RUZto47BPDzOLQh3I4PqDag5QiK3lRmTf5IaTzKULu8TZMmysvxbo7FErN7rwbAEtAKq+s4Lhfwv7F+1dWdbOCsjSPkovGtwp0xCV6upABqWS6l8fnLkjnIV9rlyMT43eoCo8AjOvR5eJ21gx77p1gYvNs65qfge9/SmKTkwMx564SW5fr3uK4lgVBx5g=="}
$pay_data = $postReturnre_result['payInfo'];
/***************************** -微信支付js脚本- **************************/

echo '<div style="text-align:center;margin-top:50px;width:100%;"><button style="width:90%;height:75px;background-color:#289402;color:#ffffff;font-size:22px;" onclick=\'goPay('.$pay_data.')\'>点击支付</button></div>';
?>
</body>
</html>