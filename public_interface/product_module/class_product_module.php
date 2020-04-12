<?php

class class_product_module{

    private $DB, $PARAM;

    public function __construct($PARAM)
    {
        $this->DB = DbOption();
        $this->PARAM = $PARAM;
    }

    /**
     * 获取商品列表（客户端接口）
     *
     */
    public function getProductListClient(){

        $timestamp = strval(time()); //添加到图片末尾 防止客户端无法及时更新图片 .jpg?17984
        $classify = $this->PARAM['CLASSIFY'];
        $row_n = $this->PARAM['ROW_N'];
        $page_n = $this->PARAM['PAGE_N'];
        $sort = $this->PARAM['SORT'];
        $sort_field = $this->PARAM['SORT_FIELD'];

        $start_index = ($page_n-1) * $row_n;

        //检查排序字段是否合法
        if($sort_field != "RP" && $sort_field!="SALE" && $sort_field!="STOCK" ){

            send_content_text("FAILURE", "参数非法", "排序字段非法");
            return;
        }

        $and = [

            "UP_DOWN[=]" => 0
        ];

        //如果类别不为加载所有商品的话
        if($this->PARAM['CLASSIFY'] != "*")
            $and["CLASSIFY[=]"] = $classify;



        //判断是否有附加条件
        if( isset($this->PARAM["ADDITIONAL"]) ){

            switch ($this->PARAM["ADDITIONAL"]){
                case 'SEC':
                    $and['SECKILL[=]'] = 'YES';
                    break;
                case 'ASSEMBLE':
                    $and['ASSEMBLE[=]'] = 'YES';
                    break;
            }
        }
        //加载指定类别的商品 （已上架的商品）
        $sql_result = $this->DB->select(
            "T_PRODUCT",
            "*",
            [
                "LIMIT" => [$start_index, $row_n],
                "ORDER" => [
                    $sort_field => $sort
                ],
                'AND' => $and
            ]
        );

        $sql_count = $this->DB->count("T_PRODUCT",['AND' => $and]);

        //添加商品详情图片列表
        foreach ( $sql_result as $index => $item){

            $info_ptc_list = $this->DB->select("T_PRODUCT_IMG", "IMG_ID", [ "PRODUCT_ID"=> $item['ID']]);

            $ptc_list = array();
            foreach ( $info_ptc_list as $item_index => $item_value){


                $ptc_list[$item_index] = [
                    'IMG_ID' => $item_value,
                    'URL' => 'http://'.$_SERVER['HTTP_HOST'] . $GLOBALS["config"]["DIR"]["GOODS_INFO_PTC_DIR"].$item_value.$GLOBALS["config"]["DIR"]["GOODS_IMG_SUFFIX_NAME"].'?'. $timestamp

                ];

            }
            $sql_result[$index]["INFO_PTC_LIST"] = $ptc_list;
            $sql_result[$index]["COV_URL"] .= '?'.$timestamp;
        }

        $request_result = array( "count"=>$sql_count, "goods"=>$sql_result );

        send_content_text("SUCCESS", "商品数据获取成功", $request_result);
    }

    /**
     * 获取商品列表 （管理端）
     */
    public function getProductListAdmin(){

        $timestamp = strval(time()); //添加到图片末尾 防止客户端无法及时更新图片 .jpg?17984
        $classify = $this->PARAM['CLASSIFY'];
        $row_n = $this->PARAM['ROW_N'];
        $page_n = $this->PARAM['PAGE_N'];
        $sort = $this->PARAM['SORT'];
        $sort_field = $this->PARAM['SORT_FIELD'];
        $start_index = ($page_n-1) * $row_n;

        //检查排序字段是否合法
        if($sort_field != "RP" && $sort_field!="SALE" && $sort_field!="STOCK" )
            send_content_text("FAILURE", "参数非法", "排序字段非法");

        $where_array = [

            "LIMIT" => [$start_index, $row_n],
            "ORDER" => [
                $sort_field => $sort
            ]

        ];

        //加载类型不为全部商品
        if( $classify!="ALL" )
            $where_array['AND'] = [
                "ClASSIFY[=]" => $classify,
                "UP_DOWN[=]" => 0
            ];

        //加载类型为已下架
        if($classify == "DOWN")
            $where_array['AND'] = [
                "UP_DOWN[=]" => 1
            ];

        if( $classify=="ALL")
            $where_array['AND'] = [
                "UP_DOWN[=]" => 0
            ];


        $sql_result = $this->DB->select("T_PRODUCT", "*", $where_array);
        $sql_count = $this->DB->count("T_PRODUCT",$where_array);

        //添加商品详情图片列表
        foreach ( $sql_result as $index => $item){

            $info_ptc_list = $this->DB->select("T_PRODUCT_IMG", ["IMG_ID"], [ "PRODUCT_ID"=> $item['ID']]);

            $ptc_list = array();
            foreach ( $info_ptc_list as $item_index => $img_id)
                $ptc_list[$item_index] = [
                'IMG_ID' => $img_id,
                'URL' => $GLOBALS["config"]["GOODS_INFO_PTC_DIR"]. $img_id .$GLOBALS["config"]["GOODS_IMG_SUFFIX_NAME"].'?'.$timestamp

            ];

            $sql_result[$index]["INFO_PTC_LIST"] = $ptc_list;
            //封面图也添加时间戳
            $sql_result[$index]["COV_URL"] .= '?'.$timestamp;
        }

        $request_result = array( "count"=>$sql_count, "goods"=>$sql_result );
        send_content_text("SUCCESS", "商品数据获取成功", $request_result);
    }

    /*
     * @info_ptc_list list 商品详情图列表 ['base64', 'base64']
     * @name string 商品标题
     * @origin_price float 商品原价
     * @recent_price float 商品现价
     * @classify string 商品类别
     * @stock int 商品库存
     * @info 商品文字详情
     * @rec 是否首页推荐
     * @assemble 是否开启拼团 YES(开启) NO(不开启)
     * @assemble_sum 拼团人数 开启拼团时有效
     * @assemble_top_time 拼团截止日期 开启拼团时有效
     * @assemble_price 拼团价格 开启拼团时有效
     * @seckill 是否开启限时折扣 YES(开启) NO(不开启)
     * @seckill_stop_time 限时折扣截止日期 开启限时折扣时有效
     * @seckill_price 限时折扣价格 开启限时折扣时有效
     * @cover 封面图base64
     *
     * @c  class_product_module
     * @f addProduct
     * @t management
     * @token token
     * 添加商品接口 （管理端）
     *
     * */
    public function addProduct(){

        if($this->PARAM['assemble'] == "YES" && $this->PARAM['seckill'] == "YES"){
            send_content_json(0, "团购和秒杀不能同时开启", []);
            return;
        }
        $product_id = 'P'.time();//封面id

        //生成封面图
        $cover_base64 = $this->PARAM["cover"];
        $cover_name =  $_SERVER['DOCUMENT_ROOT'] . $GLOBALS["config"]["DIR"]["GOODS_COVER_DIR"] .$product_id.$GLOBALS["config"]["DIR"]["GOODS_IMG_SUFFIX_NAME"];

        $ptc = fopen($cover_name, "w");
        fwrite($ptc, to_ptc($cover_base64));//生成封面图片图片
        fclose($ptc);

        $i = 0;

        //生成商品详情图
        foreach ( $this->PARAM["info_ptc_list"] as $ptc_base64){

            $info_ptc_name = $_SERVER['DOCUMENT_ROOT'] . $GLOBALS["config"]["DIR"]["GOODS_INFO_PTC_DIR"] . ($product_id . $i) . $GLOBALS["config"]["DIR"]["GOODS_IMG_SUFFIX_NAME"];
            $ptc = fopen($info_ptc_name, "w");
            fwrite($ptc, to_ptc($ptc_base64));
            fclose($ptc);

            //插入商品详情图片表
            $this->DB->insert("T_PRODUCT_IMG", [

                'IMG_ID' => ($product_id . $i),
                'PRODUCT_ID' => $product_id
            ]);
        }

        //插入商品数据

        if( $this->PARAM['assemble'] == "NO" && $this->PARAM['seckill'] == "NO")
            // 若不开启团购和限时折扣
            $this->DB->insert("T_PRODUCT", [
                "ID" => $product_id,
                "NAME" => $this->PARAM["name"],
                "OP" => $this->PARAM["origin_price"],
                "RP" => $this->PARAM["recent_price"],
                "CLASSIFY" => $this->PARAM["classify"],
                "STOCK" => $this->PARAM["stock"],
                "INFO" => $this->PARAM["info"],
                "REC" => $this->PARAM["rec"],
                "COV_URL" => 'http://'.$_SERVER['HTTP_HOST'].$GLOBALS["config"]["DIR"]["GOODS_COVER_DIR"].$product_id . ".jpg"
            ]);

        if($this->PARAM['assemble'] == "YES"){
            //若开启团购

            //插入商品数据
            $this->DB->insert("T_PRODUCT", [
                "ID" => $product_id,
                "NAME" => $this->PARAM["name"],
                "OP" => $this->PARAM["origin_price"],
                "RP" => $this->PARAM["recent_price"],
                "CLASSIFY" => $this->PARAM["classify"],
                "STOCK" => $this->PARAM["stock"],
                "INFO" => $this->PARAM["info"],
                "REC" => $this->PARAM["rec"],
                "COV_URL" => 'http://'.$_SERVER['HTTP_HOST'].$GLOBALS["config"]["DIR"]["GOODS_COVER_DIR"].$product_id . ".jpg",
                "ASSEMBLE" => $this->PARAM['assemble'],
                "ASSEMBLE_SUM" => $this->PARAM['assemble_sum'],
                "ASSEMBLE_PRICE" => floatval($this->PARAM['assemble_price']),
                "ASSEMBLE_STOP_TIME" => $this->PARAM['assemble_stop_time']
            ]);
        }

        if($this->PARAM['seckill'] == "YES"){
            //若开启团购

            //插入商品数据
            $this->DB->insert("T_PRODUCT", [
                "ID" => $product_id,
                "NAME" => $this->PARAM["name"],
                "OP" => $this->PARAM["origin_price"],
                "RP" => $this->PARAM["recent_price"],
                "CLASSIFY" => $this->PARAM["classify"],
                "STOCK" => $this->PARAM["stock"],
                "INFO" => $this->PARAM["info"],
                "REC" => $this->PARAM["rec"],
                "COV_URL" => 'http://'.$_SERVER['HTTP_HOST'].$GLOBALS["config"]["DIR"]["GOODS_COVER_DIR"].$product_id . ".jpg",
                "SECKILL" => $this->PARAM['seckill'],
                "SECKILL_STOP_TIME" => $this->PARAM['seckill_stop_time'],
                "SECKILL_PRICE" => floatval($this->PARAM['seckill_price'])
            ]);
        }

        send_content_json(1, "商品添加成功", ['product_id' => $product_id]);

    }

    /**
     * 删除商品
     */
    public function delProduct(){

        $del_id = $this->PARAM['id'];

        $sql_result = $this->DB->delete("T_PRODUCT", [ "ID[=]" => $del_id]);

        send_content_text("SUCCESS", "商品删除成功！id:" . $del_id, ["delete_result" => $sql_result]);
    }

    /*
     * 获取商品类别列表
     *
     * */
    public function getClassifyList(){

        $sql_result = $this->DB->select("T_PRODUCT_CLASSIFY", "*", []);

        send_content_json("SUCCESS", "获取商品类别列表成功", $sql_result);
    }

    /**
     * 添加商品类别
     */
    public function addProductClassify(){

        $sql_result = $this->DB->insert("T_PRODUCT_CLASSIFY",
            [
                "ID" => 'C'.time(),
                "NAME" => $this->PARAM["name"]
            ]
        );

        send_content_json("SUCCESS", "添加商品类别", ["code" => 'C'.time()]);
    }

    /**
     * 删除商品类别
     */
    public function delProductClassify(){


        $sql_result = $this->DB->delete("T_PRODUCT_CLASSIFY", [ "ID[=]" => $this->PARAM["id"]]);
        $this->DB->delete("T_PRODUCT", [ "CLASSIFY[=]" => $this->PARAM["name"]]);
        send_content_json("SUCCESS", "删除商品类别", ["code" => $sql_result]);
    }

    /**
     * 编辑商品类别
     */
    public function editClassify(){

        $this->DB->update("T_PRODUCT_CLASSIFY", ["NAME" => $this->PARAM["new_name"]], ['ID[=]' => $this->PARAM["id"]]);
        $sql_result = $this->DB->update(
            "T_PRODUCT",
            [
                "CLASSIFY" => $this->PARAM["new_name"]
            ],
            [
                'CLASSIFY[=]' => $this->PARAM["origin_name"]
            ]
        );
        send_content_json(1, "编辑商品类别成功", $sql_result);
    }


    /**
     * 收藏\取消收藏商品
     */
    public function colectProduct(){

        $product_id = $this->PARAM['product_id'];
        $client_id = $this->PARAM['client_id'];
        $sql_result = $this->DB->select("T_COLLECT", "PRODUCT_ID" ,[

            "PRODUCT_ID[=]" => $product_id,
            "CUSTOMER_ID[=]" => $client_id
        ]);

        if( count($sql_result) == 0 ){

            //说明该用户是收藏该商品
            $this->DB->select("T_COLLECT", [
                "PRODUCT_ID" => $product_id,
                "CUSTOMER_ID" => $client_id
            ]);
        }
        else
            //说明该用户要取消收藏商品
            $this->DB->delete("T_COLLECT" , [
                "PRODUCT_ID[=]" => $product_id,
                "CUSTOMER_ID[=]" => $client_id
            ]);

    }

     /**
      * 编辑商品
      *
      * id:"被编辑的商品ID",
      * info:{
             "name":this.product_title,
             "origin_price" : this.origin_price,
             "recent_price" : this.recent_price,
             "classify" : this.claasify[0],
             "stock" : this.stock,
             "info" : this.product_info,
             "rec" : 'NO',
             "assemble" :"NO",
             "assemble_sum":0,
             "assemble_stop_time" :"",
             "assemble_price" : 999,
             "seckill": "NO",
             "seckill_stop_time" :"",
             "seckill_price" : 999,
             "cover":this.cover[0],
             "info_ptc_list" :this.product_info_ptc
       },
      * cover:[
      *  "图片base64编码"
      * ],
      * info_ptc:[
      *     "被添加的base64图片编码"
      * ],
      * del_ptc:[
      *     "被删除的图片ID"
      * ]
      *
      *
      */

     public function editProduct(){

         try{

             //插入商品数据
             $product_id = $this->PARAM['id'];


             if($this->PARAM['info']['assemble'] == "YES" || true){
                 //若开启团购

                 //插入商品数据
                 $this->DB->update("T_PRODUCT", [
                     "NAME" => $this->PARAM['info']["name"],
                     "OP" => $this->PARAM['info']["origin_price"],
                     "RP" => $this->PARAM['info']["recent_price"],
                     "CLASSIFY" => $this->PARAM['info']["classify"],
                     "STOCK" => $this->PARAM['info']["stock"],
                     "INFO" => $this->PARAM['info']["info"],
                     "REC" => $this->PARAM['info']["rec"],
                     "ASSEMBLE" => $this->PARAM['info']['assemble'],
                     "ASSEMBLE_SUM" => $this->PARAM['info']['assemble_sum'],
                     "ASSEMBLE_PRICE" => floatval($this->PARAM['info']['assemble_price']),
                     "ASSEMBLE_STOP_TIME" => $this->PARAM['info']['assemble_stop_time'],
                     "SECKILL" => $this->PARAM['info']['seckill'],
                     "SECKILL_STOP_TIME" => $this->PARAM['info']['seckill_stop_time'],
                     "SECKILL_PRICE" => floatval($this->PARAM['info']['seckill_price'])
                 ],[

                     'ID[=]' => $product_id
                 ]);
             }



             //修改封面数据
             if( count($this->PARAM['cover']) != 0 ){

                 $cover_dir = $_SERVER['DOCUMENT_ROOT'] . $GLOBALS['config']['DIR']['GOODS_COVER_DIR'].$this->PARAM['id'].'.jpg';
                 unlink($cover_dir);
                 $new_cover = fopen($cover_dir, "w");
                 fwrite($new_cover, to_ptc($this->PARAM['cover'][0]));//生成封面图片图片
                 fclose($new_cover);

             }

             //修改详情图片
             if( count($this->PARAM['info_ptc']) != 0 ){

                 //详情图片ID 时间戳
                 $info_ptc_base_id = time(); $i = 0;
                 foreach($this->PARAM['info_ptc'] as $ptc_base64){

                     //向目录中添加图片
                     // /bingbing_shop/src/goods_info_ptc/1578797481.jpg
                     $info_ptc_dir = $_SERVER['DOCUMENT_ROOT'].$GLOBALS['config']['DIR']['GOODS_INFO_PTC_DIR']. strval($info_ptc_base_id+$i) .'.jpg';
                     $ptc_file = fopen($info_ptc_dir, "w");
                     fwrite($ptc_file, to_ptc($ptc_base64));
                     fclose($ptc_file);
                     $this->DB->insert("T_PRODUCT_IMG",[
                         "IMG_ID" => strval($info_ptc_base_id+$i),
                         "PRODUCT_ID" => $this->PARAM['id']
                     ]);
                     ++$i;
                 }

             }

             //删除图片
             if( count($this->PARAM['del_ptc']) != 0 ){

                 foreach( $this->PARAM['del_ptc'] as $del_id){
                     //若该ID不存在则不删除
                     if($this->DB->count("T_PRODUCT_IMG", [ "IMG_ID[=]" => $del_id]) == 0)continue;
                     $del_ptc_dir = $_SERVER['DOCUMENT_ROOT'] . $GLOBALS['config']['DIR']['GOODS_INFO_PTC_DIR']. $del_id .'.jpg';
                     unlink($del_ptc_dir);
                     $this->DB->delete("T_PRODUCT_IMG", [ "IMG_ID[=]" => $del_id]);
                 }
             }

            send_content_json(1, "编辑商品成功", $this->DB->select("T_PRODUCT", "*",[ "ID[=]" => $this->PARAM['id']]));
         }
        catch (Exception $e){

            $log_string = file_get_contents($GLOBALS['config']['DIR']['ERROR_LOG']);
            $log_decode = json_decode($log_string, true);

            $log_decode[ count($log_decode)]['error_msg'] = $e->getMessage();
            $log_decode[ count($log_decode)]['error_code'] = $e->getCode();
            $log_decode[ count($log_decode)]['error_time'] = date("h:i:sa");
            $log_decode[ count($log_decode)]['error_line'] = $e->getLine();
            $log_decode[ count($log_decode)]['error_file'] = $e->getFile();

            file_put_contents(file_get_contents($GLOBALS['config']['DIR']['ERROR_LOG']), json_encode($log_decode));
         }

     }

     /**
      * 商品下架
      */
     public function downProduct(){

         if(!token_test($this->PARAM['token'])) return;
         $this->DB->update("T_PRODUCT", ["UP_DOWN" => 1], ["ID[=]" => $this->PARAM['id']]);

     }

     /**
      * 商品上架
      */
     public function upProduct(){

         if(!token_test($this->PARAM['token'])) return;
         $this->DB->update("T_PRODUCT", ["UP_DOWN" => 0], ["ID[=]" => $this->PARAM['id']]);
     }

    /**
     * 获取商品分类列表
     */
    public function getClassify(){

        $result = $this->DB->select("T_PRODUCT_CLASSIFY", "*", []);

        send_content_json(1, "获取商品分类列表成功", $result);
    }

}
