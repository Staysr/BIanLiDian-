<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Random;
use function PHPSTORM_META\type;
use think\Validate;
use think\Db;

/**
 *
 */
class Newdevion extends Api
{
    protected $noNeedLogin = ['sqldata'];
    protected $noNeedRight = '*';

    public function _initialize()
    {
        parent::_initialize();
    }

    public function sqldata()
    {
        //device_name设备名称 device_type设备类型 devicenum设备编号 quhua 要处理字段  jingwei要处理字段
        //status 设备状态 lasttime创建时间
        $data = Db::table('zhengyuan_devices')
            ->field('device_name,device_type,devicenum,quhua,jingwei,status,lasttime')
            ->select();
        //处理 quhuan 数据
        foreach ($data as $k => $val) {
            //判断数组是否是json字符串
            $list = json_decode($val['quhua']);
            if ($this->is_json($val['quhua'])) {
                if (is_array($list)) {
                    //处理数组
                    foreach ($list as $k => $va) {
                        $catset = get_object_vars($va);
                        if(array_key_exists('cname',$catset)){
                            $cname = $catset['cname'];
                        }
                        if(array_key_exists('cphone',$catset)){
                            $cphone = $catset['cphone'];
                        }
                        if(array_key_exists('cinfo',$catset)){
                            $cinfo = $catset['cinfo'];
                        }
                    }
                }else{
                    //处理对象
                    $jsonquhua = json_decode($val['quhua']);
                    $catset = get_object_vars($jsonquhua);
                    if(array_key_exists('cname',$catset)){
                        $cname = $catset['cname'];
                    }
                    if(array_key_exists('cphone',$catset)){
                        $cphone = $catset['cphone'];
                    }
                    if(array_key_exists('cinfo',$catset)){
                        $cinfo = $catset['cinfo'];
                    }
                }
            } else {
                echo "抛出数组[不为json字符转]";
                $cname = '';
                $cphone = '';
                $cinfo = '';
            }
            //处理 jingwei
            $jingwei = explode(',',$val['jingwei']);
            $jing = isset($jingwei[0])?$jingwei[0]:'';
            $wei  =  isset($jingwei[1])?$jingwei[1]:'';
            $data = [];
            $data['device_name'] =$val['device_name'];
            $data['contacts'] =$cname;
            $data['telephone'] =$cphone;
            $data['address'] =$cinfo;
            $data['longitude'] =$jing;
            $data['latitude'] =$wei;
            $data['device_type'] =$val['device_type'];
            $data['device_status'] =$val['status']== null? '0':$val['status'];
            $data['create_time'] =$val['lasttime'];
           $add =  Db::table('NewDeviceTable')
                ->insert($data);
           if($add){
               echo"插入成功";
           }else{
               echo"插入失败";
           }
        }
    }
    //判断字符串会否为json字符串
    public function is_json($data = '', $assoc = false)
    {
        $data = json_decode($data, $assoc);
        if (($data && (is_object($data))) || (is_array($data) && !empty($data))) {
            return true;
        }
        return false;
    }
}