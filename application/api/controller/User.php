<?php

namespace app\api\controller;

use app\common\controller\Api;
use app\common\library\Ems;
use app\common\library\Sms;
use fast\Random;
use function PHPSTORM_META\type;
use think\Validate;

/**
 * 会员接口
 */
class User extends Api
{
    protected $noNeedLogin = ['login', 'mobilelogin', 'register', 'resetpwd', 'changeemail', 'changemobile', 'third', 'accredit', 'address', 'listrest','setDefault','delAddress','useraddres','phoneuser','userdata','userupdate'];
    protected $noNeedRight = '*';
    public static $key_t = "sjiofssdsfd";//设置加密种子

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     * 会员中心
     */
    public function index()
    {
        $this->success('', ['welcome' => $this->auth->nickname]);
    }

    /*
     * 授权登入*/

    public function accredit()
    {
//        接收code 获取openid
        $code = input('code');
        $type = $this->openid($code)['openid'];
        $openid = db('userlist')->where("openid", $type)->find();
        if ($openid == '') {
            if ($code != '') {
                $data = [];
                $data['avatarUrl'] = input('avatarUrl');
                $data['city'] = input('city');
                $data['country'] = input('country');
                $data['language'] = input('language');
                $data['nickName'] = input('nickName');
                $data['province'] = input('province');
                $data['openid'] = $type;
                $data['createtime'] = time();
                $add = db('userlist')->insert($data);
                if ($add) {
                    $datas = db('userlist')
                        ->where('openid', '=', $type)
                        ->field('id,openid,phone')
                        ->find();
                    $options = [
                        // 缓存类型为File
                        'type' => 'File',
                        // 缓存有效期为永久有效
                        'expire' => 0,
                        // 指定缓存目录
                        'path' => APP_PATH . 'runtime/cache/',
                    ];
                    cache($options);
                    $username = $this->encrypt($datas);
                    cache('username', $username, 3600);
                    $this->success('注册成功', $username, 200);

                }
            }
        } else {
            $this->success('已有用户', '', 105);
        }


    }

//  用户添加收货地址
    public function address()
    {
        $token = input('token');
        if ($token == '') {
            $this->success('token不能为空', '', 105);
        } else {
            $uid = $this->decrypt($token)['id'];
            $data = [];
            $data['uid'] = $uid;
            $data['city'] = input('provinceName') . '  ' . input('cityName');//省 市
            $data['content'] = input('countyName') . '  ' . input('detailInfo');
            $data['phone'] = input('telNumber');
            $data['username'] = input('userName');
            $data['createtime'] = time();
            $address = db('address')->insert($data);
            if ($address) {
                $uid = $this->decrypt($token)['id'];
                $addresslist = db('address')
                    ->where('uid', '=', $uid)
                    ->order('id desc')
                    ->select();
                $this->success('添加成功', $addresslist, 200);
            } else {
                $this->success('添加失败', '', 105);
            }

        }
    }

    //获取用户地址列表
    public function listrest()
    {
        $token = input('token');
        if ($token == '') {
            $this->success('token不能为空', '', 105);
        } else {
            $uid = $this->decrypt($token)['id'];
            $addresslist = db('address')
                ->where('uid', '=', $uid)
                ->order('id desc')
                ->select();
            if ($addresslist) {
                $this->success('获取成功', $addresslist, 200);
            } else {
                $this->success('获取失败', '', 105);
            }
        }


    }

    //用户设置默认收货地址
    public function setDefault()
    {
        $id = input('id');
        $token = input('token');
        if ($id == '') {
            $this->success('地址id不能为空', '', 105);
        } else {
            $setDefault = db('address')
                ->where('states', '=', '1')
                ->where('uid', '=', $this->decrypt($token)['id'])
                ->value('id');
            if ($setDefault) {
                $states = db('address')
                    ->where('id', '=', $setDefault)
                    ->update(['states' => 0]);
                if ($states) {
                    $updateset = db('address')
                        ->where('id', '=', $id)
                        ->update(['states' => 1]);
                    if($updateset){
                        $uid = $this->decrypt($token)['id'];
                        $addresslist = db('address')
                            ->where('uid', '=', $uid)
                            ->order('id desc')
                            ->select();
                        $this->success('更新成功', $addresslist, 200);
                    }
                }
            } else {
                $updateset = db('address')
                    ->where('id', '=', $id)
                    ->update(['states' => 1]);
                if($updateset){
                    $uid = $this->decrypt($token)['id'];
                    $addresslist = db('address')
                        ->where('uid', '=', $uid)
                        ->order('id desc')
                        ->select();
                    $this->success('更新成功', $addresslist, 200);
                }
            }
        }
    }
/*删除地址
 * */
   public  function delAddress(){
        $id = input('id');
        if($id == ''){
            $this->success('地址id不能为空', '', 105);
        }else{
            $deleadd = db('address')
                ->where('id','=',$id)
                ->delete();
            if($deleadd){
                $this->success('删除成功', '', 200);
            }else{
                $this->success('删除失败', '', 105);
            }
        }
   }
   /*
    * 获取默认用户地址列表*/
   public function useraddres(){
       $token = input('token');
       if($token == ''){
           $this->success('token不能为空', '', 105);
       }else{
           $uid = $this->decrypt($token)['id'];
           $addresslist = db('address')
               ->where('uid', '=', $uid)
               ->where('states', '=', 1)
               ->find();
           if($addresslist){
               $this->success('获取成功', $addresslist, 200);
           }else{
               $uid = $this->decrypt($token)['id'];
               $addresslistmin = db('address')
                   ->where('uid', '=',$uid )
                   ->min('id');
                   $list = db('address')->where('id','=',$addresslistmin)->find();
                   $this->success('获取成功', $list, 200);
           }
       }
   }
   //获取手机联系方式
   public function phoneuser(){
       $token = input('token');
       if($token == ''){
           $this->error('token不能为空', '', 105);
       }else{
           $uid = $this->decrypt($token)['id'];
           $phoneuser = db('userlist')
               ->where('id', '=',$uid )
               ->value('phone');
           if($phoneuser){
               $this->success('获取成功', $phoneuser, 200);
           }else{
               $this->error('暂无数据', '', 105);
           }

       }
   }
//    小程序用户页用户信息
   public function userdata(){
       $token = input('token');
       if($token == ''){
           $this->error('token不能为空','',105);
       }
       $uid = $this->decrypt($token)['id'];//获取用户id
         $userdata = db('userlist')
           ->where('id','=',$uid)
             ->field('id,name,nickName,phone,avatarUrl,price')
             ->find();
         if($userdata){
             $this->success('获取成功',$userdata,200);
         }else{
             $this->error('获取失败[参数不正确]',105);
         }
   }
   //更新用户信息
   public function userupdate(){
       if($_POST){
        //处理文件上传
          $data =  $this->request->request();
          $uid = $this->decrypt($data['token'])['id'];
          $updatat = [];
          $updatat['name'] = $data['username']=='' ? '' : $data['username'];
          $updatat['phone'] = $data['phone']=='' ? '' : $data['phone'];
          $updatat['mailbox'] = $data['mailbox'] == '' ? '' :$data['mailbox'];
          $updatat['avatarUrl'] = !array_key_exists('imgUrl',$data) ? $data['avatarUrl'] : $data['imgUrl'];
          $updrudedata = db('userlist')
              ->where('id','=',$uid)
              ->update($updatat);
          if ($updrudedata!== false){
              $this->success('更新成功','',200);
          }else{
              $this->error('更新失败','',105);
          }
       }else{
           $token = input('token');
           $uid = $this->decrypt($token)['id'];
           $userdata = db('userlist')
               ->where('id','=',$uid)
               ->field('name,nickName,phone,avatarUrl,mailbox,state,city,country,province')
               ->find();
           if($userdata){
               $this->success('获取成功',$userdata,200);
           }else{
               $this->error('获取失败','',105);
           }
       }
   }
    //小程序获取openid

    /**
     * @return array
     */
    public function openid($code)
    {
        $appid = 'wx3fb24f5d2f0956d6'; // 小程序APPID
        $secret = '95fe56bc1dcfffd9a98904fe2f8e27f1'; // 小程序secret
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . $appid . '&secret=' . $secret . '&js_code=' . $code . '&grant_type=authorization_code';
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, 500);
        // 为保证第三方服务器与微信服务器之间数据传输的安全性，所有微信接口采用https方式调用，必须使用下面2行代码打开ssl安全校验。
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_URL, $url);
        $res = curl_exec($curl);
        curl_close($curl);

        return json_decode($res, true); // 这里是获取到的信息
    }


    /*加密数组*/

    public static function encrypt($cookie_array)
    {
        $txt = serialize($cookie_array);
        srand();//生成随机数
        $encrypt_key = md5(rand(0, 10000));//从0到10000取一个随机数
        $ctr = 0;
        $tmp = '';
        for ($i = 0; $i < strlen($txt); $i++) {
            $ctr = $ctr == strlen($encrypt_key) ? 0 : $ctr;
            $tmp .= $encrypt_key[$ctr] . ($txt[$i] ^ $encrypt_key[$ctr++]);
        }
        return base64_encode(User::key($tmp, User::$key_t));
    }

    /*解密数组*/

    public static function decrypt($txt)
    {
        $txt = User::key(base64_decode($txt), User::$key_t);
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

    /**
     * 会员登录
     *
     * @param string $account 账号
     * @param string $password 密码
     */
    public function login()
    {
        $account = $this->request->request('account');
        $password = $this->request->request('password');
        if (!$account || !$password) {
            $this->error(__('Invalid parameters'));
        }
        $ret = $this->auth->login($account, $password);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 手机验证码登录
     *
     * @param string $mobile 手机号
     * @param string $captcha 验证码
     */
    public function mobilelogin()
    {
        $mobile = $this->request->request('mobile');
        $captcha = $this->request->request('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (!Sms::check($mobile, $captcha, 'mobilelogin')) {
            $this->error(__('Captcha is incorrect'));
        }
        $user = \app\common\model\User::getByMobile($mobile);
        if ($user) {
            if ($user->status != 'normal') {
                $this->error(__('Account is locked'));
            }
            //如果已经有账号则直接登录
            $ret = $this->auth->direct($user->id);
        } else {
            $ret = $this->auth->register($mobile, Random::alnum(), '', $mobile, []);
        }
        if ($ret) {
            Sms::flush($mobile, 'mobilelogin');
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Logged in successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 注册会员
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $email 邮箱
     * @param string $mobile 手机号
     * @param string $code 验证码
     */
    public function register()
    {
        $username = $this->request->request('username');
        $password = $this->request->request('password');
        $email = $this->request->request('email');
        $mobile = $this->request->request('mobile');
        $code = $this->request->request('code');
        if (!$username || !$password) {
            $this->error(__('Invalid parameters'));
        }
        if ($email && !Validate::is($email, "email")) {
            $this->error(__('Email is incorrect'));
        }
        if ($mobile && !Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        $ret = Sms::check($mobile, $code, 'register');
        if (!$ret) {
            $this->error(__('Captcha is incorrect'));
        }
        $ret = $this->auth->register($username, $password, $email, $mobile, []);
        if ($ret) {
            $data = ['userinfo' => $this->auth->getUserinfo()];
            $this->success(__('Sign up successful'), $data);
        } else {
            $this->error($this->auth->getError());
        }
    }

    /**
     * 注销登录
     */
    public function logout()
    {
        $this->auth->logout();
        $this->success(__('Logout successful'));
    }

    /**
     * 修改会员个人信息
     *
     * @param string $avatar 头像地址
     * @param string $username 用户名
     * @param string $nickname 昵称
     * @param string $bio 个人简介
     */
    public function profile()
    {
        $user = $this->auth->getUser();
        $username = $this->request->request('username');
        $nickname = $this->request->request('nickname');
        $bio = $this->request->request('bio');
        $avatar = $this->request->request('avatar', '', 'trim,strip_tags,htmlspecialchars');
        if ($username) {
            $exists = \app\common\model\User::where('username', $username)->where('id', '<>', $this->auth->id)->find();
            if ($exists) {
                $this->error(__('Username already exists'));
            }
            $user->username = $username;
        }
        $user->nickname = $nickname;
        $user->bio = $bio;
        $user->avatar = $avatar;
        $user->save();
        $this->success();
    }

    /**
     * 修改邮箱
     *
     * @param string $email 邮箱
     * @param string $captcha 验证码
     */
    public function changeemail()
    {
        $user = $this->auth->getUser();
        $email = $this->request->post('email');
        $captcha = $this->request->request('captcha');
        if (!$email || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::is($email, "email")) {
            $this->error(__('Email is incorrect'));
        }
        if (\app\common\model\User::where('email', $email)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Email already exists'));
        }
        $result = Ems::check($email, $captcha, 'changeemail');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->email = 1;
        $user->verification = $verification;
        $user->email = $email;
        $user->save();

        Ems::flush($email, 'changeemail');
        $this->success();
    }

    /**
     * 修改手机号
     *
     * @param string $email 手机号
     * @param string $captcha 验证码
     */
    public function changemobile()
    {
        $user = $this->auth->getUser();
        $mobile = $this->request->request('mobile');
        $captcha = $this->request->request('captcha');
        if (!$mobile || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if (!Validate::regex($mobile, "^1\d{10}$")) {
            $this->error(__('Mobile is incorrect'));
        }
        if (\app\common\model\User::where('mobile', $mobile)->where('id', '<>', $user->id)->find()) {
            $this->error(__('Mobile already exists'));
        }
        $result = Sms::check($mobile, $captcha, 'changemobile');
        if (!$result) {
            $this->error(__('Captcha is incorrect'));
        }
        $verification = $user->verification;
        $verification->mobile = 1;
        $user->verification = $verification;
        $user->mobile = $mobile;
        $user->save();

        Sms::flush($mobile, 'changemobile');
        $this->success();
    }

    /**
     * 第三方登录
     *
     * @param string $platform 平台名称
     * @param string $code Code码
     */
    public function third()
    {
        $url = url('user/index');
        $platform = $this->request->request("platform");
        $code = $this->request->request("code");
        $config = get_addon_config('third');
        if (!$config || !isset($config[$platform])) {
            $this->error(__('Invalid parameters'));
        }
        $app = new \addons\third\library\Application($config);
        //通过code换access_token和绑定会员
        $result = $app->{$platform}->getUserInfo(['code' => $code]);
        if ($result) {
            $loginret = \addons\third\library\Service::connect($platform, $result);
            if ($loginret) {
                $data = [
                    'userinfo' => $this->auth->getUserinfo(),
                    'thirdinfo' => $result
                ];
                $this->success(__('Logged in successful'), $data);
            }
        }
        $this->error(__('Operation failed'), $url);
    }

    /**
     * 重置密码
     *
     * @param string $mobile 手机号
     * @param string $newpassword 新密码
     * @param string $captcha 验证码
     */
    public function resetpwd()
    {
        $type = $this->request->request("type");
        $mobile = $this->request->request("mobile");
        $email = $this->request->request("email");
        $newpassword = $this->request->request("newpassword");
        $captcha = $this->request->request("captcha");
        if (!$newpassword || !$captcha) {
            $this->error(__('Invalid parameters'));
        }
        if ($type == 'mobile') {
            if (!Validate::regex($mobile, "^1\d{10}$")) {
                $this->error(__('Mobile is incorrect'));
            }
            $user = \app\common\model\User::getByMobile($mobile);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Sms::check($mobile, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Sms::flush($mobile, 'resetpwd');
        } else {
            if (!Validate::is($email, "email")) {
                $this->error(__('Email is incorrect'));
            }
            $user = \app\common\model\User::getByEmail($email);
            if (!$user) {
                $this->error(__('User not found'));
            }
            $ret = Ems::check($email, $captcha, 'resetpwd');
            if (!$ret) {
                $this->error(__('Captcha is incorrect'));
            }
            Ems::flush($email, 'resetpwd');
        }
        //模拟一次登录
        $this->auth->direct($user->id);
        $ret = $this->auth->changepwd($newpassword, '', true);
        if ($ret) {
            $this->success(__('Reset password successful'));
        } else {
            $this->error($this->auth->getError());
        }
    }
}
