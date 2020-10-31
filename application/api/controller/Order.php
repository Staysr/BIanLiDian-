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
 * 商品的接口
 */
class Order extends Api
{
    protected $noNeedLogin = ['orderone', 'orderlist', 'configuration', 'showshop', 'verificationinventory', 'ordersubmit','NoRand','monitorlist','userorderlist','Newtabliedave'];
    protected $noNeedRight = '*';
    public static $key_t = "sjiofssdsfd";//设置加密种子

    public function _initialize()
    {
        parent::_initialize();
    }

    //获取商品的一级分类
    public function orderone()
    {
        $list = db('classify')
            ->where('state', '>', '0')
            ->field('id,name,description')
            ->select();
        if ($list) {
            $this->success('获取成功', $list, 200);
        } else {
            $this->error('暂无商品', '', 105);
        }
    }

    //获取商品列表
    public function orderlist()
    {
        $id = input('id');
        if ($id == '') $this->error('一级分类id不能为空', '', 105);
        $list = db('commodity')
            ->where("classify_id = $id AND state > 0 AND repertory > 0")
            ->order('id desc')
            ->select();

        if ($list) {
            $this->success('获取成功', $list, 200);
        } else {
            $this->error('暂无商品', '', 105);
        }
    }

    //购物配置项
    public function configuration()
    {
        $data = db('configuration')
            ->where('state', '=', 1)
            ->field('id,distribution,deliverypay')
            ->find();
        if ($data) {
            $this->success('获取数据成功', $data, 200);
        } else {
            $this->error('配置项为空', '', 105);
        }
    }

    //获取购物车列表
    public function showshop()
    {
        $goodsid = input('goodsid');
        if (!empty($goodsid)) {
            $data = db('commodity')
                ->where("state  > 0 AND repertory > 0")
                ->where('id', 'in', explode(',', $goodsid))
                ->select();
            if ($data) {
                $this->success('获取数据成功', $data, 200);
            }
        } else {
            $this->error('参数不能为空', '', 105);
        }
    }

    /*验证购物库存*/
    public function verificationinventory()
    {
        $goods = input('goods');
        $data = json_decode(html_entity_decode($goods), true);
        $list = db('commodity');
        foreach ($data as $value) {
            $cooder = $list->where('id', '=', $value['goodsid'])->select();
            foreach ($cooder as $val) { //repertory 库存 //buypay 单个商品的购买的数量 //count 前台的购买数量
                if ($value['goodsid'] == $val['id']) {
                    if ($value['count'] > $val['repertory'] && $value['count'] > $val['buypay']) { //判断库存
                        $this->error($val['commodityname'] . '库存不足!请重新下单', '', 105);
                        return;
                    } else if ($value['count'] > $val['buypay']) {//判断单个购买的数量
                        $this->error($val['commodityname'] . '购买数量较大请联系客服', '', 105);
                    } else {
                        $this->error('订单正常', '', 200);
                    }

                }
            }
        }
    }

    /*提交订单*/
    public function ordersubmit()
    {
        $goods = input('goods');//订单列表
        $addresid = input('addresid');//地址id
        $token = input('token');//token
        $deliverypay = input('deliverypay');//配送费用
        $moneys = input('moneys');//总价格
        $orderstatus = input('orderstatus');//订单的配送状态  2自取 1 配送
        $paytype = input('paytype');//支付状态;
        $phone = input('phone');//自取的联系方式
        $remark = input('remark');//订单备注
        $data = [];
        $data['uid'] = $this->decrypt($token)['id'];
        if($orderstatus == 1){
            $data['address_id'] = $addresid;
            $data['serial_pay'] = $deliverypay;
        }
        $data['description'] = $remark;
        $data['createtime'] = time();
        $data['order_num'] = $this->onlyosn();
        $data['serial_number'] = $this->NoRand();
        $data['pay_type'] = $paytype;
        $data['distribution_type'] = $orderstatus;
        $data['price'] = $moneys;
        $set = db('order')->insertGetId($data);
        if ($set) {
            $orderlist = json_decode(html_entity_decode($goods), true);
            $goodsid = ' ';
            $count = ' ';
            $strcount = '';
            foreach ($orderlist as $key => $vals) {
                $goodsid .= $vals['goodsid'] . ',';
                $count .= $vals['count'] . ',';
            }
            $topicid = rtrim($goodsid, ',');
            $counts = rtrim($count, ',');
            foreach (explode(',', $topicid) as $keys => $val) {
                foreach (explode(',', $counts) as $key => $vals) {
                    if ($keys == $key) {
                        $strcount .= ',' . $val . '=>' . $vals;
                    }
                }
            }
            $upend = db('order')->where('id', '=', $set)->update(['commodity_id' => $topicid, 'num' => ltrim($strcount, ',')]);
            if ($upend) {
                if($orderstatus == 2){
                    $userphone = db('userlist')->where('id', '=', $this->decrypt($token)['id'])->value('phone');
                    if ($userphone == 0) {
                        $upendphone = db('userlist')->where('id', '=', $this->decrypt($token)['id'])->update(['phone' => $phone]);
                    }
                }
                $this->success('订单生成成功', $set, 200);
            } else {
                $this->error('订单生成失败', '', 105);
            }
        }

    }

    //通用生成唯一订单号
    public function onlyosn()
    {
        @date_default_timezone_set("PRC");
        $order_id_main = date('YmdHis') . rand(10000000, 99999999);
        //订单号码主体长度
        $order_id_len = strlen($order_id_main);
        $order_id_sum = 0;
        for ($i = 0; $i < $order_id_len; $i++) {
            $order_id_sum += (int)(substr($order_id_main, $i, 1));
        }
        //唯一订单号码（YYYYMMDDHHIISSNNNNNNNNCC）
        $osn = $order_id_main . str_pad((100 - $order_id_sum % 100) % 100, 2, '0', STR_PAD_LEFT); //生成唯一订单号
        return $osn;
    }
//流水号
    public function NoRand()
    {
        $danhao = date('Ymd') . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        return $danhao;
    }
    /*解密数组*/

    public static function decrypt($txt)
    {
        $txt = Order::key(base64_decode($txt), Order::$key_t);
        $tmp = '';
        for ($i = 0; $i < strlen($txt); $i++) {
            $md5 = $txt[$i];
            $tmp .= $txt[++$i] ^ $md5;
        }
        $tmp_t = unserialize($tmp);
        return $tmp_t;
    }

    //key
    public static function key($txt, $encrypt_key)
    {
        $encrypt_key = md5($encrypt_key);
        $ctr = 0;
        $tmp = '';
        for ($i = 0; $i < strlen($txt); $i++) {
            $ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
            $tmp .= $txt[$i] ^ $encrypt_key[$ctr++];
        }
        return $tmp;
    }
    //监测订单状态
    public function monitorlist(){
        $id = input('id');
        if($id == ''){
            $this->error('订单id不能为空', '', 105);
        }
        $distributiontype = db('order')->where('id','=',$id)->value('distribution_type');
        $sosshui = db('order')->where('id','=',$id)->value('status');
        if($distributiontype == 1){ //  distribution_type 配送方式:1=配送,2=自提
            switch ((int)$sosshui) { //配送状态:0=未配送;1=配送中,2=配送完成
                case 0:
                    $this->success('您的订单正在打包!', '', 106);
                    break;
                case 1:
                    $this->success('您的订单正在配送中!', '', 107);
                    break;
                case 2:
                    $this->success('您的订单配送完毕请查收!', '', 108);
                    break;
                default:
                    $this->success('无知订单!', '', 105);
            }
        }else if($distributiontype == 2){//2=自提
            switch ((int)$sosshui) { //配送状态:0=未配送;1=配送中,2=配送完成
                case 0:
                    $this->success('您的订单正在打包!', '', 106);
                    break;
                case 1:
                    $this->success('您的订单打包完毕!', '', 107);
                    break;
                case 2:
                    $this->success('您的订单配送完毕请查收!', '', 108);
                    break;
                default:
                    $this->success('无知订单!', '', 105);
            }
        }else{
            $this->error('订单异常', '', 105);
        }
    }
//获取 用户的商店订单\
    public function userorderlist(){
        $uid = input('token');
        if($uid == ''){
            $this->error('token不能为空','',105);
        }
        $userorderlist = db('order')
            ->where('uid','=',$this->decrypt($uid)['id'])
            ->order('id desc')
            ->select();
        //处理订单名称
        foreach($userorderlist as $key=>$val){
            $commodity_id = explode(',',$val['commodity_id']);//商品id
            $num = explode(',',$val['num']);//商品数量
            foreach ($num as $value){
                $commoditynum = explode('=>',$value);//商品数量
            }
            $data = db('commodity')
                ->where('id','in',$commodity_id)
                ->field('id,commodityname')
                ->select();
            $datasun = [
                'commoditynum'=>$commoditynum,
                'userorderlist'=>$userorderlist
            ];
        }
        if ($userorderlist){
            $this->success('获取成功',$datasun,200);
        }else{
            $this->error('获取失败','',105);
        }
    }
}
