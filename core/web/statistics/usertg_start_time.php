<?php
if (!defined('IN_IA')) {
	exit('Access Denied');
}

class Usertg_start_time_EweiShopV2Page extends WebPage
{
	public function main()
	{
		global $_W;
		global $_GPC;
        $list=array(
            array(
                'realname'=>'i戏子',
                'start_time'=>'2019/12/02 14:13:28',
                'url'=>'好物推荐',
                'ip'=>'223.104.49.95',
                'address'=>'成都',
                'on_line_time_second'=>'18\'59"',
                'url_address'=>'web/index.php?c=site&a=entry&m=ewei_shopv2&do=web&r=statistics.usertg_start_time',
            ),
        );
		include $this->template('statistics/usertg_start_time');
	}
}

?>
