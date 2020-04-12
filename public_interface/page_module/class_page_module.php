<?php


class class_page_module
{
    private $DB, $PARAM;

    public function __construct($PARAM)
    {
        $this->DB = DbOption();
        $this->PARAM = $PARAM;
    }

    /**
     * 获取门店列表
     */
    public function getStoreList(){

        $result = $this->DB->select("T_STORE_LIST", "*", []);
        send_content_json(1, "门店列表获取成功", $result);
    }

    /**
     * @param client_id 小程序用户唯一标识
     *
     * 添加新访客 2020-3-20 20:37:18
     */
    public function addClientCount(){

        //查询用户是否为新用户 1旧用户  0新用户
        $new_user = $this->DB->count('T_CUSTOMERS' , ['ID[=]' => $this->PARAM['client_id']]);

        if($this->DB->count('T_RECORD') == 0){


            //若今天没有新纪录则新增记录
            $this->DB->insert("T_RECORD",[
                'RECORD_DATE' => date("Y-m-d"),
                'VISITOR_NUMBER' => 1,
                'NEW_USER' => $new_user
            ]);
        }
        else{

            $this->DB->update(
                "T_RECORD",
                [
                    'VISITOR_NUMBER[+]' => 1,
                    'NEW_USER[+]' => $new_user
                ],
                [
                    'RECORD_DATE[=]' => date("Y-m-d")
                ]
            );
        }

        if($new_user == 0)
            //若为新用户 则总用户数+1
            $this->DB->update("T_RECORD_MAIN", ['CLIENT_TOTAL[+]' => 1]);

    }


}
