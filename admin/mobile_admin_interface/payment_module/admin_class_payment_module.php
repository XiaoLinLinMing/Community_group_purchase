<?php


class admin_class_payment_module
{

    private $DB, $PARAM;

    public function __construct($PARAM)
    {
        $this->DB = DbOption();
        $this->PARAM = $PARAM;
    }

    /**
     * 获取退款订单
     *
     * @param type 订单类型 全部(all) 异地(yd) 门店(md) 同城(tc)
     *
     * @return
     */
    public function getRefundOrder(){

        //self:$this;
        $result = [];

        $where = ['STATUS[=]' => 'NO'];
        switch ($this->PARAM['type']){

            case 'all'://获取所有退款订单


                break;

            case 'yd'://获取异地退款订单

                $where[ 'ORDER_ID[~]'] = "BB-YD_";
                break;

            case 'md'://获取门店退款订单
                $where[ 'ORDER_ID[~]'] = "BB-MD_";
                break;
            case 'tc'://获取门店退款订单
                $where[ 'ORDER_ID[~]'] = "BB-TC_";
                break;
        }

        $result = $this->DB->select("T_PAYMENT_RECORD", "*", $where);

        $result_count = $this->DB->count("T_PAYMENT_RECORD", "*", $where);

       if( count($result) == 0) send_content_json(0, "没有退款订单", []);
       else send_result(1, "获取退款订单成功", $result_count, $result);
    }


}
