<?php  
	class Test_EweiShopV2Page extends WebPage {
		public function main () {
			load()->func('tpl');
			include $this->template('goods/test');
			exit();
		}
	}
?>