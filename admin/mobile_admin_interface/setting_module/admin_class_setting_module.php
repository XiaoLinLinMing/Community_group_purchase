<?php


class admin_class_setting_module
{

    private $DB, $PARAM;

    public function __construct($PARAM)
    {
        $this->DB = DbOption();
        $this->PARAM = $PARAM;
    }

    /**
     * 按设置名设置相应的值
     */
    public function setting(){

        $this->DB->update("T_SETTING",["value" => $this->PARAM['value'] ], [ "NAME[=]" => $this->PARAM['setting_name'] ] );

    }

    /**
     * 获取设置值
     */
    public function getSetting(){


        send_content_json(1, "设置值获取成功", $this->DB->select( "T_SETTING", "*", []));
    }

    /**
     * @param md_name 门店名称
     * 添加门店
     */
    public function addStore(){

        //添加门店列表
        $store_id = strval(time());
        $this->DB->insert(
            "T_STORE_LIST",
            [
                'STORE_ID' => $store_id,
                'STORE_NAME' => $this->PARAM['md_name']
            ]
        );


        $result = $this->DB->select("T_STORE_LIST", "*", []);

        //添加店铺管理员
        foreach ($this->PARAM['store_admin'] as $index => $item){

            $this->DB->insert('T_STORE_ADMIN', [
                'ID' => strval(time()),
                'NAME' => $item['clerk_name'],
                'ACCOUNT' => $item['account'],
                "PASSWORD" => $item['password'],
                'STORE_ID' => $store_id
            ]);
        }

        send_content_json(1, "添加门店成功", $result);
    }

    /**
     * 编辑门店信息
     *
     * md_id 被修改的门店ID
     * md_name 门店名称
     * del_admin:[ {clerk_id:’‘} ]
     * add_admin:[ {account:'', password:'', clerk_name:''} ]
     * update_admin:[{account:'', password:'', clerk_name:'', id:''}]
     */
    public function editStore(){

        $this->DB->update('T_STORE_LIST',['STORE_NAME' => $this->PARAM['md_name']],['STORE_ID[=]' => $this->PARAM['md_id']]);

        foreach ($this->PARAM['del_admin'] as $index => $item)
            $this->DB->delete('T_STORE_ADMIN', ['ID' => $item['clerk_id']]);

        //添加店铺管理员
        foreach ($this->PARAM['add_admin'] as $index => $item)

            $this->DB->insert('T_STORE_ADMIN', [
                'ID' => strval(time()),
                'NAME' => $item['clerk_name'],
                'ACCOUNT' => $item['account'],
                "PASSWORD" => $item['password'],
                'STORE_ID' => $this->PARAM['md_id']
            ]);

        //更改店铺管理员
        foreach ($this->PARAM['update_admin'] as $index => $item)

            $this->DB->update(
                'T_STORE_ADMIN',
                [
                    'NAME' => $item['clerk_name'],
                    'ACCOUNT' => $item['account'],
                    "PASSWORD" => $item['password']
                ],
                [
                    'ID[=]' => $item['id']
                ]
            );

        send_content_json(1, "编辑门店成功", $this->PARAM['md_id']);
    }

    /**
     * 删除店铺管理员
     */
    public function delAdmin(){

        $this->DB->delete('T_STORE_ADMIN', ['ID[=]' => $this->PARAM['clerk_id']]);
        send_content_json(1, "删除店铺管理员成功", $this->PARAM['clerk_id']);
    }

    /**
     * 删除店铺
     */
    public function delStore(){

        $this->DB->delete('T_STORE_LIST', ['STORE_ID[=]' => $this->PARAM['md_id']]);
        $this->DB->delete('T_STORE_ADMIN', ['STORE_ID[=]' => $this->PARAM['md_id']]);
        send_content_json(1, "删除店铺成功", $this->PARAM['md_id']);

    }
}
