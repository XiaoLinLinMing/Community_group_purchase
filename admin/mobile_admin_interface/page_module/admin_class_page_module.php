<?php


class admin_class_page_module
{
    private $DB, $PARAM;
    public function __construct($PARAM)
    {
        $this->DB = DbOption();
        $this->PARAM = $PARAM;
    }


    /**
     * 检查token是否过期
     */
    public function inspectToken(){

        send_content_json(1, "令牌未过期", []);
    }
    /**
     * 无参数
     *
     * 页面初始化数据 总销量、订单量等
     */
    public function getPageInit(){

        $history = [];//历史数据

        //此逻辑待定
        if( count($this->DB->select("T_RECORD_MAIN", "SALE_TOTAL", [])) == 0)

            //若没有记录则插入新纪录
            $this->DB->insert(
                'T_RECORD_MAIN',
                [
                    'SALE_TOTAL' => 0.0,
                    'ORDER_TOTAL' => 0,
                    'CLIENT_TOTAL' => 0,
                    'MD_ORDER_TOTAL' => 0,
                    'TC_ORDER_TOTAL' => 0,
                    'YD_ORDER_TOTAL' => 0,
                    'REFUND_ORDER_TOTAL' => 0,
                    'REFUND_TOTAL' => 0.0
                ]
            );

        if( $this->DB->count('T_RECORD', [ 'RECORD_DATE[=]' => date("Y-m-d") ]) == 0 )

            //若今天没有新纪录则新增记录
            $this->DB->insert("T_RECORD",[
                'RECORD_DATE' => date("Y-m-d")
            ]);


        $history = $this->DB->select("T_RECORD_MAIN", "*");
        $today = $this->DB->select("T_RECORD", "*", [ 'RECORD_DATE[=]' => date("Y-m-d")] );

        $result = ["history" => $history, 'today' => $today];
        send_content_json(1, "获得数据成功", $result);

    }

    /**
     * @param  date 日期：格式为 2020-08-08
     * 获取某一天的销售数据
     */
    public function getDataByDate(){

        $result = $this->DB->select("T_RECORD" , "*", [ 'RECORD_DATE' => date('Y-m-d')]);
        if( count($result) == 1 )
            send_content_json(1, "获取".$this->PARAM['date']."数据成功", $result);
        else
            send_content_json(0, $this->PARAM['date']."无数据", $result);
    }

    /**
     * @param day 间隔日期: 7
     * 获取从今天往前 day 天的数据
     */
    public function getDataForward(){

        if( $this->DB->count('T_RECORD') == 0 )

            //若今天没有新纪录则新增记录
            $this->DB->insert("T_RECORD",[
                'RECORD_DATE' => date("Y-m-d")
            ]);

        $sql_str = "SELECT * FROM T_RECORD where DATE_SUB(CURRENT_DATE(), INTERVAL ".$this->PARAM['day']." DAY) <= date(RECORD_DATE)";
        $result = $this->DB->query( $sql_str )->fetchAll();

    }

    /**
     * 获取店铺详情
     *
     * @param id 店铺ID
     */
    public function getStoreInfo(){

        $result = $this->DB->select('T_STORE_ADMIN', '*', ['STORE_ID[=]' => $this->PARAM['id']]);
        send_content_json(1, "获取店铺管理员列表成功", $result);
    }

    /**
     * 获取商品排行
     */
    public function getProductRanking(){

        $result = $this->DB->select('T_PRODUCT', '*', [
            "LIMIT" => [0, $this->PARAM['num']],
            "ORDER" => [
                "SALE" => "DESC"
            ]
        ]);

        send_content_json(1, "获取商品销量排行成功", $result);
    }

}
