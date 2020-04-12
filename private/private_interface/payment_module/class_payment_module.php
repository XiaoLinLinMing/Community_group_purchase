<?php

/**
 * Class class_payment_module
 *
 * 还差支付完成回调接口
 */

class class_payment_module
{
    protected $DB, $PARAM, $nonce_str, $sign, $open_id;

    public function __construct($PARAM)
    {
        $this->DB = DbOption();
        $this->PARAM = $PARAM;
        $this->nonce_str= $this->getRandomString();
        $this->open_id = $this->getClientOpenId();

    }

    /**
     * @param out_trade_no 商户内部订单号 String
     * @param total_fee  支付金额 单位分 INT
     * @param openid  微信小程序用户的open_id String
     * @param spbill_create_ip  支付设备的IP String
     * @param login_code 用户登录凭证 String
     * 微信支付统一下单接口
     */
    public  function CallWeChatPay(){

        $payResult = $this->xml_to_array(
            $this->HttpPostCallWechatPay(
                "https://api.mch.weixin.qq.com/pay/unifiedorder",
                $this->getXmlRequestString()
            )
        );


        //获取当前时间戳
        $timeStamp = time();

        //合成二次签名字符串
        $secondSign = "appId=" . $GLOBALS['config']['MINIPROGRAM']['APP_ID'];
        $secondSign = $secondSign . "&nonceStr=" . $this->nonce_str;
        $secondSign = $secondSign . "&package=prepay_id=" . $payResult['prepay_id'];
        $secondSign = $secondSign . "&signType=MD5";
        $secondSign = $secondSign . "&timeStamp=" . $timeStamp;
        $secondSign = $secondSign . "&key=" . $GLOBALS['config']['MINIPROGRAM']['MCH_KEY'];

        $secondSign = strtoupper(md5($secondSign));//二次签名已成功生成

        $WeChatPay_result = array();
        //随机字符串
        $WeChatPay_result['nonceStr'] = $this->nonce_str;
        //第一次签名
        $WeChatPay_result['sign'] = $this->sign;
        //二次签名
        $WeChatPay_result['secondSign'] = $secondSign;
        //提起预支付的时间戳
        $WeChatPay_result['timeStamp']= $timeStamp;
        //商户内部订单号
        $WeChatPay_result['order_id'] = $this->PARAM["out_trade_no"];
        //prepay_id
        $WeChatPay_result['prepay_id'] =  $payResult['prepay_id'];

        send_content_json(1, "微信预支付成功", $WeChatPay_result);

    }


    /**
     * @param client_id 小程序用户唯一标识 String
     * @param login_code 小程序用户登录凭证 String
     * 获取微信小程序用户open_id
     *
     * @return bool|string open_id
     */
    protected function getClientOpenId(){

        $openUrl = "https://api.weixin.qq.com/sns/jscode2session?appid=".$GLOBALS['config']['MINIPROGRAM']['APP_ID'];
        $openUrl = $openUrl . "&secret=".$GLOBALS['config']['MINIPROGRAM']['SECRET'];
        $openUrl = $openUrl . "&js_code=".$this->PARAM["login_code"];
        $openUrl = $openUrl . "&grant_type=authorization_code";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $openUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $data = curl_exec($curl);
        curl_close($curl);
        send_content_text(1, "获取openid成功", $data);
        return $data;
    }
    /**
     * 生成32位随机字符串
     */
    protected function getRandomString(){


        $base = 'ABCDEFGHIJKLOMNOPQRSTUVWXYZ';
        $random_string = '';
        for($i=0;$i<31;$i++)
            $random_string .= $base[mt_rand(0,26)];
        return $random_string;
    }


    /**
     * @return string 微信支付签名
     */
    protected function getSign(){

        if( !is_int($this->PARAM["total_fee"]) ) {
            send_content_text(5479, "预支付失败", ["msg" => "total_fee字段类型出错"]);
            return;
        }

        //微信预支付签名
        $sign = "";

        // 2.生成签名
        $sign_str = "appid=".$GLOBALS['config']['MINIPROGRAM']['APP_ID'];
        $sign_str .= "&body=".$GLOBALS['config']['MINIPROGRAM']['PAY_BODY'];
        $sign_str .= "&mch_id=".$GLOBALS['config']['MINIPROGRAM']['MCH_ID'];
        $sign_str .= "&nonce_str=".$this->nonce_str;
        $sign_str .= "&notify_url=".$GLOBALS['config']['MINIPROGRAM']['REFUND_CALL_BACK_URL'];
        //此处修改为 系统自动获取用户openid
        //$sign_str .= "&openid=".$this->PARAM["openid"];
        $sign_str .= "&openid=".$this->open_id;
        $sign_str .= "&out_trade_no=".$this->PARAM["out_trade_no"];
        $sign_str .= "&spbill_create_ip=".$this->PARAM["spbill_create_ip"];
        $sign_str .= "&total_fee=". $this->PARAM["total_fee"];
        $sign_str .= "&trade_type="."JSAPI";
        $sign_str .= "&key=". $GLOBALS['config']['MINIPROGRAM']['MCH_KEY'];

        $sign = strtoupper(md5($sign_str));//签名已生成
        $this->sign = $sign;
        return $sign;
    }

    /**
     * 获取XML请求报文
     */
    protected function getXmlRequestString(){

        if( !is_int($this->PARAM["total_fee"]) ) {
            send_content_text(5479, "预支付失败", ["msg" => "total_fee字段类型出错"]);
            return;
        }

        $appId = "<appid><![CDATA[".$GLOBALS['config']['MINIPROGRAM']['APP_ID']."]]></appid>";
        $body = "<body><![CDATA[".$GLOBALS['config']['MINIPROGRAM']['PAY_BODY']."]]></body>";
        $mch_id = "<mch_id><![CDATA[".$GLOBALS['config']['MINIPROGRAM']['MCH_ID']."]]></mch_id>";
        $nonce_str = "<nonce_str><![CDATA[".$this->nonce_str."]]></nonce_str>";
        $notify_url = "<notify_url><![CDATA[".$GLOBALS['config']['MINIPROGRAM']['REFUND_CALL_BACK_URL']."]]></notify_url>";
        $openid = "<openid><![CDATA[".$this->open_id."]]></openid>";
        $out_trade_no = "<out_trade_no><![CDATA[".$this->PARAM["out_trade_no"]."]]></out_trade_no>";
        $spbill_create_ip = "<spbill_create_ip><![CDATA[".$this->PARAM["spbill_create_ip"]."]]></spbill_create_ip>";
        $total_fee = "<total_fee><![CDATA[".$this->PARAM["total_fee"]."]]></total_fee>";
        $trade_type = "<trade_type><![CDATA[JSAPI]]></trade_type>";
        $sign = "<sign>".$this->getSign()."</sign>";
        $requestXML = "<xml>".$appId.$body.$mch_id.$nonce_str.$notify_url.$openid.$out_trade_no.$spbill_create_ip.$total_fee.$trade_type.$sign."</xml>";

        return $requestXML;
    }

    /** 发起http post请求微信支付下单
     * @param $url
     * @param $param
     * @return bool|string
     */
    protected function HttpPostCallWechatPay($url, $param){

        $ch = curl_init();
        //如果$param是数组的话直接用
        curl_setopt($ch, CURLOPT_URL, $url);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $param);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

        $data = curl_exec($ch);

        curl_close($ch);
        return $data;
    }

    protected function xml_to_array($xml){

        if(!$xml){
            return false;
        }

        libxml_disable_entity_loader(true);
        $data = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
        return $data;
    }

}
