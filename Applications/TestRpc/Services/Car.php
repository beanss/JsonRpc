<?php
/**
 *  测试
 * @author walkor <worker-man@qq.com>
 */

namespace Test\Service;


class Car
{
    public static function getInfoByUid($uid)
    {
        return array(
            'uid'    => $uid,
            'name'=> 'test',
            'age'   => 18,
            'sex'    => 'hmm..',
            'rpc' => 'car'
        );
    }

    public static function getEmail($uid)
    {
        return 'worker-man-car@qq.com';
    }
}
