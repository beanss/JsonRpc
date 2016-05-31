<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link http://www.workerman.net/
 * @license http://www.opensource.org/licenses/mit-license.php MIT License
 */
use Workerman\Worker;

// 自动加载类
require_once __DIR__ . '/../../Workerman/Autoloader.php';
//require_once __DIR__ . '/Clients/StatisticClient.php';
// 初始化worker类
require_once __DIR__ . '/../../Workerman/InitWorker.php';
// service 配置文件
$configFile = __DIR__ . '/config/workerConfig.php';
if (file_exists($configFile)) {
    $config = include $configFile;
    \Workerman\InitWorker::run($config);
}


/*
require_once __DIR__ . '/Clients/StatisticClient.php';
*/
/*
// 开启的端口
$worker = new Worker('JsonNL://0.0.0.0:2016');
// 启动多少服务进程
$worker->count = 16;
// worker名称，php start.php status 时展示使用
$worker->name = 'TestRpc';


$worker->onMessage = function ($connection, $data)
{
    $statistic_address = 'udp://127.0.0.1:55656';
    // 判断数据是否正确
    if(empty($data['version']) || empty($data['service']) || empty($data['class']) || empty($data['method']) || !isset($data['param_array']))
    {
        // 发送数据给客户端，请求包错误
        return $connection->send(array('code'=>400, 'msg'=>'bad request', 'data'=>null));
    }

    // 鉴权


    // 获得要调用的类、方法、及参数
    $service_name = $data['service'];
    $class = $data['class'];
    $method = $data['method'];
    $param_array = $data['param_array'];


    StatisticClient::tick($class, $method);
    $success = false;
    // 判断类对应文件是否载入
    if(!class_exists($class))
    {

        $include_file = __DIR__ . "/Services/$class.php";
        //use Test\Service;
        $class  = "$service_name\\Service\\" . $class;
        var_dump($class);
        error_log($class.PHP_EOL,3,'/tmp/yanxi.log');
        if(is_file($include_file))
        {
            require_once $include_file;
        }
        if(!class_exists($class))
        {
            $code = 404;
            $msg = "class $class not found";
            StatisticClient::report($class, $method, $success, $code, $msg, $statistic_address);
            // 发送数据给客户端 类不存在
            return $connection->send(array('code'=>$code, 'msg'=>$msg, 'data'=>null));
        }
    }

    // 调用类的方法
    try
    {
        // 对应参数顺序.
        if (isset($data['isReflectParams']) && $data['isReflectParams'] && isset($param_array[0])) {
            $param_array    = parseParam($class, $method, $param_array);
        }
        $ret = call_user_func_array(array($class, $method), $param_array);
        StatisticClient::report($class, $method, 1, 0, json_encode($ret), $statistic_address);
        // 发送数据给客户端，调用成功，data下标对应的元素即为调用结果
        return $connection->send(array('code'=>200, 'msg'=>'success', 'data'=>$ret));
    }
        // 有异常
    catch(Exception $e)
    {
        // 发送数据给客户端，发生异常，调用失败
        $code = $e->getCode() ? $e->getCode() : 500;

        $msg = $e->getMessage();

        $formatMsg = " IP: %s; ErrMsg: %s; Params: %s";
        $reportMsg = sprintf($formatMsg, '127.0.0.1', $msg, json_encode($param_array));

        StatisticClient::report($class, $method, $success, $code, $reportMsg, $statistic_address);

        return $connection->send(array('code'=>$code, 'msg'=>$msg, 'data'=>null));
    }
};

// 如果不是在根目录启动，则运行runAll方法
if(!defined('GLOBAL_START'))
{
    Worker::runAll();
}
*/
/**
 * 自动对应具体接口参数顺序.
 *
 * @param string $targetClass 具体调用类名.
 * @param string $method      具体调用接口方法.
 * @param array  $param_array 接收参数.
 *
 * @return array 与具体调用方法相同的顺序的参数数组
 * @throws \Exception
 */
function parseParam($targetClass, $method, array $param_array = array())
{
    $param_array    = $param_array[0];
    $reflect        = new \ReflectionMethod($targetClass, $method);
    $refParams      = $reflect->getParameters();
    $params         = array();
    $lowParamsArray = array();

    foreach ($param_array as $k => $data) {
        $lowParamsArray[ strtolower($k) ] = $data;
    }

    foreach ($refParams as $i => $one) {
        $name   = strtolower($one->name);
        if (isset($lowParamsArray[ $name ])) {
            $params[ $i ]   = $lowParamsArray[ $name ];
        } elseif ($one->isDefaultValueAvailable()) {
            $params[ $i ]   = $one->getDefaultValue();
        }
    }

    if (count($refParams) !== count($params)) {
        throw new Exception(sprintf("method:%s params wrong", $method), 404);
    }

    return $params;
}

/**
 * 访问权限验证.
 *
 * @param string $class  类名称.
 * @param string $method 方法名称.
 * @param array  $data   请求数据.
 *
 * @return void
 * @throws Exception Rpc 异常.
 */
function authAccess($class, $method, $data)
{
    if (!isset($data['access']['user']) || !isset($data['access']['password']) || !isset($data['access']['timestamp'])) {
        throw new Exception('Auth: Bad Auth request!');
    }
    if (empty(Env::$authConfig) && class_exists('\Config\Auth')) {
        Env::$authConfig = (array) new \Config\Auth;
    }
    if (empty(Env::$authConfig) || empty(Env::$authConfig[$data['access']['user']])) {
        throw new Exception(sprintf('Auth: Missing configuration for `%s`', $data['access']['user']));
    }

    $serverSecret = "{1BA09530-F9E6-478D-9965-7EB31A59537A}";
    $password = md5($data['access']['user'] . $serverSecret . $data['access']['timestamp']);
    if ($password != $data['access']['password']) {
        throw new Exception("Auth:Bas password");
    }

    // cache current app and id
    if (isset($data['access']['app'])) {
        Env::$curApp   = $data['access']['app'];
    }

    $allowedMethods = Env::$authConfig[$data['access']['user']]['allowedMethods'];
    if (!in_array("*", $allowedMethods) && !in_array($class . "::*", $allowedMethods) && !in_array($class . "::" . $method, $allowedMethods)) {
        throw new Exception(sprintf("Auth: Don't allow to call `%s` of `%s` ", $method, $class));
    }
}