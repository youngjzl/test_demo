<?php
if (!defined('IN_IA')) {
    exit('Access Denied');
}

class Index_EweiShopV2Page extends ComWebPage
{
    public function __construct($_com = 'ceyh')
    {
        parent::__construct($_com);
    }

    public function main()
    {
        global $_W;
        global $_GPC;
        $uniacid = intval($_W['uniacid']);
        $pindex = max(1, intval($_GPC['page']));
        $psize = 20;
        $condition = ' and uniacid=:uniacid';
        $params = array(':uniacid' => $uniacid);
        $type = trim($_GPC['type']);

        if ($type == 'ing') {
            $condition .= ' and  status=1 ';
        } else {
            if ($type == 'stop') {
                $condition .= ' and status=2 ';
            }
        }
        if (!empty($_GPC['keyword'])) {
            $_GPC['keyword'] = trim($_GPC['keyword']);
            $condition .= ' AND title LIKE :title';
            $params[':title'] = '%' . trim($_GPC['keyword']) . '%';
        }
        $packages = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_ceyh') . "\r\n                    WHERE 1 " . $condition . ' ORDER BY id DESC LIMIT ' . ($pindex - 1) * $psize . ',' . $psize, $params);
        //添加url链接
        if (!empty($_GPC['ceyhurl'])) {
            // 从文件中读取数据到PHP变量
            $ceyhurl_json = file_get_contents(dirname(__FILE__).'/ceyhurl.json');
            $ceyhurl['ceyhurl']=$_GPC['ceyhurl'];
            file_put_contents(dirname(__FILE__).'/ceyhurl.json',json_encode($ceyhurl));
        }
        $ceyhurl_json = file_get_contents(dirname(__FILE__).'/ceyhurl.json');
        $ceyhurl=json_decode($ceyhurl_json,true)['ceyhurl'];

        $total = pdo_fetchcolumn('SELECT COUNT(1) FROM ' . tablename('ewei_shop_ceyh') . ' WHERE 1 ' . $condition . ' ', $params);

        $pager = pagination2($total, $pindex, $psize);
        include $this->template();
    }

    public function add()
    {
        $this->post();
    }

    public function edit()
    {
        $this->post();
    }

    protected function post()
    {
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        $uniacid = intval($_W['uniacid']);
        $params = array(':uniacid' => $uniacid);
        //商品分类
        $categorys = m("shop")->getFullCategory(true, true);
        $category = array();
        foreach( $categorys as $cate )
        {
            $category[$cate["id"]] = $cate;
        }

        //商品组
        $condition = ' and uniacid=:uniacid and merchid=0';
        $shopgroups = pdo_fetchall('SELECT * FROM ' . tablename('ewei_shop_goods_group') . (' WHERE 1 ' . $condition . '  ORDER BY id DESC  ') ,$params);
        if ($_W['ispost']) {
            $data = array();
            $shopgoods=array();
            $data['uniacid']=$uniacid;
            $data['displayorder']=intval($_GPC['displayorder']);
            $data['title']=$_GPC['title'];
            $data['enoughmoney']=floatval($_GPC['enoughmoney']);
            $data['enoughdeduct']=floatval($_GPC['enoughdeduct']);
            $data['diylayer']=intval($_GPC['diylayer']);
            $data['status']=intval($_GPC['status']);
            $data['cates']=$_GPC['cates'];//分类商品
            $data['shopgroup']=$_GPC['shopgroup'];//商品组
            $data['goodsids']=$_GPC['goodsids'];//单个商品
            //商品分类
            if ($data['diylayer']=='1'){
                if (is_array($data['cates']) && !empty($data['cates'])){
                    foreach ($data['cates'] as $cate){
                        $sql='SELECT id FROM ' . tablename('ewei_shop_goods') . (' WHERE  FIND_IN_SET('.$cate.',cates)<>0   ORDER BY id DESC  ');
                        $shopgoods_items=pdo_fetchall($sql,$params);
                        foreach ($shopgoods_items as $shopgoods_item){
                            $shopgoods[]=$shopgoods_item['id'];
                        }
                    }

                }
            }
            //商品组
            if ($data['diylayer']=='2'){
                if (is_array($data['shopgroup']) && !empty($data['shopgroup'])){
                    foreach ($data['shopgroup'] as $shopgroup){
                        $sql='SELECT goodsids FROM ' . tablename('ewei_shop_goods_group') . (' WHERE  id='.$shopgroup.' ORDER BY id DESC  ');
                        $shopgoods_items=pdo_fetch($sql,$params);
                        $shopgoods_arr=explode(',',$shopgoods_items['goodsids']);
                        foreach ($shopgoods_arr as $shopgood){
                            $shopgoodss[]=$shopgood;
                        }
                    }
                    $shopgoods=array_unique($shopgoodss);
                }
            }
            unset($shopgoodss);
            //单个选择商品
            if ($data['diylayer']=='3'){
                if (is_array($data['goodsids']) && !empty($data['goodsids'])){
                    $shopgoods=$data['goodsids'];
                }
            }
            $data['shopid']=implode(",", $shopgoods);
            $data['cates']=implode(",", $data['cates']);
            $data['shopgroup']=implode(",", $data['shopgroup']);
            $data['goodsids']=implode(",", $data['goodsids']);

            if (empty($id)){
                pdo_insert('ewei_shop_ceyh',$data);
            }else{
                pdo_update('ewei_shop_ceyh', $data, array('id' => $id));
            }
            plog("sale.ceyh", "操作凑额优惠");
            show_json(1,'操作成功');
        }
        $cates=array();
        $group=array();
        $goods=array();
        if (!empty($id)){
            $item=pdo_fetch('SELECT * FROM ' . tablename('ewei_shop_ceyh') . (' WHERE id='.$id.' LIMIT 1 ') ,$params);
            switch ($item['diylayer']){
                //商品分类
                case $item['diylayer']==1:
                    $cate=explode(',',$item['cates']);
                    foreach ($category as $catess){
                        foreach ($cate as $cat){
                            if ($catess['id']==$cat){
                                $cates[]=$catess['id'];
                            }
                        }
                    }
                    break;
                //商品组
                case $item['diylayer']==2:
                    $shopgroup=explode(',',$item['shopgroup']);
                    foreach ($shopgroups as $shopgroups_is){
                        foreach ($shopgroup as $shopgroup_is){
                            if ($shopgroups_is['id']==$shopgroup_is){
                                $group[]=$shopgroups_is['id'];
                            }
                        }
                    }
                    break;
                //单个商品
                case $item['diylayer']==3:
                    $goodsids=explode(',',$item['goodsids']);
                    $sqlgoods = 'SELECT id,title,thumb FROM ' . tablename('ewei_shop_goods') . ' where uniacid=:uniacid ';
                    $goodsinfo = pdo_fetchall($sqlgoods, array(':uniacid' => $_W['uniacid']));
                    foreach ($goodsinfo as $goodsinfos){
                        foreach ($goodsids as $goodsid){
                            if ($goodsinfos['id']==$goodsid){
                                $goods[]=array('id'=>$goodsinfos['id'],'title'=>$goodsinfos['title'],'thumb'=>$goodsinfos['thumb']);
                            }
                        }
                    }
                    break;
            }
        }
        include $this->template();
    }

    public function delete()
    {
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);

        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }

        $items = pdo_fetchall('SELECT id,title FROM ' . tablename('ewei_shop_package') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

        foreach ($items as $item) {
            pdo_update('ewei_shop_package', array('deleted' => 1, 'status' => 0), array('id' => $item['id']));
            plog('sale.package.delete', '删除套餐 ID: ' . $item['id'] . ' 套餐名称: ' . $item['title'] . ' ');
        }

        show_json(1, array('url' => referer()));
    }

    public function status()
    {
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);
        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }

        $items = pdo_fetchall('SELECT id,title FROM ' . tablename('ewei_shop_ceyh') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

        foreach ($items as $item) {
            pdo_update('ewei_shop_ceyh', array('status' => intval($_GPC['status'])), array('id' => $item['id']));
            plog('sale.package.edit', '修改套餐状态<br/>ID: ' . $item['id'] . '<br/>套餐名称: ' . $item['title'] . '<br/>状态: ' . $_GPC['status'] == 1 ? '上架' : '下架');
        }

        show_json(1, array('url' => referer()));
    }

    public function delete1()
    {
        global $_W;
        global $_GPC;
        $id = intval($_GPC['id']);

        if (empty($id)) {
            $id = is_array($_GPC['ids']) ? implode(',', $_GPC['ids']) : 0;
        }

        $items = pdo_fetchall('SELECT id,title FROM ' . tablename('ewei_shop_ceyh') . (' WHERE id in( ' . $id . ' ) AND uniacid=') . $_W['uniacid']);

        foreach ($items as $item) {
            pdo_delete('ewei_shop_ceyh', array('id' => $item['id']));
            plog('sale.ceyh.edit', '彻底删除套餐<br/>ID: ' . $item['id'] . '<br/>套餐名称: ' . $item['title']);
        }

        show_json(1, array('url' => referer()));
    }
}