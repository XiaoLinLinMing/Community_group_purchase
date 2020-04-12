<?php


class admin_class_notice_module
{
    private $DB, $PARAM;
    public function __construct($PARAM)
    {
        $this->DB = DbOption();
        $this->PARAM = $PARAM;

    }

    /**
     * @param md_name 查询的门店编码 其他门店可在T_STORE_LIST查询
     * 查询是否有新订单
     */
    public function queryNewOrder(){

        if( count( $this->DB->select("T_STORE_LIST", "STORE_ID", ["STORE_ID[=]" => $this->PARAM['md_name']])) == 0){

            send_content_text(5479, "门店不存在", [ 'name' => $this->PARAM['md_name'] ]);
            return;
        }

        $result = $this->DB->select("T_NEW_NOTICE", "NOTICE", [ "MD[=]" => $this->PARAM['md_name'] ]);
        send_content_text(1, "提醒", $result);
    }

    /**
     * 查询顺丰未发货订单
     *
     * 测试通过
     */
    public function queryUnShippingOrder(){
        $row_n = $this->PARAM['ROW_N'];
        $page_n = $this->PARAM['PAGE_N'];
        $start_index = ($page_n-1) * $row_n;

        $where = [
            "LIMIT" => [$start_index, $row_n],
            'AND' => [
                'STATUS[=]' => 'ON_DELIVER'
            ]
        ];

        $result = $this->DB->select(
            "T_ORDER_YD",
            "MAIN_ID",
            $where
        );

        $result_count = $this->DB->count("T_ORDER_YD",$where['AND']);
        $return_data = ["count" => $result_count, "data"=> $result];
        send_content_json(1, "异地未发货订单列表", $return_data);
    }

    /**
     * 查询顺丰已发货订单
     */
    public function queryShippingOrder(){

        $row_n = $this->PARAM['ROW_N'];
        $page_n = $this->PARAM['PAGE_N'];
        $start_index = ($page_n-1) * $row_n;

        $where = ["LIMIT" => [$start_index, $row_n], 'AND' => ['STATUS[=]' => 'ON_WAY'] ];
        $result = $this->DB->select(
            "T_ORDER_YD",
            "MAIN_ID",
            $where
        );

        $result_count = $this->DB->count("T_ORDER_YD",$where['AND']);
        $return_data = ["count" => $result_count, "data"=> $result];
        send_content_json(1, "异地未发货订单列表", $return_data);
    }

    /**
     * 查询已退款订单
     */
    public function queryRefundOrder(){

        $orderType = $this->PARAM['order_type'];
        $row_n = $this->PARAM['row_n'];
        $page_n = $this->PARAM['page_n'];
        $start_index = ($page_n-1) * $row_n;
        $result = [];
        $where = [];
        switch ($orderType){

            //门店已退款订单
            case 'MD' :

                $result = $this->DB->select(
                    "T_ORDER_MD",
                    "*",
                    [
                        "LIMIT" => [$start_index, $row_n],
                        "AND" =>
                            [
                                "PAYMENT_STATUS[=]" => 'REFUND'
                            ]
                    ]
                );
                $result_count = $this->DB->count("T_ORDER_MD", [ "PAYMENT_STATUS[=]" => 'REFUNDED' ]);
                break;

            //同城已退款订单
            case 'TC' :

                $result = $this->DB->select(
                    "T_ORDER_MAIN",
                    [
                        "[>]T_ORDER_TC" => ["ID" =>"MAIN_ID"]
                    ],
                    "*",
                    [
                        "LIMIT" => [$start_index, $row_n],
                        "AND" =>
                            [
                                "T_ORDER_MAIN.PAYMENT_STATUS[=]" => 'REFUNDED'
                            ]
                    ]
                );


                $result_count = $this->DB->count("T_ORDER_MAIN", [ 'PAYMENT_STATUS[=]' => 'REFUNDED' ]);
                break;

        }

        if( count($result) == 0) send_content_text(0, "获取已退款订单失败", $result);

        else {

            $return_data = ["count" => $result_count, "data"=> $result];
            send_content_text(1, "获取已退款订单成功", $return_data);
        }


    }

    /**
     * 页面初始化
     */
    public function pageInit(){

        $data = $this->DB->select("T_RECORD", "*", [ 'RECORD_DATE' => date("Y-m-d")]);
        $result = [];

    }
}
