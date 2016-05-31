<?php
/**
 *  测试
 * @author walkor <worker-man@qq.com>
 */

namespace Json\Service;

class Car
{
    public static function getInfoByUid($uid)
    {
        return array(
            'uid'    => $uid,
            'name'=> 'test',
            'age'   => 18,
            'sex'    => 'hmm..',
            'rpc' => 'jsoncar'
        );
    }

    public static function getEmail($uid)
    {
        return 'worker-man-car@qq.com';
    }
}
