<?php


/**
 * Class class_order_module
 *
 *  1.统一下单接口
 *  2.用户获取异地订单列表
 *  3.用户同城订单列表
 *  4.用户获取门店订单
 */

class class_order_module
{

    private $DB, $PARAM;

    public function __construct($PARAM)
    {
        $this->DB = DbOption();
        $this->PARAM = $PARAM;
    }


    /**
     * 统一下单接口 仅下单，订单为未支付状态，要进一步申请支付
     *
     * t=client&c=class_order_module&f=addOrder&p={"freight":25.00,"total":"15.25","type":"TC","client_id":"abcdefg","name":"lin","tel":"15707860113","province"%20:%20"海南省","city":"三亚市","county":"吉阳区","address":"不知道","commodity_price":15.25,"product_list":[{"id":"123","name":"测试商品",%20"price":15.25,%20"qty":1}]}
     * t=client&c=class_order_module&f=addOrder&p={"lng":"154","lat":"154","company":"三亚学院","freight":25.00,"total":"15.25","type":"TC","client_id":"abcdefg","name":"lin","tel":"15707860113","province"%20:%20"海南省","city":"三亚市","county":"吉阳区","address":"不知道","commodity_price":15.25,"product_list":[{"id":"123","name":"测试商品",%20"price":15.25,%20"qty":1}]}
     *
     *测试通过
     *
     *
     */
    public function addOrder(){

        //订单ID
        $order_id = "BB-" . $this->PARAM['type'] . time(); //例如:BB-MD1234587456 代表饼饼商城的门店订单

        //若订单不属于门店订单
        if( $this->PARAM['type'] != 'MD') {

            $sql_result = $this->DB->insert("T_ORDER_MAIN", [
                "ID" => $order_id,
                "CLIENT_ID" => $this->PARAM['client_id'],
                "NAME" => $this->PARAM['name'],
                "TEL" => $this->PARAM['tel'],
                "PROVINCE" => $this->PARAM['province'],
                "CITY" => $this->PARAM['city'],
                "COUNTY" => $this->PARAM['county'],
                "ADDRESS" => $this->PARAM['address'],
                "COMMODITY_PRICE" => floatval($this->PARAM['commodity_price']),
                "PAYMENT_STATUS" => "UNPAID",
                "TYPE" => $this->PARAM['type'],
                "FREIGHT" => floatval($this->PARAM['freight']),
                "TOTAL" => floatval($this->PARAM['total']),
                "WAYBILL_NO" => "UNSHIPPED"
            ]);


            //若订单为异地订单 则添加异地订单信息
            if( $this->PARAM['type'] == 'YD'){

                $sql_result = $this->DB->insert("T_ORDER_YD", [

                    "MAIN_ID" => $order_id,
                    "STATUS" => "ON_DELIVER"
                ]);
            }

            //若订单为同城订单 则添加配送员信息
            if( $this->PARAM['type'] == 'TC'){

                $sql_result =  $this->DB->insert("T_ORDER_TC", [

                    "MAIN_ID" => $order_id,
                    "LNG" => $this->PARAM['lng'],
                    "LAT" => $this->PARAM['lat'],
                    "COMPANY" => $this->PARAM['company'],
                    "STATUS" => "UNSHIPPED"
                ]);


            }


            if($sql_result == false){
                send_content_json("SUCCESS","下单失败", $this->DB->error());
                return;
            }



        }

        else{


            $this->DB->insert("T_ORDER_MD", [
                "ID" => $order_id,
                "TOTAL" => floatval($this->PARAM['total']),
                "TAKE_TIME" => strval(date('Y-m-d H:m:s',$this->PARAM['take_time'])),
                "MD_NAME" => $this->PARAM['md_name'],
                "CLIENT_ID" => $this->PARAM['client_id'],
                "CLIENT_NAME" => $this->PARAM['client_name']
            ]);
            //echo $this->DB->last_query();

        }


        //最后添加商品数据

        foreach ($this->PARAM['product_list'] as $product_obj) {

            $this->DB->insert("T_ORDER_COMMODITY", [
                "PRODUCT_ID" => $product_obj['id'],
                "NAME" => $product_obj['name'],
                "PRICE" => $product_obj['price'],
                "QTY" => $product_obj['qty'],
                "ORDER_ID" => $order_id,
            ]);

        }

        send_content_text(1, "下单成功", [ "order_id" => $order_id]);
    }

    /**
     * 用户获取 异地 订单列表 （运输中ON_DELIVER）(待发货 UNSHIPPED) （已送达 FINISH）
     * 测试通过
     */
    public function getYdOrderList(){

        $get_type = $this->PARAM['type'];
        $get_client = $this->PARAM['client_id'];
        $page_n = $this->PARAM['page_n'];
        $row_n = $this->PARAM['row_n'];
        $start_index = ($page_n-1) * $row_n;

        if($get_type !== "ON_DELIVER" && $get_type !== "UNSHIPPED" && $get_type !== "FINISH"){

            send_content_text("FAILURE", "参数错误", $GLOBALS['config']['ERROR_CODE']['PARAMETER_ERROR']);
            return;
        }

        //查询条件 用于订单查询 和符合条件的行数查询（分页）
        $where = [   "LIMIT" => [$start_index, $row_n],
            "AND" => [
                "T_ORDER_MAIN.CLIENT_ID[=]"=>$get_client,
                "T_ORDER_YD.STATUS[=]" => $get_type,

            ]
        ];

        //订单查询结果
        $sql_result = $this->DB->select("T_ORDER_MAIN",
            [
                "[>]T_ORDER_YD" => ["ID" =>"MAIN_ID"]

            ],
            "*",
            $where
           );

        if( count($sql_result) != 0)
            foreach ($sql_result as $index => $item)
                $sql_result[$index]["PRODUCT_LIST"] = $this->DB->select(
                    "T_ORDER_COMMODITY",
                    "*",
                    [
                        "ORDER_ID[=]" => $sql_result[0]['ID']
                    ]
                );

        //总结果行数
        $count_result = $this->DB->count("T_ORDER_MAIN",
            [
                "[>]T_ORDER_YD" => ["ID" =>"MAIN_ID"]
            ],
            $where
        );

        send_result(1, $get_client."：获取运输中订单", $count_result, $sql_result);

    }

    /**
     * 用户获取 同城 订单列表 UNSHIPPED(待发货) DELIVERED(已送达) CANCEL(订单取消) ON_DELIVER(配送中) WAIT_DELIVER(等待配送员结单) ON_WAY(配送员正在赶过来)
     */
    public function getTcOrderList(){

        $get_type = $this->PARAM['type'];
        $get_client = $this->PARAM['client_id'];
        $page_n = $this->PARAM['page_n'];
        $row_n = $this->PARAM['row_n'];
        $start_index = ($page_n-1) * $row_n;

        if(
            $get_type !== "DELIVERED" &&
            $get_type !== "CANCEL" &&
            $get_type !== "ON_DELIVER" &&
            $get_type !== "WAIT_DELIVER" &&
            $get_type !== "ON_WAY" &&
            $get_type !== "UNSHIPPED"

        ){

            send_content_text("FAILURE", "参数错误", $GLOBALS['config']['ERROR_CODE']['PARAMETER_ERROR']);
            return;
        }

        $where = [   "LIMIT" => [$start_index, $row_n],
            "AND" => [
                "T_ORDER_MAIN.CLIENT_ID[=]"=>$get_client,
                "T_ORDER_TC.STATUS[=]" => $get_type
            ]
        ];

        $sql_result = $this->DB->select("T_ORDER_MAIN",
            [
                "[>]T_ORDER_TC" => ["ID" =>"MAIN_ID"]

            ],
            "*",
            $where

        );

        if( count($sql_result) !=0 )
            foreach ($sql_result as $index => $item)
                $sql_result[$index]["PRODUCT_LIST"] = $this->DB->select(
                    "T_ORDER_COMMODITY",
                    "*",
                    [
                        "ORDER_ID[=]" => $sql_result[0]['ID']
                    ]
                );

        //总结果行数
        $count_result = $this->DB->count("T_ORDER_MAIN",
            [
                "[>]T_ORDER_YD" => ["ID" =>"MAIN_ID"]
            ],
            $where
        );

        send_result(1, $get_client."：获取同城订单", $count_result, $sql_result);

    }

    /**
     * 用户获取 门店订单
     */
    public function getMdOrderList(){

        /*支付状态 UNPAID(未支付) PAID(已支付) WAIT_PICK_UP(待取餐) TAKEN_MEALS(已派餐)*/
        $get_type = $this->PARAM['type'];
        $get_client = $this->PARAM['client_id'];
        $page_n = $this->PARAM['page_n'];
        $row_n = $this->PARAM['row_n'];
        $start_index = ($page_n-1) * $row_n;

        if(
            $get_type !== "UNPAID" &&
            $get_type !== "PAID" &&
            $get_type !== "WAIT_PICK_UP" &&
            $get_type !== "TAKEN_MEALS"

        ){

            send_content_text("FAILURE", "参数错误", $GLOBALS['config']['ERROR_CODE']['PARAMETER_ERROR']);
            return;
        }


        $where =  [
            "LIMIT"=>[$start_index, $row_n],
            "AND" => [
                "CLIENT_ID[=]" => $get_client,
                "PAYMENT_STATUS" => $this->PARAM['type']
            ]
        ];
        $sql_result = $this->DB->select("T_ORDER_MD","*", $where);

        if( count($sql_result) !=0 )
            foreach ($sql_result as $index => $item)
                $sql_result[$index]['PRODUCT_LIST'] = $this->DB->select("T_ORDER_COMMODITY","*", [ "ORDER_ID[=]" => $item['ID'] ]);


        //总结果行数
        $count_result = $this->DB->count("T_ORDER_MD", $where);
        send_result(1, $get_client."：获取门店订单", $count_result, $sql_result);

    }

}