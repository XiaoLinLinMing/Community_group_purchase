<?php


function send_content_text($code_no, $return_tip, $return_text){

    /*
     * code_no : SUCCESS/FAILURE
     * return_tip :"错误提示/成功提示"
     * return_content:json
     * */
    $return_json = array('code_no' =>$code_no, 'return_tip' =>$return_tip, 'data' =>$return_text);
    echo json_encode($return_json,JSON_UNESCAPED_UNICODE);
}

function send_content_json($code_no, $return_tip, $return_array){

    $return_json = array('code_no' =>$code_no, 'return_tip' =>$return_tip, 'data' =>$return_array);
    echo json_encode($return_json,JSON_UNESCAPED_UNICODE);
}

//发送带有结果行数的方法
function send_result($code_no, $return_tip, $query_count, $return_array){


    $return_json = array('code_no' =>$code_no,
        'return_tip' =>$return_tip,
        'data' =>
            [
                'count' => $query_count,
                'result' => $return_array
            ]
    );

    echo json_encode($return_json,JSON_UNESCAPED_UNICODE);
}

//base64 转图片
function to_ptc($ptc_base64){

    preg_match('/^(data:\s*image\/(\w+);base64,)/', $ptc_base64, $result);
    return base64_decode(str_replace($result[1], '', $ptc_base64));
}


function xml_to_array($xml){

    if(!$xml){
        return false;
    }

    //将XML转为array
    //禁止引用外部xml实体
    libxml_disable_entity_loader(true);
    $data = json_decode(
        json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA))
        ,true
    );

    return $data;
}

/*
 * 阻止恶意登录爆破 同一个IP只允许错误5次
 * */
function prevent_polling(){


    //session_destroy();
    if( !isset($_SESSION[$_SERVER['REMOTE_ADDR']]) ){

        //若登录失败计数器不存在则创建
        $_SESSION[$_SERVER['REMOTE_ADDR']] = 0;

        return true;
    }
    elseif ($_SESSION[$_SERVER['REMOTE_ADDR']] == 5){

        send_content_json("FAILURE", "xxxxx", "fuck off!");
        return false;
    }else {
        $_SESSION[$_SERVER['REMOTE_ADDR']] = $_SESSION[$_SERVER['REMOTE_ADDR']] + 1;

    }

}


/*
 * 校验口令
 * */
function token_test($token){

    if( $token !== $_SESSION['token']){

        send_content_json("FAILURE", "口令有误", []);
        return false;
    }

    else
        return true;
}

/**
 * 打印错误日志
 */
function print_err_log($logType, $str){

    $file=null;
    switch ($logType){
        case "ISS":
            $file = fopen(Root_Path . "private/log/iss.log", "a");
            break;
        case "SF":break;

    }

    fwrite($file, "\n". "时间：".date('Y-m-d h:i:s', time())."----".$str);
}
