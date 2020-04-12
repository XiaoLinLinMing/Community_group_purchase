<?php


class class_logistics_sf_module
{
    //下单结果
    private $call_express_result, $DB, $PARAM;

    public function __construct($PARAM)
    {
        $this->DB = DbOption();
        $this->PARAM = $PARAM;
    }
    /*
     * 顺丰下单接口
     * */
    public function addSfLogisticsOrder(){

        /**
         * [OrderService 向顺丰下达快递订单]
         * @param [string] $order_id   [客户内部的订单号]
         * @param [string] $d_city     [收件人所在 市]
         * @param [string] $d_province [收件人所在 省]
         * @param [string] $d_county   [收件人所在 县/区]
         * @param [string] $d_company  [收件人所在 机构/组织/公司]
         * @param [string] $d_contact  [收件人姓名]
         * @param [string] $d_tel      [收件人手机号码]
         * @param [string] $d_address  [收件人地址]
         * @param [string] $d_goods_name [商品名]
         * @return XML_object
         */

        $order_id = $this->PARAM['order_id'];
        $d_city = $this->PARAM['d_city'];
        $d_province = $this->PARAM['d_province'];
        $d_county = $this->PARAM['d_county'];
        $d_company = $this->PARAM['d_company'];
        $d_contact = $this->PARAM['d_contact'];
        $d_tel = $this->PARAM['d_tel'];
        $d_address = $this->PARAM['d_address'];
        $d_goods_name = $this->PARAM['d_goods_name'];


        /*
         * XML请求串拼接
         *
         * */
        $xml_content = '<?xml version="1.0"?><Request lang="zh-CN" service="OrderService">';
        $xml_content = $xml_content . '<Head>' .$GLOBALS['config']['LOGISTICS']['SF']['CLIENT_CODE']. '</Head>';
        $xml_content = $xml_content . '<Body>';
        $xml_content = $xml_content . '<Order orderid="'.$order_id.'"'.' ';

        //说明该快件为到岛内件
        if(strpos($d_province, "海南") != false){
            $xml_content = $xml_content . 'express_type="33"'.' ';
        }

        else $xml_content = $xml_content . 'express_type="1"'.' ';
        //寄件地址
        $xml_content = $xml_content . 'j_province="' .$GLOBALS['config']['LOGISTICS']['SF']['SENDER_PROVINCE']. '"'.' ';
        $xml_content = $xml_content . 'j_city="'.$GLOBALS['config']['LOGISTICS']['SF']['SENDER_CITY'].'"'.' ';
        $xml_content = $xml_content . 'j_contact="'.$GLOBALS['config']['LOGISTICS']['SF']['SENDER_CONTACT'].'"'.' ';
        $xml_content = $xml_content . 'j_county="'.$GLOBALS['config']['LOGISTICS']['SF']['SENDER_COUNTY'].'"'.' ';
        $xml_content = $xml_content . 'j_company="'.$GLOBALS['config']['LOGISTICS']['SF']['SENDER_COMPANY'].'"'.' ';
        $xml_content = $xml_content . 'j_tel="'.$GLOBALS['config']['LOGISTICS']['SF']['SENDER_TEL'].'"'.' ';
        $xml_content = $xml_content . 'j_address="'.$GLOBALS['config']['LOGISTICS']['SF']['SENDER_DETAILED_ADDRESS'].'"'.' ';

        //收件地址
        $xml_content = $xml_content . 'd_province="' .$d_province. '"'.' ';

        //2019年10月19日09:58:40 BUG修改

        if (strpos($d_city, "县") != false) {
            //$d_city =  substr($d_county,0,strlen($d_city)-2);
            $d_city = "";
        }
        $xml_content = $xml_content . 'd_city="'.$d_city.'"'.' ';
        $xml_content = $xml_content . 'd_contact="'.$d_contact.'"'.' ';
        $xml_content = $xml_content . 'd_county="'.$d_county.'"'.' ';
        $xml_content = $xml_content . 'd_company="'.$d_company.'"'.' ';
        $xml_content = $xml_content . 'd_tel="'.$d_tel.'"'.' ';
        $xml_content = $xml_content . 'd_address="'.$d_address.'"'.' ';
        $xml_content = $xml_content . 'pay_method="2"'.' ';
        $xml_content = $xml_content . 'parcel_quantity="1"'.' ';
        $xml_content = $xml_content . 'custid="'.$this->custid.'"'.' ';
        $xml_content = $xml_content . 'routelabelService="1"'.' ';
        $xml_content = $xml_content . 'customs_batchs=""'.' ';
        $xml_content = $xml_content . 'cargo="叶记手工绿豆馅饼"'.'> ';
        $xml_content = $xml_content . '</Order>';
        $xml_content = $xml_content . '</Body></Request>';

        //创建XML文件用于存放订单数据
        $OrderServiceFile = fopen("OrderService.xml", "w");
        fwrite($OrderServiceFile, $xml_content);
        fclose($OrderServiceFile);

        //读取请求XML数据
        $xmlContent = file_get_contents("OrderService.xml");

        //请求内容 + 验证码 先转换MD5 再转 base64
        $verifyCode = base64_encode(md5(($xmlContent . $this->checkword), TRUE));
        //请求参数
        $post_data = array(
            'xml' => $xmlContent,
            'verifyCode' => $verifyCode
        );

        $result= $this->send_post("http://bsp-oisp.sf-express.com/bsp-oisp/sfexpressService", $post_data);
        $this->call_express_result = json_decode(
            json_encode(
                simplexml_load_string(
                    $result,
                    'SimpleXMLElement',
                    LIBXML_NOCDATA)
            ),
            true
        );
    }

    /**
     *  顺丰路由查询接口
     */
    public function querySfExpress(){

    }

    /**
     * 顺丰时效运费查询
     */
    public function getPostage(){

        //运费表
        $postageArr = [];
        $arr['first'] = 0;
        $arr['second'] = 0;
        $postageArr['224'] = ['广东','广西'];
        $postageArr['235'] = ['福建','贵州'];
        $postageArr['236'] = ['湖南','江西','云南'];
        $postageArr['237'] = ['安徽','北京','重庆','上海','天津','河北','河南','湖北','江苏','辽宁','山东','山西','济南','四川','浙江'];
        $postageArr['239'] = ['甘肃'];
        $postageArr['2510'] = ['黑龙','吉林'];
        $postageArr['2813'] = ['西藏','新疆'];

        if ( in_array($this->PARAM['province'], $postageArr['224']) ){

            $arr['first'] = 22;
            $arr['second'] = 4;
            send_content_json(1, "顺丰时效运费查询成功", $arr);
            return;
        }

        if ( in_array($this->PARAM['province'], $postageArr['235']) ){

            $arr['first'] = 23;
            $arr['second'] = 5;
            send_content_json(1, "顺丰时效运费查询成功", $arr);
            return;
        }

        if ( in_array($this->PARAM['province'], $postageArr['236']) ){

            $arr['first'] = 23;
            $arr['second'] = 6;
            send_content_json(1, "顺丰时效运费查询成功", $arr);
            return;
        }

        if ( in_array($this->PARAM['province'], $postageArr['237']) ){

            $arr['first'] = 23;
            $arr['second'] = 7;
            send_content_json(1, "顺丰时效运费查询成功", $arr);
            return;
        }

        if ( in_array($this->PARAM['province'], $postageArr['239']) ){

            $arr['first'] = 23;
            $arr['second'] = 9;
            send_content_json(1, "顺丰时效运费查询成功", $arr);
            return;
        }

        if ( in_array($this->PARAM['province'], $postageArr['2510']) ){

            $arr['first'] = 25;
            $arr['second'] = 10;
            send_content_json(1, "顺丰时效运费查询成功", $arr);
            return;
        }

        if ( in_array($this->PARAM['province'], $postageArr['2813']) ){

            $arr['first'] = 28;
            $arr['second'] = 13;
            echo json_encode($arr);
            return;
        }

        if ( $this->PARAM['province'] === '海南'){

            $arr['first'] = 12;
            $arr['second'] = 1;
            send_content_json(1, "顺丰时效运费查询成功", $arr);
            return;
        }

        send_content_json(0, "该地址不支持", []);


    }
}
