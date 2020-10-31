public function  Newtabliedave(){
$zhengyuan_devices = Db::table('zhengyuan_devices')
->field('deviceid,devicenum,device_name,device_type,jingwei,status,quhua')
->select();
//处理 quhua
foreach ($zhengyuan_devices as $key=>$value){
$json = get_object_vars(json_decode($value['quhua']));

$cname = $json['cname'] == '' ? '' :  $json['cname'];
$cphone = $json['cphone'] == '' ? '' :  $json['cphone'];
$cinfo = $json['cinfo'] == '' ? '' : $json['cinfo'];
//处理经纬
$jingwei = $value['jingwei'];
$jing =  explode(',',$jingwei);
$jing1 = $jing[1] == '' ? '' :$jing[1];
$wei = $jing[0] == '' ? '' : $jing[0];
$data = [];
$data['device_name'] = $value['device_name'];
$data['contacts'] = $cname;
$data['telephone'] = $cphone;
$data['address'] = $cinfo;
$data['longitude'] =$jing1;
$data['latitude'] =$wei;
$data['device_type'] =$value['device_type'];
$data['device_status'] = $value['status'];
$data['device_no'] = $value['devicenum'];
$Newtable = Db::table('NewDeviceTable')
->insert($data);
if($Newtable){
echo '成功';
}else{
echo '失败';
}
}
}