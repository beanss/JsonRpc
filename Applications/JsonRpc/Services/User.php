<?php
/**
 *  测试
 * @author walkor <worker-man@qq.com>
 */
//namespace Json\Service;

class User
{
   public static function getInfoByUid($uid)
   {
       return array(
               'uid'    => $uid,
               'name'=> 'test',
               'age'   => 18,
               'sex'    => 'hmm..',
               'rpc' => 'json'
               );
   }
   
   public static function getEmail($uid)
   {
       return 'worker-man-json@qq.com';
   }
}
