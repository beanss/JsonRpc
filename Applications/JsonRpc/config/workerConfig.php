<?php
/**
 * Created by PhpStorm.
 * User: Dou
 * Date: 2016/5/11
 * Time: 13:52
 */
/*
;Rpc服务
;进程入口文件
worker_file = ../init.php
;监听的端口
listen = tcp://0.0.0.0:2022
;这里设置成短连接
persistent_connection = 0
;启动多少worker进程
start_workers=10
;接收多少请求后退出
max_requests=1000
;以哪个用户运行该worker进程，应该使用权限较低的用户
user=root
;socket有数据可读的时候预读长度，一般设置为应用层协议包头的长度
preread_length=84000


;统计数据上报地址，即StatisticWorker.conf配置的地址
statistic_address = udp://127.0.0.1:55656
*/
return array(
    'listen' => 'Json://0.0.0.0:2015',    // 监听端口
    'start_workers' => 10,                  // 启动worker进程数
    'name' => 'JsonRpc',                    // worker 名称
    'statistic_address' => 'udp://127.0.0.1:55656', // 统计数据上报地址，即StatisticWorker.conf配置的地址
);