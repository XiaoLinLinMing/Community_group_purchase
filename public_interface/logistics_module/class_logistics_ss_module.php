<?php


namespace logistics_module;


class class_logistics_ss_module
{

    private $DB, $PARAM;
    public function __construct($PARAM)
    {
        $this->DB = DbOption();
        $this->PARAM = $PARAM;

    }


    /**
     * address 详细地址
     * place_name 地点名称
     * city 城市名
     * latitude 经度
     * longitude 纬度
     * tel 收件人联系方式
     * contact 联系人姓名
     * order_id 商户内部订单号
     * product_name 商品名称
     * weight 重量
     * type 请求类型 下单(xd) 查询费用(fy)
     * 闪送下单接口
     */

    public function callSsExpress(){

        $address = $this->PARAM['address'];
        $place_name = $this->PARAM['place_name'];
        $city = $this->PARAM['city'];
        $latitude = $this->PARAM['latitude'];
        $longitude = $this->PARAM['longitude'];
        $tel = $this->PARAM['tel'];
        $contact = $this->PARAM['contact'];
        $order_id = $this->PARAM['order_id'];
        $product_name = $this->PARAM['product_name'];
        $weight = $this->PARAM['weight'];

        //预约取件时间 一般为null 即立即生效
        //$appointTime = $this->PARAM['appointTime'];


        /*收件人部分*/
        $receiver = array(

            'addr' => $address,
            'addrDetail' => $place_name,
            'city' => $city,
            'lat' => floatval($latitude),
            'lng' => floatval($longitude),
            'mobile' => $tel,
            'name' => $contact
        );

        $receiverList = [$receiver];

        /*寄件人部分*/
        $sender = array(

            'addr' => $GLOBALS['config']['LOGISTICS']['SS']['SENDER_ADDRESS'],
            'addrDetail' => $GLOBALS['config']['LOGISTICS']['SS']['SENDER_ADDRESS_DETAILED'],
            'city' => $GLOBALS['config']['LOGISTICS']['SS']['SENDER_CITY'],
            'lat' => $GLOBALS['config']['LOGISTICS']['SS']['SENDER_LAT'],
            'lng' => $GLOBALS['config']['LOGISTICS']['SS']['SENDER_LNG'],
            'mobile' => $GLOBALS['config']['LOGISTICS']['SS']['SENDER_TEL'],
            'name' => $GLOBALS['config']['LOGISTICS']['SS']['SENDER_CONTACT']
        );

        /*商户部分*/
        $merchant = [

            'id' => $GLOBALS['config']['LOGISTICS']['SS']['PARTNER_ID'],
            'mobile' => $GLOBALS['config']['LOGISTICS']['SS']['PARTNER_MOBILE'],
            'name' => $GLOBALS['config']['LOGISTICS']['SS']['PARTNER_COMPANY'],
            'token' => $GLOBALS['config']['LOGISTICS']['SS']['TOKEN'],
        ];

        /*签名生成*/
        $signature = md5(
            $GLOBALS['config']['LOGISTICS']['SS']['PARTNER_NO']  .
            $order_id .
            $GLOBALS['config']['LOGISTICS']['SS']['PARTNER_MOBILE'] .
            $GLOBALS['config']['LOGISTICS']['SS']['KEY']
        );
        $signature =  strtoupper($signature);


        /*订单部分*/
        $order = [

            'orderNo' => $order_id,
            'merchant' => $merchant,
            'receiverList' => $receiverList,
            'sender' => $sender,
            'goods' => $product_name,
            'weight' => floatval($weight),
            'addition' => 0,
            'remark' => "轻拿轻放",
            'appointTime' => "null",
        ];

        /*请求串生成*/
        $request_arr = array(

            'order' => $order,
            'partnerNo' => $GLOBALS['config']['LOGISTICS']['SS']['PARTNER_ID'],
            'signature' => $signature,
        );

        $result = [];
        switch ($this->PARAM['type']){

            case 'xd':
                $result = json_decode(
                    http_post_json(
                        $GLOBALS['config']['LOGISTICS']['SS']['CALL_SS_EXPRESS_OPEN'],
                        json_encode($request_arr)
                    ),
                    true
                );break;

            case 'fy' :
                $result = json_decode(
                    http_post_json(
                        $GLOBALS['config']['LOGISTICS']['SS']['QUERY_SS_FREIGHT'],
                        json_encode($request_arr)
                    ),
                    true
                );break;
        }

        //下单成功
        if ($result['status'] == 'OK') {

            //若是查询费用则
            if( $this->PARAM['type'] == 'fy' ){

                send_content_json(1, '费用查询成功', $result['data']);
                return;
            }

            //以下为下单时执行
            $iss_order_id = $result['data'];//闪送运单号
            //先查询订单状态获取
            $gerInfoUrl = $GLOBALS['config']['LOGISTICS']['SS']['QUERY_ORDER_INFO'];
            $gerInfoUrl = $gerInfoUrl . "?partnerno=".$GLOBALS['config']['LOGISTICS']['SS']['PARTNER_NO'];
            $gerInfoUrl = $gerInfoUrl . "&orderno=". $order_id;
            $gerInfoUrl = $gerInfoUrl . "&mobile=". $GLOBALS['config']['LOGISTICS']['SS']['SENDER_TEL'];
            $gerInfoUrl = $gerInfoUrl . "&signature=".$signature;
            $gerInfoUrl = $gerInfoUrl . "&issorderno=". $iss_order_id;

            $getData = json_decode(file_get_contents($gerInfoUrl), true);

            if($getData['status'] == 'ER'){

                //若查询失败
                print_err_log("ISS", $getData['errMsg'] . "--" . $getData['errCode']);
                send_content_json(0, "订单状态查询失败，请联系开发人员", ["msg" => $getData['errMsg'] ]);
                return;
            }

            $pickupPassword = $getData['data']['pickupPassword']; //取件码
            $courierName = $getData['data']['courierName'];//闪送员姓名
            $courier = $getData['data']['courier'];//闪送员联系方式
            $orderStatusNo = $getData['data']['orderStatusTxt'];//订单状态

            //订单状态数字编码转字符
            $orderStatusCode = '';
            switch ($orderStatusNo){
                case 20: $orderStatusCode = 'WAIT_DELIVER';break;
                case 30: $orderStatusCode = 'ON_WAY';break;
                case 42: $orderStatusCode = 'ON_DELIVER';break;
                case 44: $orderStatusCode = 'ON_DELIVER';break;
                case 60: $orderStatusCode = 'DELIVERED';break;
                case 64: $orderStatusCode = 'CANCEL';break;
            }

            //更新订单表中的运单号
            $this->DB->update('T_ORDER_MAIN',
                [
                    "WAYBILL_NO" => $iss_order_id
                ]
                ,
                [
                    "ID[=]" => $order_id
                ]
            );

            //同城订单扩展表插入新数据
            $this->DB->insert("T_ORDER_TC", [
                "MAIN_ID" => $order_id,
                "LNG" => $latitude,
                "LAT" => $longitude,
                "COMPANY" => $place_name,
                "DC_NAME" => $courierName,
                "DC_TEL" => $courier,
                "STATUS" => $orderStatusCode,
                "PICK_UP_CODE" => $pickupPassword
            ]);

            send_content_text(1, "闪送下单成功", [

                'DC_NAME' => $courierName,
                'DC_TEL' => $courier,
                'PICK_UP_CODE' => $pickupPassword
            ]);
        }

    }

    /**
     *  ID 商户内部订单号
     *  WAYBILL_NO 闪送平台订单编号 （运单号）
     *
     *  闪送取消订单接口
     */
    public function cancelSsOrder(){


        /*签名生成*/
        $signature = md5(
            $GLOBALS['config']['LOGISTICS']['SS']['PARTNER_NO']  .
            $this->PARAM['ID'] .
            $GLOBALS['config']['LOGISTICS']['SS']['PARTNER_MOBILE'] .
            $GLOBALS['config']['LOGISTICS']['SS']['KEY']
        );
        $signature =  strtoupper($signature);

        $request_str = $GLOBALS['config']['LOGISTICS']['SS']['ORDER_CANCEL'];
        $request_str .= ("?partnerno=" . $GLOBALS['config']['LOGISTICS']['SS']['PARTNER_NO']);
        $request_str .= ("&orderno=" . $this->PARAM['ID']);
        $request_str .= ("&mobile" . $GLOBALS['config']['LOGISTICS']['SS']['SENDER_TEL']);
        $request_str .= ("&signature" . $signature);
        $request_str .= ("&issorderno" . $this->PARAM['WAYBILL_NO']);

        $getData = json_decode(file_get_contents($request_str), true);

        if( $getData['status'] == 'OK' && $getData['data']['orderStatus'] == 64){

            send_content_text(1, "取消订单成功", []);
            $this->DB->update("T_ORDER_TC", [ 'STATUS' => 'CANCEL' ] , [ 'MAIN_ID' => $this->PARAM['ID']]);
        }

        else{

            send_content_text(0, "取消订单失败", [ "msg" => $getData['errMsg'] ]);
            print_err_log("ISS", $getData['errMsg'] . "--" . $getData['errCode']);

        }

    }

    /**
     *  ID 商户内部订单号
     *  WAYBILL_NO 闪送平台订单编号 （运单号）
     *
     *  按订单号查询闪送订单数据接口
     */
    public function querySsOrderBy(){

        /*签名生成*/
        $signature = md5(
            $GLOBALS['config']['LOGISTICS']['SS']['PARTNER_NO']  .
            $this->PARAM['ID'] .
            $GLOBALS['config']['LOGISTICS']['SS']['PARTNER_MOBILE'] .
            $GLOBALS['config']['LOGISTICS']['SS']['KEY']
        );
        $signature =  strtoupper($signature);

        $request_str = $GLOBALS['config']['LOGISTICS']['SS']['ORDER_CANCEL'];
        $request_str .= ("?partnerno=" . $GLOBALS['config']['LOGISTICS']['SS']['PARTNER_NO']);
        $request_str .= ("&orderno=" . $this->PARAM['ID']);
        $request_str .= ("&mobile" . $GLOBALS['config']['LOGISTICS']['SS']['SENDER_TEL']);
        $request_str .= ("&signature" . $signature);
        $request_str .= ("&issorderno" . $this->PARAM['WAYBILL_NO']);

        $getData = json_decode(file_get_contents($request_str), true);
        if( $getData['status'] == 'OK')

            send_content_json(1, "订单状态查询成功", $getData);



        else{

            send_content_text(0, "订单状态查询失败", [ "msg" => $getData['errMsg'] ]);
            print_err_log("ISS", $getData['errMsg'] . "--" . $getData['errCode']);

        }

    }

    /**
     * 获取闪送运费
     *
     */
    public function getSsPostage(){

    }

    protected function http_post_json($url, $jsonStr) {

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonStr);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json; charset=utf-8',
                'Content-Length: ' . strlen($jsonStr)
            )
        );
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $response;
    }

}
