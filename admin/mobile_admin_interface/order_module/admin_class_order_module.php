<?php


class admin_class_order_module
{

    private $DB, $PARAM;

    public function __construct($PARAM)
    {
        $this->DB = DbOption();
        $this->PARAM = $PARAM;
    }

    /**
     *
     * 按类型获取异地订单
     *
     * @param row_n 获取结果行数
     * @param page_n 当前页码
     * @param type 订单类型 未发货(ON_DELIVER) 已发货(ON_WAY) 已完成(DELIVERED)
     */
    public function getYdOrder(){

        $start_index = ($this->PARAM['page_n']-1) * $this->PARAM['row_n'];

        $where = [
            "LIMIT" => [$start_index, $this->PARAM['row_n'] ],
            "AND" => [

                "T_ORDER_YD.STATUS[=]" => $this->PARAM['type'],
            ]
        ];

        $sql_result = $this->DB->select("T_ORDER_MAIN",
            [
                "[>]T_ORDER_YD" => ["ID" =>"MAIN_ID"]
            ],
            "*",
            $where
        );

        $count_result = $this->DB->count("T_ORDER_MAIN",
            [
                "[>]T_ORDER_YD" => ["ID" =>"MAIN_ID"]

            ],
            "*",
            $where
        );

        if( count($sql_result) != 0 )

            foreach ($sql_result as $index => $item)

                $sql_result[$index]["PRODUCT_LIST"] = $this->DB->select(
                    "T_ORDER_COMMODITY",
                    "*",
                    [
                        "ORDER_ID[=]" => $sql_result[$index]['ID']
                    ]
                );

        else{send_content_json(0, "获取同城订单失败(admin)", []);return;}

        send_result(1, "获取异地待发货订单", $count_result, $sql_result);
    }

    /**
     * 无参数
     * 获取待发货订单数
     */
    public function getUnshippingSum(){

        $result = $this->DB->count("T_ORDER_YD",[ 'STATUS[=]' => 'ON_DELIVER' ]);
        send_content_json(1, "获取待发货订单数量成功", ['sum' => $result]);
    }
    /**
     * 按门店名称获取门店待发货订单
     *
     * @param row_n 获取结果行数
     * @param page_n 当前页码
     * @param md 门店名称
     * @param type 订单类型 REFUNDED(退款) UNPAID(未支付) PAID(已支付) WAIT_PICK_UP(待取餐) TAKEN_MEALS(已派餐)
     */
    public function getMdOrder(){

        $start_index = ($this->PARAM['page_n']-1) * $this->PARAM['row_n'];

        $where = [   "LIMIT" =>
            [$start_index, $this->PARAM['row_n']],
            "AND" => [

                "MD_NAME[=]" => $this->PARAM['md'],
                "STATUS[=]" => $this->PARAM['type']
            ]
        ];

        $result = $this->DB->select("T_ORDER_MD", "*", $where);

        $result_count = $this->DB->count("T_ORDER_MD", "*", $where);

        if( count($result) != 0 )

            foreach ($result as $index => $item)

                $result[$index]["PRODUCT_LIST"] = $this->DB->select(
                    "T_ORDER_COMMODITY",
                    "*",
                    [
                        "ORDER_ID[=]" => $result[$index]['ID']
                    ]
                );

        else{send_content_json(0, "获取同城订单失败(admin)", []);return;}

        send_result(1, "获取异地待发货订单", $result_count, $result);
    }

    /**
     * 按类型获取同城订单
     *
     * @param row_n 获取结果行数
     * @param page_n 当前页码
     * @param type 订单类型 UNSHIPPED(待发货) DELIVERED(已送达) CANCEL(订单取消) ON_DELIVER(配送中) WAIT_DELIVER(等待配送员接单) ON_WAY(配送员正在赶过来)
     */
    public function getTcOrder(){

        $start_index = ($this->PARAM['page_n']-1) * $this->PARAM['row_n'];

        $where = [
            "LIMIT" => [$start_index, $this->PARAM['row_n'] ],
            "AND" => [

                "T_ORDER_TC.STATUS[=]" => $this->PARAM['type'],
            ]
        ];

        $result = $this->DB->select(
            "T_ORDER_MAIN",
            [
                "[>]T_ORDER_TC" => ["ID" =>"MAIN_ID"]
            ],
            $where
        );

        $result_count = $this->DB->count(
            "T_ORDER_MAIN",
            [
                "[>]T_ORDER_TC" => ["ID" =>"MAIN_ID"]
            ],
            $where
        );

        if( count($result) != 0 )

            foreach ($result as $index => $item)

                $result[$index]["PRODUCT_LIST"] = $this->DB->select(
                    "T_ORDER_COMMODITY",
                    "*",
                    [
                        "ORDER_ID[=]" => $result[$index]['ID']
                    ]
                );

        else{send_content_json(0, "获取同城订单失败(admin)", []);return;}

        send_result(1, "获取异地待发货订单", $result_count, $result);
    }

    /**
     * 同城、门店点单设为已读状态
     *
     * @param id 订单号
     *
     */
    public function setAlreadyRead(){

        switch (substr($this->PARAM['id'], 3,2)){
            case 'MD':
                $this->DB->update("T_ORDER_MD", [ 'CAN_SEE' => 1], [ 'MAIN_ID[=]' => $this->PARAM['id'] ] );
                break;

            case 'TC':
                $this->DB->update("T_ORDER_TC", [ 'CAN_SEE' => 1], [ 'MAIN_ID[=]' => $this->PARAM['id'] ] );
                break;
        }

        send_content_json(1, $this->PARAM['id'].":设为已读", []);
    }



}
