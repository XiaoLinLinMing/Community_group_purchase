/*订单号约定

    BB（代表饼饼商城）
    MD(代表门店)
    YD(代表异地订单)
    TC(代表同城订单)
    数字为订单下单时的时间戳

    例如:BB-MD1234587456，代表饼饼商城的门店订单

*/


CREATE DATABASE D_BINGBING_SHOP;

CREATE TABLE T_PRODUCT (
    ID VARCHAR(20)/*商品ID*/,
    NAME VARCHAR(60) DEFAULT 'ERR商品'/*商品名称*/,
    OP FLOAT(7, 2) DEFAULT 999/*商品原价*/,
    RP FLOAT(7, 2) DEFAULT 999/*商品现价 或单买价*/,
    SALE INT DEFAULT 0/*销量*/,
    UP_DOWN TINYINT DEFAULT 0/*是否下架 1下架 0没下架*/,
    STOCK INT/*库存*/,
    INFO VARCHAR(500)/*商品描述*/,
    REC TINYINT/*是否首页推荐 1是 0否*/,
    COV_URL VARCHAR(200)/*封面URL*/,
    CLASSIFY VARCHAR(30)/*产品类别*/,
    ASSEMBLE VARCHAR(10) DEFAULT 'NO'/*是否开启团购 YES(开启) NO(不开启) 默认不团购*/,
    ASSEMBLE_SUM INT DEFAULT 0/*拼团人数 不开启拼团默认为0*/,
    ASSEMBLE_PRICE FLOAT(12, 2) DEFAULT 999/*拼团价格*/,
    ASSEMBLE_STOP_TIME DATETIME/*拼团截止日期*/,
    SECKILL VARCHAR(10) DEFAULT 'NO'/*是否开启秒杀 YES(开启) NO(不开启) 默认不秒杀*/,
    SECKILL_STOP_TIME DATETIME/*秒杀截止日期*/,
    SECKILL_PRICE FLOAT(12, 2) DEFAULT 999/*秒杀价*/
);

/*产品类别表*/
CREATE TABLE T_PRODUCT_CLASSIFY(
    ID VARCHAR(30)/*类别ID*/,
    NAME VARCHAR(50)/*类别名*/
);

/*商品图片表*/
CREATE TABLE T_PRODUCT_IMG(
    IMG_ID VARCHAR(20)/*图片ID*/,
    PRODUCT_ID VARCHAR(20)/*所属产品ID*/
);

/*用户表 用于数据收集*/
CREATE TABLE T_CUSTOMERS(
    ID VARCHAR(100) /*小程序用户标识*/,
    NAME VARCHAR(50)/*顾客名称*/,
    GENDER VARCHAR(5)/*顾客性别*/,
    TEL VARCHAR(20)/*联系电话*/,
    SHOP_COUNT INT/*本店购买次数*/,
    COLLECT INT/*用户收藏商品个数*/,
    TOTAL FLOAT(12,2)/*用户消费总额*/,
    PROVINCE VARCHAR(20)/*用户所在省*/,
    CITY VARCHAR(30)/*用户所在市*/,
    COUNTY VARCHAR(50)/*用户所在县/区*/,
    ADDRESS VARCHAR(100)/*用户详细地址*/
);

/*订单表 不包含门店订单 2020年3月14日01:29:46 增加WAYBILL_NO默认值NOTHING 未更新*/
CREATE TABLE T_ORDER_MAIN(
    ID VARCHAR(30)/*订单ID 主键 一般为时间戳*/,
    CLIENT_ID VARCHAR(50)/*客户唯一标示*/,
    NAME VARCHAR(50)/*下单收件人名称*/,
    TEL VARCHAR(20)/*联系电话*/,
    PROVINCE VARCHAR(20)/*收件地址 省*/,
    CITY VARCHAR(20)/*收件地址 城市*/,
    COUNTY VARCHAR(50)/*收件地址 县区*/,
    ADDRESS VARCHAR(150)/*详细地址*/,
    COMMODITY_PRICE FLOAT(10, 2)/*商品总价*/,
    FREIGHT FLOAT(6, 2)/*运费*/,
    TOTAL FLOAT(10,2)/*订单总价 (运费+商品总价)*/,
    CREATE_TIME DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP/*下单时间*/,
    PAYMENT_STATUS VARCHAR(10)/*支付状态 UNPAID(未支付) PAID(已支付) REFUNDED(已退款) */,
    TYPE VARCHAR(10)/*订单类型 TC(同城) YD(异地) */,
    WAYBILL_NO VARCHAR(100) DEFAULT 'NOTHING'/*运单号*/
);

/*订单表 同城订单扩展 2020年3月16日12:36:03 更新未修改*/
CREATE TABLE T_ORDER_TC(
    MAIN_ID VARCHAR(30)/*外键 ID*/,
    LNG VARCHAR(10)/*经度*/,
    LAT VARCHAR(10)/*纬度*/,
    COMPANY VARCHAR(100)/*收件地址中的房名（某某幼儿园、某某饭店等）*/,
    DC_NAME VARCHAR(20)/*配送员姓名*/,
    DC_TEL VARCHAR(20)/*配送员联系方式*/,
    STATUS VARCHAR(10),/*配送状态 UNSHIPPED(待发货) DELIVERED(已送达) CANCEL(订单取消) ON_DELIVER(配送中) WAIT_DELIVER(等待配送员接单) ON_WAY(配送员正在赶过来)*/
    PICK_UP_CODE VARCHAR(50)/*取件码*/,
    CAN_SEE TINYINT DEFAULT 0/*是否已读 1（是） 0（否）*/
);

/*订单表 异地订单扩展*/
CREATE TABLE T_ORDER_YD(
    MAIN_ID VARCHAR(30)/*外键 ID*/,
    STATUS VARCHAR(10)/*DELIVERED(已送达) ON_DELIVER(未发货) ON_WAY(运输中)*/
);

/*订单表 门店订单 2020年3月14日01:43:54 增加PAYMENT_STATUS退款表示 未更新*/
CREATE TABLE T_ORDER_MD(
    ID VARCHAR(30)/*订单ID 主键 一般为时间戳*/,
    CLIENT_NAME VARCHAR(30)/*顾客姓名*/,
    TOTAL FLOAT(6, 2)/*订单总价*/,
    CREATE_TIME DATETIME/*下单时间*/,
    TAKE_TIME DATETIME/*取件时间*/,
    STATUS VARCHAR(10) DEFAULT 'NOTHING'/*支付状态 REFUNDED(退款) UNPAID(未支付) PAID(已支付) WAIT_PICK_UP(待取餐) TAKEN_MEALS(已派餐)*/,
    CLIENT_ID VARCHAR(50)/*用户唯一标示*/,
    MD_NAME VARCHAR(10)/*门店名称*/,
    CAN_SEE TINYINT DEFAULT 0/*是否已读 1（是） 0（否）*/
);


/*用户收藏表*/
CREATE TABLE T_COLLECT(
    PRODUCT_ID VARCHAR(20)/*收藏的商品ID*/,
    CUSTOMER_ID VARCHAR(30)/*所属用户ID*/
);

/*门店列表*/
CREATE TABLE T_STORE_LIST(
    STORE_ID VARCHAR(20),/*门店编码*/
    STORE_NAME VARCHAR(50)/*门店名称*/,
    SALE_TOTAL FLOAT(12, 2) DEFAULT 0.0/*门店总销售额*/,
    ORDER_SUM INT DEFAULT 0/*门店订单总数*/
);

/*设置信息表*/
CREATE TABLE T_SETTING(
    SETTING_NAME VARCHAR(50)/*设置名*/,
    VALUE4 VARCHAR(50)/*设置值*/
);

/*销售信息总表*/
CREATE TABLE T_RECORD_MAIN(
    SALE_TOTAL FLOAT(12,2)/*销售总额*/,
    ORDER_TOTAL INT/*订单总数*/,
    CLIENT_TOTAL INT/*用户总数*/,
    MD_ORDER_TOTAL INT/*门店订单总数*/,
    TC_ORDER_TOTAL INT/*同城订单总数*/,
    YD_ORDER_TOTAL INT/*异地订单总数*/,
    REFUND_ORDER_TOTAL INT/*总退单数*/,
    REFUND_TOTAL FLOAT(12, 2)/*退款总额*/
);

/*销售信息表*/
CREATE TABLE T_RECORD(
    RECORD_DATE DATETIME DEFAULT '2025-1-1',/*记录日期 2019-10-1*/
    TOTAL_SALES FLOAT(15,2) DEFAULT 0.0,/*销售总额*/
    TURNOVER_NUMBER INT DEFAULT 0,/*成交笔数*/
    VISITOR_NUMBER INT DEFAULT 0,/*访客数*/
    NEW_USER INT DEFAULT 0/*新用户数*/,
    YD_O_NUM INT DEFAULT 0/*异地订单数*/,
    TC_O_NUM INT DEFAULT 0/*同城订单数*/,
    WY_O_NUM INT DEFAULT 0/*吾悦门店订单数*/,
    TL_O_NUM INT DEFAULT 0/*泰龙城门店订单数*/,
    YD_SALES_VOLUME FLOAT(10, 2) DEFAULT 0.0/*异地订单销售额*/,
    TC_SALES_VOLUME FLOAT(10, 2) DEFAULT 0.0/*同城订单销售额*/,
    WY_SALES_VOLUME FLOAT(10, 2) DEFAULT 0.0/*吾悦订单销售额*/,
    TL_SALES_VOLUME FLOAT(10, 2) DEFAULT 0.0/*泰龙订单销售额*/,
    REFUND_TOTAL FLOAT(12, 2)/*退款总额*/
);

/*订单商品表*/
CREATE TABLE T_ORDER_COMMODITY(
    PRODUCT_ID VARCHAR(20)/*商品ID*/,
    NAME VARCHAR(60)/*商品名称*/,
    PRICE FLOAT(6, 2)/*商品被购买时的现价*/,
    QTY INT/*购买的数量*/,
    ORDER_ID VARCHAR(30)/*商品所属订单ID*/
);

/*管理员信息表*/
CREATE TABLE T_ADMIN(
    ACCOUNT VARCHAR(30)/*账号*/,
    PASSWORD VARCHAR(100)/*密码*/,
    LEVEL VARCHAR(10)/*账号级别 TOP(超级管理员) LESS(员工)*/,
    STORE_NAME VARCHAR(200)/*所属店铺*/
);

CREATE TABLE T_COMMENT(
    CLIENT_ID VARCHAR(30)/*评论者ID*/,
    COMmENT_INFO VARCHAR(200)/*评论内容*/,
    CREATE_TIME DATETIME/*评论时间*/,
    STAR INT/*评论星级 1-5星*/,
    EXAMINATION TINYINT/*评论是否过审 默认否 开启自由评论模式则无需审查*/
);

/*微信支付订单记录*/
CREATE TABLE T_PAYMENT_RECORD(
    ORDER_ID VARCHAR(30),/*支付的商户内部订单号*/
    WECHAT_PAYMENT_ID VARCHAR(100),/*微信支付订单号*/
    STATUS VARCHAR(30),/*支付状态 （OK 已支付） （NO 已退款）*/
    PAYMENT_DATE VARCHAR(50)/*支付时间 2020年3月13日12:43:31*/
);

/*新订单提醒*/
CREATE TABLE T_NEW_NOTICE(
    MD VARCHAR(50)/*门店名称*/,
    NOTICE TINYINT/*是否有更新 1(是) 0(否)*/
);

/*店铺管理员*/
CREATE TABLE T_STORE_ADMIN(
    ID VARCHAR(100),/*管理员ID*/
    NAME VARCHAR(100),/*管理员姓名*/
    ACCOUNT VARCHAR(100),/*管理员账号*/
    PASSWORD VARCHAR(200)/*管理员密码*/,
    STORE_ID VARCHAR(100)/*管理员所属店铺ID*/
);
