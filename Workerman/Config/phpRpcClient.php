<?php

namespace Config;

class phpRpcClient
{
    public $user = array(
        'lang' => 'php',
        'uri' => array(
            'tcp://127.0.0.1:2016',
        ),
        'user' => array(
            'api' => '{1BA09530-F9E6-478D-9965-7EB31A59537A}',
            'pc' => '{5F1EA6A6-7A02-2EF9-AB3C-25642CD52C30}',
            'weChat' => '{98016804-45AC-E62B-094A-66763E86A62B}',
        )
    );

    public $Test = array(
        'lang' => 'php',
        'uri' => array(
            'tcp://192.168.33.10:2016',
        ),
        'user' => array(
            'api' => '{1BA09530-F9E6-478D-9965-7EB31A59537A}',
            'pc' => '{5F1EA6A6-7A02-2EF9-AB3C-25642CD52C30}',
            'weChat' => '{98016804-45AC-E62B-094A-66763E86A62B}',
        )
    );
}