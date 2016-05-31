<?php
return array(
    'rpc' => array(
        'Java' => array(
            'lang' => 'java',
            'uri' => 'http://127.0.0.1:8082/api/json',
            'id' => '2c90a45a49a7e03c0149a7e0eb140002',
            'appkey' => '679c5257-f14e-4240-a801-698a679d07b5',
        ),
        'User' => array(
            'lang' => 'php',
            'uri' => array(
                'tcp://127.0.0.1:2016',
            ),
            'user' => 'Api',
            'secret' => '{1BA09530-F9E6-478D-9965-7EB31A59537A}',
            /*
            'user' => array(
                'pc' => '{5F1EA6A6-7A02-2EF9-AB3C-25642CD52C30}',
                'weChat' => '{98016804-45AC-E62B-094A-66763E86A62B}',
            )
            */
        ),
    )
);

// 生成guid
function create_guid ()
{
    $charid = strtoupper (md5 (uniqid (mt_rand (), true)));
    $hyphen = chr (45);// "-"
    $uuid = substr ($charid, 6, 2) . substr ($charid, 4, 2) . substr ($charid, 2, 2) . substr ($charid, 0, 2) . $hyphen . substr ($charid, 10, 2) . substr ($charid, 8, 2) . $hyphen . substr ($charid, 14, 2) . substr ($charid, 12, 2) . $hyphen . substr ($charid, 16, 4) . $hyphen . substr ($charid, 20, 12);
    return $uuid;
}