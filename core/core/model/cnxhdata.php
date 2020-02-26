<?php
class Cnxhdata_EweiShopV2Model{


    public function setopenmallnum(){
        global $_W;
        $openid = $_W['openid'];
        $info = m('member')->getInfo($openid);
        $uid=!empty($info) ? $info['id']:'';
        $cnxh_user=pdo_fetch("select * from " . tablename("ewei_shop_cnxh_data") . " where user_id=:user_id and open_mall_num is not null", array( ":user_id" => $uid));
        //第二日凌晨的时间戳
        $today = !empty($cnxh_user)  ? $cnxh_user['addtime'] : strtotime('+1 day');
        if (!$cnxh_user){
            pdo_insert('ewei_shop_cnxh_data',array('user_id'=>$uid,'addtime'=>time(),'open_mall_num'=>1,'open_mall_num_status'=>1));
        }
        if (!empty($cnxh_user) && time()>$today && $cnxh_user['open_mall_num_status']==1){
            $num=++$cnxh_user['open_mall_num'];
            pdo_update('ewei_shop_cnxh_data',array('addtime'=>time(),'open_mall_num'=>$num,'open_mall_num_status'=>2),array('user_id'=>$uid,'open_mall_num_status'=>1));
        }
    }
    public function setclickcat($cats_id){
        global $_W;
        $openid = $_W['openid'];
        $info = m('member')->getInfo($openid);
        $uid=!empty($info) ? $info['id']:'';
        $cnxh_user=pdo_fetch("select * from " . tablename("ewei_shop_cnxh_data") . " where user_id=:user_id and cats_id=:cats_id ", array( ":user_id" => $uid,":cats_id"=>$cats_id));
        if (!$cnxh_user){
            pdo_insert('ewei_shop_cnxh_data',array('user_id'=>$uid,'addtime'=>time(),'cats_id'=>$cats_id,'click_catsnum'=>1));
        }
        if (!empty($cnxh_user)){
            $num=++$cnxh_user['cats_id'];
            pdo_update('ewei_shop_cnxh_data',array('click_catsnum'=>$num,'addtime'=>time(),),array('user_id'=>$uid,'cats_id'=>$cats_id));
        }
    }
    public function setclickgoodsnum($goods_id){
        global $_W;
        $openid = $_W['openid'];
        $info = m('member')->getInfo($openid);
        $uid=!empty($info) ? $info['id']:'';
        $cnxh_user=pdo_fetch("select * from " . tablename("ewei_shop_cnxh_data") . " where user_id=:user_id and goods_id=:goods_id ", array( ":user_id" => $uid,":goods_id"=>$goods_id));
        if ($cnxh_user!=false){
            pdo_update('ewei_shop_cnxh_data',array('click_goodsnum'=>$cnxh_user['click_goodsnum']+1,'addtime'=>time()),array('user_id'=>$uid,'goods_id'=>$goods_id));
        }else{
            pdo_insert('ewei_shop_cnxh_data',array('user_id'=>$uid,'addtime'=>time(),'goods_id'=>$goods_id,'click_goodsnum'=>1));
        }
    }
    public function sethistorykeywords($history_keywords){
        global $_W;
        $openid = $_W['openid'];
        $info = m('member')->getInfo($openid);
        $uid=!empty($info) ? $info['id']:'';
        $cnxh_user=pdo_fetch("select * from " . tablename("ewei_shop_cnxh_data") . " where user_id=:user_id and history_keywords=:history_keywords ", array( ":user_id" => $uid,":history_keywords"=>$history_keywords));
        if (!$cnxh_user){
            pdo_insert('ewei_shop_cnxh_data',array('user_id'=>$uid,'addtime'=>time(),'history_keywords'=>$history_keywords));
        }
        if (!empty($cnxh_user)){
            pdo_query("update ".tablename("ewei_shop_cnxh_data")." set history_keywords=CONCAT(history_keywords,",$history_keywords.") , addtime=".time()." where user_id=$uid");
        }
    }
}