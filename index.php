<?php
/**
 * 请求统一使用JSON格式
 *
 * 一共有两种请求方式：POST、 GET
 * 该文件为请求统一入口
 */

/*设置跨与访问*/
header('Content-Type: text/html;charset=utf-8');
header('Access-Control-Allow-Origin:*');
header('Access-Control-Allow-Headers:x-requested-with,content-type');


/*导入配置文件*/
$config = file_get_contents("private\config\config.xml");

$config = json_decode(
    json_encode(
        simplexml_load_string($config, 'SimpleXMLElement', LIBXML_NOCDATA)
    ),
    true
);


global $config;

//引入基准路径
include("_DIR_.php");

//数据库操作模块
require(Root_Path."\common\db\Medoo-1.1.2\medoo.php");

//数据库操作模块
require(Root_Path."\common\db\db.php");

//请求数据返回格式化
require(Root_Path."\common\data_formatting.php");

//商品模块
require(Root_Path."\public_interface\product_module\class_product_module.php");

//订单模块
require(Root_Path."\public_interface\order_module\class_order_module.php");

//微信支付模块
require (Root_Path. "\private\private_interface\payment_module\class_payment_module.php");

//顺丰模块
require(Root_Path . "\public_interface\logistics_module\class_logistics_sf_module.php");

//同城闪送模块
require(Root_Path . "\public_interface\logistics_module\class_logistics_ss_module.php");

//数据记录模块
require(Root_Path . "/public_interface/record_module/class_record_module.php");

//后台消息通知模块
require(Root_Path . "/admin/mobile_admin_interface/notice_module/admin_class_notice_module.php");

//后台订单操作模块
require(Root_Path . "/admin/mobile_admin_interface/order_module/admin_class_order_module.php");

//后台支付订单操作模块
require(Root_Path . "/admin/mobile_admin_interface/payment_module/admin_class_payment_module.php");

//后台支付订单操作模块
require(Root_Path . "/admin/mobile_admin_interface/setting_module/admin_class_setting_module.php");

//后台支付订单操作模块
require(Root_Path . "/admin/mobile_admin_interface/page_module/admin_class_page_module.php");

//客户端页面初始化
require(Root_Path . "\public_interface\page_module\class_page_module.php");

/*
 *  请求串规定
 *  $controller_name 操作类名
 *  $model_name 操作方法名
 *  $param 参数
 *  $request_type 请求参数
 * */

try{
    session_start();

    if ( !empty($_POST) ){



        //注明该请求来自客户端还是管理端
        $request_type = $_POST['t'];
    }

    else if( !empty($_GET) ){

        if( empty($_GET['c']) || empty($_GET['f']) || empty($_GET['p']) || empty($_GET['t'])){
            echo "缺少参数！";return;
        }
        if($_GET['f'] === '' || $_GET['p'] === '' || $_GET['t'] === ''){echo "参数非法！";return;}

        $class_name = $_GET['c'];
        $function_name = $_GET['f'];
        $param = $_GET['p'];
        $request_type = $_GET['t'];
    }

    else {echo "参数为空！";return;}

    if($request_type === "management"){



        if( isset($_POST["token"]) ){

            if( empty($_POST['c']) || empty($_POST['f']) || empty($_POST['p']) || empty($_POST['t'])){
                echo "缺少参数！";return;
            }
            $class_name = $_POST['c'];
            $function_name = $_POST['f'];
            $param = $_POST['p'];
            //若请求中带有token则说明该管理员已经登陆过
            if( !isset($_SESSION['token'])){

                send_content_text("5477", "令牌失效", "relogin");
                //unset($_SESSION['token']);
                //prevent_polling();
                return;
            }

            elseif( $_POST["token"] !== $_SESSION['token']){

                send_content_text("5478", "令牌错误", []);
                //prevent_polling();
                return;
            }

        }
        else{

            //说明管理员需要重新登录或者第一次登录
            $account = $_POST['account'];
            $password = $_POST['password'];

            $res = DbOption()->select("T_ADMIN",
                [ "PASSWORD" ],
                [
                    "AND" =>[
                        "PASSWORD[=]" => $password,
                        "ACCOUNT[=]" => $account
                    ]
                ]
            );

            if( count($res) == 0) {

                send_content_json("FAILURE", "账户或密码错误", []);
                //prevent_polling();
                return;
            }
            else{

                //生成token返回
                $token = $_SESSION['token'] = md5($_SERVER['REMOTE_ADDR'] . time());

                send_content_text("SUCCESS", "token成功返回", $_SESSION['token']);
                return;
            }

        }

    }
    //若客户端请求带有admin类则说明权限错误
    elseif (strpos($class_name, "admin_") === 0) {echo "权限不足"; return;}


    $pos = strpos($class_name, "class_");
    if( ($pos!=0 || $pos===false) && $request_type == 'client') echo '非法参数';

    else {

        $param = json_decode($param, true);
        eval('$C = new '.$class_name.'($param);');
        eval('$C->'.$function_name.'();');
    }
}

catch (Exception $e){

    send_content_json(0, "未知错误", []);
}
