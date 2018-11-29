<?php
/**
 * Created by PhpStorm.
 * User: fengy
 * Date: 2018/11/28
 * Time: 17:19
 */
namespace app\api\controller;

use think\Controller;

use OSS\OssClient;
use OSS\Core\OssException;

class Upload extends Controller
{
    public function index(){
        $scr = $_FILES['file']['tmp_name'];
        if(!$scr){
            return '参数为空';
        }
        $ext = substr($_FILES['file']['name'],strrpos($_FILES['file']['name'],'.')+1); // 上传文件后缀
        //$dst = md5(time()).'-'.$scr.'.'.$ext;     //上传文件名称
        $fileName = 'daotuba-images/add-image/' . sha1(date('YmdHis', time()) . uniqid()) . '.'. $ext;
        // $this->load-
        $url = $this->upload($fileName,$scr);
        return $url;
        $data = array('url' =>$url);
    }

    public function upload($dst,$src){
        $accessKeyId = "LTAIJhZ8q5l3tSDt";
        $accessKeySecret = "iP1N9m2ykzgqT4WiNaTcFVhthABXze";
        // Endpoint以杭州为例，其它Region请按实际情况填写。
        $endpoint = "oss-cn-hangzhou.aliyuncs.com";
        $bucket = 'images-daotuba123';

        @error_reporting (E_ALL & ~E_NOTICE & ~E_WARNING);

        //获取对象
        $auth = new OssClient($accessKeyId,$accessKeySecret,$endpoint);
        try {
            //上传图片
            $result  = $auth->uploadFile($bucket,$dst,$src);
            dump($result);die;
            return $result['info']['url'];
        } catch (OssException $e) {
            return $e->getMessage();
        }
    }

}