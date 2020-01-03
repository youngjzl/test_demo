<?php

class SignService {
  
    private static $instance;
  
    public static function instance() {
        if (self::$instance == null) {
          self::$instance = new SignService();
        }
        return self::$instance;
    }

    /**
     * sha256签名
     * @param $params
     * @param $key
     * @return string
     */
    function sign_sha256hex($params, $signkey){

        //去空
        $params = $this->paraFilter($params);

        //排序
        $params = $this->argSort($params);

        //生成签名字串
        $src_sign = "";
        foreach ($params as $key => $val) {
            $src_sign = $src_sign . $key . "=" . $val . ",";
        }
        //生成前后包含"sha256Key"的签名字串
        $src_sign = $signkey . "," . $src_sign . $signkey;
        $re = hash('sha256', $src_sign, true);

        return bin2hex($re);
    }

    public static function mcryptEncrypt($input, $key) {
        $size = mcrypt_get_block_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB);
        $input = self::pkcs5Pad($input, $size);
        $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, '', MCRYPT_MODE_ECB, '');
        $iv = mcrypt_create_iv (mcrypt_enc_get_iv_size($td), MCRYPT_RAND);//MCRYPT_DEV_URANDOM
        mcrypt_generic_init($td, $key, $iv);
        $data = mcrypt_generic($td, $input);
        mcrypt_generic_deinit($td);
        mcrypt_module_close($td);
        $data = base64_encode($data);
        return $data;
    }

    /**
     * [decrypt description]
     * 使用mcrypt库进行解密
     * @param  [type] $sStr
     * @param  [type] $sKey
     * @return [type]
     */
    public static function mcryptDecrypt($sStr, $sKey) {
        $iv = mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB), MCRYPT_RAND);//MCRYPT_DEV_URANDOM
        $decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $sKey, base64_decode($sStr), MCRYPT_MODE_ECB, $iv);
        $dec_s = strlen($decrypted);
        $padding = ord($decrypted[$dec_s-1]);
        $decrypted = substr($decrypted, 0, -$padding);
        return $decrypted;
    }

    /**
     * 获取客户端IP地址
     * @param integer $type 返回类型 0 返回IP地址 1 返回IPV4地址数字
     * @param boolean $adv 是否进行高级模式获取（有可能被伪装）
     * @return mixed
     */
    function get_client_ip($type = 0,$adv=false) {
        $type       =  $type ? 1 : 0;
        static $ip  =   NULL;
        if ($ip !== NULL) return $ip[$type];
        if($adv){
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $arr    =   explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
                $pos    =   array_search('unknown',$arr);
                if(false !== $pos) unset($arr[$pos]);
                $ip     =   trim($arr[0]);
            }elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip     =   $_SERVER['HTTP_CLIENT_IP'];
            }elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip     =   $_SERVER['REMOTE_ADDR'];
            }
        }elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip     =   $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u",ip2long($ip));
        $ip   = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }

    /**
     * post 请求
     * @param $url
     * @param $data
     * @param array $http_header
     * @return mixed
     */
    function http_post_function($url, $data, $http_header=[]) {

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        if(!empty($http_header))
            curl_setopt($curl, CURLOPT_HTTPHEADER, $http_header);

        $res = curl_exec($curl);
        curl_close($curl);

        return $res;
    }

    /**
     * [pkcs5Pad description]
     * @param  [type] $text
     * @param  [type] $blocksize
     * @return [type]
     */
    private static function pkcs5Pad($text, $blocksize) {
        $pad = $blocksize - (strlen($text) % $blocksize);
        return $text . str_repeat(chr($pad), $pad);
    }

    /**
    * 除去数组中的空值和签名参数
    * @param $para 签名参数组
    * return 去掉空值与签名参数后的新签名参数组
    */
    private function paraFilter($para) {
        $para_filter = array();
        while (list ($key, $val) = each ($para)) {
            if($key == "sign" || $key == "sign_type" || $val == "")
                continue;
            else  $para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }

    /**
    * 对数组排序
    * @param $para 排序前的数组
    * return 排序后的数组
    */
    private function argSort($para) {
        ksort($para);
        reset($para);
        return $para;
    }


}

