<?php


function DbOption(){

    global $config;

    $Localhost = $config['DB']['HOST'];
    $UserName = $config['DB']['USERNAME'];
    $Password = $config['DB']['PASSWORD'];
    $DbName = $config['DB']['DBNAME'];
    $database = new Medoo([
        // required
        'database_type' => 'mysql',
        'database_name' => $DbName,
        'server' => $Localhost,
        'username' => $UserName,
        'password' => $Password,
        'charset' => 'utf8',
        'port' => 3306]);
    return $database;

}
