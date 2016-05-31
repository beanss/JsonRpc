<?php
/**
 * This file is part of workerman.
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the MIT-LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @author    walkor<walkor@workerman.net>
 * @copyright walkor<walkor@workerman.net>
 * @link      http://www.workerman.net/
 * @license   http://www.opensource.org/licenses/mit-license.php MIT License
 */

if (!class_exists ('JsonProtocol')) create_JsonProtocol ();

/**
 *
 *  RpcClient Rpc客户端
 *
 *
 *  示例
 *  // 服务端列表
 * $address_array = array(
 * 'tcp://127.0.0.1:2015',
 * 'tcp://127.0.0.1:2015'
 * );
 * // 配置服务端列表
 * RpcClient::config($address_array);
 *
 * $uid = 567;
 * // 选择服务
 * $userServer = RpcClient::instance('User');
 *
 * // ==同步调用==
 * // 选择类
 * $user_client = $userServer->setClass('User');
 * $ret_sync = $user_client->getInfoByUid($uid);
 * // Or
 * $ret_sync = RpcClient::instance('User')->setClass('User')->getInfoByUid($uid);
 *
 *
 * // ==异步调用==
 * // 异步发送数据
 * $user_client->asend_getInfoByUid($uid);
 * $user_client->asend_getEmail($uid);
 *
 * 这里是其它的业务代码
 * ..............................................
 *
 * // 异步接收数据
 * $ret_async1 = $user_client->arecv_getEmail($uid);
 * $ret_async2 = $user_client->arecv_getInfoByUid($uid);
 *
 * @author walkor <worker-man@qq.com>
 */
class RpcClient
{
    /**
     * 发送数据和接收数据的超时时间  单位S
     * @var integer
     */
    const TIME_OUT = 5;

    /**
     * 异步调用发送数据前缀
     * @var string
     */
    const ASYNC_SEND_PREFIX = 'asend_';

    /**
     * 异步调用接收数据
     * @var string
     */
    const ASYNC_RECV_PREFIX = 'arecv_';

    /**
     * 服务端地址
     * @var array
     */
    //protected static $addressArray = array();
    protected static $rpcConfig = array();
    /**
     * 异步调用实例
     * @var string
     */
    protected static $asyncInstances = array();

    /**
     * 同步调用实例
     * @var string
     */
    protected static $instances = array();

    /**
     * 到服务端的socket连接
     * @var resource
     */
    protected $connection = null;

    /**
     * 实例的服务名
     * @var string
     */
    protected $serviceName = '';

    /**
     * 实例的类名
     * @var string
     */
    protected $class = '';

    /**
     * rpc 默认语言
     * @var string
     */
    protected $rpcLang = 'php';

    /**rpc 调用地址
     * @var string
     */
    protected $rpcUri = array();

    /**
     * rpc 调用者
     * @var array
     */
    protected $rpcUser = '';

    /**
     * 私钥
     * @var string
     */
    protected $rpcSecret = '';

    /**
     * 设置/获取服务端地址
     * @param array $rpc_config_array
     *
     * @return array
     */
    public static function config($rpc_config_array = array())
    {
        if(!empty($rpc_config_array)){
            self::$rpcConfig = $rpc_config_array;
        }

        return self::$rpcConfig;
    }


    /**
     * 获取一个实例
     *
     * @param string $service_name
     *
     * @return mixed
     * @throws Exception
     */
    public static function instance ($service_name = '')
    {
        if (!$service_name) {
            throw new Exception(sprintf ('RpcClient: instance needs a service name'));
        }
        if (!isset(self::$instances[$service_name])) {
            self::$instances[$service_name] = new self($service_name);
        }
        return self::$instances[$service_name];
    }

    /**
     * 构造函数
     *
     * @param string $service_name
     */
    protected function __construct ($service_name)
    {
        $this->serviceName = $service_name;
        // 初始化配置信息
        $this->init ($service_name);
    }

    // 初始化配置信息
    public function init ($service_name)
    {
        //$rpc_config = array(); // $config['rpc']
        $rpc_config = self::config();
        /*
        if(empty($rpc_config)){  // 无rpc配置时加载本地rpc配置
            $config = array(
                'User' => array(
                    'lang' => 'php',
                    'uri' => array(
                        'tcp://127.0.0.1:2016',
                    ),
                    'user' => 'Api',
                    'secret' => '{1BA09530-F9E6-478D-9965-7EB31A59537A}',
                ),
            );
            $rpc_config = self::config($config);
        }
        */

        if (empty($rpc_config)) {
            throw new Exception('RpcClient: Missing RPC configurations');
        }

        if (!isset($rpc_config[$service_name])) { // miss server config
            throw new Exception(sprintf ('RpcClient: Missing configuration for service `%s`', $service_name));
        }

        $server_config = $rpc_config[$service_name];
        $this->rpcLang = isset($server_config['lang']) ? $server_config['lang'] : 'php';
        $this->rpcUser = isset($server_config['user']) ? $server_config['user'] : '';
        if (!$this->rpcUser) {
            throw new Exception(sprintf ('RpcClient: Missing configuration user in service `%s`', $service_name));
        }

        $this->rpcUri = isset($server_config['uri']) && is_array ($server_config['uri']) ? $server_config['uri'] : array(); // array
        if (empty($this->rpcUri)) {
            throw new Exception(sprintf ('RpcClient: Missing configuration uri in service `%s`', $service_name));
        }

        $this->rpcSecret = isset($server_config['secret']) ? $server_config['secret'] : '';
        if (!$this->rpcSecret) {
            throw new Exception(sprintf ('RpcClient: Missing configuration secretKey in service `%s`', $service_name));
        }
    }

    /**
     * @param string $method
     * @param array  $arguments
     *
     * @return mixed
     * @throws Exception
     */
    public function __call ($method, $arguments)
    {
        // 判断是否是异步发送
        if (0 === strpos ($method, self::ASYNC_SEND_PREFIX)) {
            $real_method = substr ($method, strlen (self::ASYNC_SEND_PREFIX));
            $instance_key = $real_method . serialize ($arguments);
            if (isset(self::$asyncInstances[$instance_key])) {
                throw new Exception($this->serviceName . "->$method(" . implode (',', $arguments) . ") have already been called");
            }
            self::$asyncInstances[$instance_key] = new self($this->serviceName);
            return self::$asyncInstances[$instance_key]->sendData ($real_method, $arguments);
        }
        // 如果是异步接受数据
        if (0 === strpos ($method, self::ASYNC_RECV_PREFIX)) {
            $real_method = substr ($method, strlen (self::ASYNC_RECV_PREFIX));
            $instance_key = $real_method . serialize ($arguments);
            if (!isset(self::$asyncInstances[$instance_key])) {
                throw new Exception($this->serviceName . "->asend_$real_method(" . implode (',', $arguments) . ") have not been called");
            }
            return self::$asyncInstances[$instance_key]->recvData ();
        }
        // 同步发送接收
        $this->sendData ($method, $arguments);

        return $this->recvData ();
    }

    /**
     * 设置类名
     *
     * @param string $class
     *
     * @return $this
     */
    public function setClass ($class)
    {
        $this->class = $class;
        return $this;
    }

    /**
     * 准备发送数据给服务端
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return string
     */
    public function prepareSendData ($method, $arguments)
    {
        $user = $this->rpcUser;
        $secret = $this->rpcSecret;
        $timestamp = microtime (true);

        $bin_data = JsonProtocol::encode (array(
            'version' => '1.0.1',
            'isReflectParams' => true, // 是否对应参数
            'access' => array(
                'user' => $user,
                'password' => md5 ($user . $secret . $timestamp),
                'timestamp' => $timestamp
            ),
            'service' => $this->serviceName,
            'class' => $this->class,
            'method' => $method,
            'param_array' => $arguments,
        ));
        return $bin_data;
    }

    /**
     * 发送数据给服务端
     *
     * @param string $method
     * @param array  $arguments
     *
     * @return bool
     * @throws Exception
     */
    public function sendData ($method, $arguments)
    {
        $this->openConnection ();
        // 准备发给服务端的数据
        $bin_data = $this->prepareSendData ($method, $arguments);
        echo 'sendData:'.PHP_EOL;
        print_r($bin_data);
        if (fwrite ($this->connection, $bin_data) !== strlen ($bin_data)) {
            throw new \Exception('Can not send data');
        }
        return true;
    }

    /**
     * 从服务端接收数据
     * @throws Exception
     */
    public function recvData ()
    {
        $ret = fgets ($this->connection);
        echo PHP_EOL.'recVData:'.$ret;
        $this->closeConnection ();
        if (!$ret) {
            throw new Exception("recvData empty");
        }
        return JsonProtocol::decode ($ret);
    }

    /**
     * 打开到服务端的链接
     * @return void
     * @throws Exception
     */
    protected function openConnection ()
    {
        $address = $this->rpcUri[array_rand($this->rpcUri)];
        $this->connection = stream_socket_client ($address, $err_no, $err_msg);
        if (!$this->connection) {
            throw new Exception("can not connect to $address, $err_no:$err_msg");
        }
        stream_set_blocking ($this->connection, true);
        stream_set_timeout ($this->connection, self::TIME_OUT);
    }

    /**
     * 关闭到服务端的连接
     * @return void
     */
    protected function closeConnection ()
    {
        fclose ($this->connection);
        $this->connection = null;
    }
}

function create_JsonProtocol ()
{
    /**
     * RPC 协议解析 相关
     * 协议格式为 [json字符串\n]
     * @author walkor <worker-man@qq.com>
     * */
    class JsonProtocol
    {
        /**
         * 从socket缓冲区中预读长度
         * @var integer
         */
        const PRREAD_LENGTH = 87380;

        /**
         * 判断数据包是否接收完整
         *
         * @param string $bin_data
         * @param mixed  $data
         *
         * @return integer 0代表接收完毕，大于0代表还要接收数据
         */
        public static function dealInput ($bin_data)
        {
            $bin_data_length = strlen ($bin_data);
            // 判断最后一个字符是否为\n，\n代表一个数据包的结束
            if ($bin_data[$bin_data_length - 1] != "\n") {
                // 再读
                return self::PRREAD_LENGTH;
            }
            return 0;
        }

        /**
         * 将数据打包成Rpc协议数据
         *
         * @param mixed $data
         *
         * @return string
         */
        public static function encode ($data)
        {
            return json_encode ($data) . "\n";
        }

        /**
         * 解析Rpc协议数据
         *
         * @param string $bin_data
         *
         * @return mixed
         */
        public static function decode ($bin_data)
        {
            return json_decode (trim ($bin_data), true);
        }
    }
}
/*
// ==以下调用示例==
if (PHP_SAPI == 'cli' && isset($argv[0]) && $argv[0] == basename (__FILE__)) {
    // 服务端列表
    $address_array = array(
        'tcp://127.0.0.1:2015',
        'tcp://127.0.0.1:2015'
    );
    // 配置服务端列表
    RpcClient::config ($address_array);

    $uid = 567;
    $user_client = RpcClient::instance ('User');
    // ==同步调用==
    $ret_sync = $user_client->getInfoByUid ($uid);

    // ==异步调用==
    // 异步发送数据
    $user_client->asend_getInfoByUid ($uid);
    $user_client->asend_getEmail ($uid);


    // 这里是其它的业务代码
    // ..............................................


    // 异步接收数据
    $ret_async1 = $user_client->arecv_getEmail ($uid);
    $ret_async2 = $user_client->arecv_getInfoByUid ($uid);

    // 打印结果
    var_dump ($ret_sync, $ret_async1, $ret_async2);
}
*/