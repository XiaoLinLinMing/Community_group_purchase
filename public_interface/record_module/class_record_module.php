<?php


class class_record_module
{

    private $DB, $PARAM;

    public function __construct($PARAM)
    {
        $this->DB = DbOption();
        $this->PARAM = $PARAM;
    }

    /**
     * 获取今日数据
     */
    public function getTodayData(){

        $result = $this->DB->select("T_RECORD", "*", [ "RECORD_DATE[=]" => date("Y-m-d") ]);

        if ( count($result) == 0){

            $this->DB->insert("T_RECORD");//添加新记录 初始化
            $result = $this->DB->select("T_RECORD", "*", [ "RECORD_DATE[=]" => date("Y-m-d") ]);
            send_content_json(5474, "暂无数据", $result);
        }

        else
            send_content_json(1, "今日数据获取成功", $result);

    }

    /**
     * 获取指定日期间隔的销售数据
     * @param  interval 间隔天数，也就是几天前
     */
    public function getDataByInterval(){

        $sql_str = "SELECT * FROM T_RECORD WHERE DATE_SUB(CURRENT_DATE(), INTERVAL ".$this->PARAM['interval']." DAY) <= date(RECORD_DATE)";
        $result = $this->DB->query($sql_str)->fetchAll();

        if( count($result) == 0 ){

            //说明今天没有数据
            $this->DB->insert("T_RECORD",[]);//添加新记录 初始化
            $result = $this->DB->select("T_RECORD", "*", [ "RECORD_DATE[=]" => date("Y-m-d") ]);
            send_content_json(5474, "暂无数据", $result);
        }
        else
            send_content_json(1, "指定日期间隔数据获取成功", $result);
    }

    /**
     * 获取指定日期的销售数据
     * @param  date 日期 格式：2020-3-12
     */
    public function getDataByDate(){

        $result = $this->DB->select("T_RECORD", "*", [ "RECORD_DATE[=]" => $this->PARAM['date'] ]);

        if( count($result) == 0)
            send_content_json(0, "查询日期不存在或没有数据", $result);
        else
            send_content_json(1, "指定日期数据查询成功", $result);
    }

    /**
     * 按照字段来增加值
     * @param field 需要增加的字段
     * @param value 增加的值
     * @param date 数据的日期
     */
    public function addDataByField(){

        if( $this->PARAM['field'] == 'RECORD_DATE' )
            send_content_json(5479, "参数错误", []);



        else{

            $old_data =  $this->DB->select(
                "T_RECORD",
                $this->PARAM['field'],
                [ "RECORD_DATE[=]" => $this->PARAM['date'] ]
            )[$this->PARAM['field']];

            $data = ["field" => $this->PARAM['field'], "old" => $old_data];

            $this->DB->update("T_RECORD", [ $this->PARAM['field']."[+]" , $this->PARAM['value']]);

            $new_data =  $this->DB->select(
                "T_RECORD",
                $this->PARAM['field'],
                [ "RECORD_DATE[=]" => $this->PARAM['date'] ]
            )[$this->PARAM['field']];

            $data[ "new_data"] = $new_data;

            send_content_json(1, "修改成功", $data);
        }

    }


}
