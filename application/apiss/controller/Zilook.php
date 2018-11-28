<?php
/**
 * Created by PhpStorm.
 * User: hu
 * Date: 2018/11/19
 * Time: 15:58
 */
namespace app\api\controller;

use http\Env\Request;
use think\Controller;

class Zilook extends Controller
{
    //我的收藏
    public function shouCang()
    {
        $user_id = input('user_id');
//        $issue_id = input('issue_id');
        if (!$user_id) {
            return error('fail', '请查看参数', '');
        }
        $ress = [];
        $res = [];
        $page = input('page') ? input('page') : 0;
        $data = model('XcxShoucang')->where('user_id', $user_id)->page("$page,10")->select();

        foreach ($data as $v => $k) {
            $user = model('XcxUser')->where('id', $user_id)->find();

            $res['user']['nickName'] = $user['nickName'];
            $res['user']['avatarUrl'] = $user['avatarUrl'];

            $resu = model('XcxAdd')->where('id', $k['comment_id'])->find();
            $image = model('XcxImg')->field('name')->where('imgid', $resu['id'])->find();

            $arr = $image['name'];
            $res['id'] = $k['comment_id'];
            $res['image'] = $arr;
            $ress[] = $res;
        }
        return $ress;


    }

    //别人点我点赞列表
    public function userdianZanList()
    {
        $user_id = input('user_id');
        if (!$user_id) {
            return $this->error('用户id不存在', '', '', '');
        }
        $page = input('page') ? input('page') : 0;
        $wenzhang = model('XcxZan')->where('touser_id',$user_id)->page("$page,10")->select();
        $res = [];
        $ress = [];
        foreach ($wenzhang as $keys => $values) {
            $comment = model('XcxAdd')->field('issue_id')->where('id', $values['comment_id'])->find();
            $iamge = model('XcxImg')->field('name')->where('imgid', $comment['issue_id'])->find();
            $res['image'] = $iamge['name'];
            $data = model('XcxUser')->field('avatarUrl,nickName')->where('id', $values['user_id'])->find();
            $data['nickName'] = $data['nickName'] . '攒了你的分享';
            $res['user'] = $data;
            $res['id'] = $values['comment_id'];
            $ress[] = $res;
        }
        return $ress;

    }

    //新增粉丝
    public function userGuanZhu()
    {
        $user_id = input('user_id');  // 获取用户id
        if ($user_id === ''){  // 用户id 未传递
            return '';
        }
        $page = input('page') ? input('page') : 0;
        // 该用户的粉丝
        $data = model('XcxUserguanzhu')
            ->field('form_user_id')
            ->where('user_id', $user_id)
            ->page("$page,10")
            ->select();
        if (count($data)==0){
            return [];
        }
        $data = array_map(function ($v){
            return intval($v['form_user_id']);
        },$data);
        $touser_list = model('XcxUser')
            ->field(['autograph_name','nickName','avatarUrl','id'])
            ->where('id','in',$data)
            ->select();

        // 该用户关注的粉丝
        $is_both_notice = model('XcxUserguanzhu')
            ->field(['user_id'])
            ->where('form_user_id',$user_id)
            ->select();
        $is_both_notice = array_map(function ($v){
            return intval($v['user_id']);
        },$is_both_notice);
        $GLOBALS['data'] = $is_both_notice;

        // 增加用户的互相关注状态
        $touser_list = array_map(function ($v){
            $v['state']=in_array($v['id'],$GLOBALS['data']) ? '已关注' : '未关注';
            return $v;
        },$touser_list);
        return $touser_list;
    }

    // 收到的评论
    public function receive_comment(){
        $user_id = input('user_id');
        if ($user_id == null){
            return $this->error('用户信息错误!','','','');
        }
        $page = input('page') ? input('page') : 0;
        $message_list = model('XcxComment')
            ->field(['issue_id'])
            ->where('user_id',$user_id)
            ->page("$page,10")
            ->select();
        if (count($message_list)==0){
            return [];
        }
        $message_list = array_map(function ($v){
            return intval($v['issue_id']);
        },$message_list);
        $comment = model('XcxAdd')
            ->field(['home_uaer_name','r_image','image','id'])
            ->where('id','in',$message_list)
            ->select();
        return $comment;
    }

    // 我的晒一晒列表
    public function articleMine(){
        $user_id = input('user_id');
        $article = model('XcxAdd')  //  提取用户文章
        ->field(['home_uaer_name','r_image','description'])
            ->where('user_id',$user_id)
            ->select();

        $article_list = array_map(function ($v){  // 替换关键字
            $article = $v['description'];
            $article = preg_replace('/@\d{1,9}/','here_uid',$article);
            return explode('here_uid',$article);
        },$article);

        $at_user_list = array_map(function ($v){   //  提取所@用户的id
            preg_match_all('/@\d{1,9}/',$v['description'],$user_arr);
            return $user_arr;
        },$article);

        //  提取真实的用户id
        $at_user_list = array_map(function ($v){
            if (count($v[0]) == 0){
                return $v[0];
            }else{
                $res = array_map(function ($val){
                    return intval(substr($val,1));
                },$v[0]);
                return $res;
            }
        },$at_user_list);

        //  根据用户id提取用户名
        $user_list = array_map(function ($v){
            if (count($v)==0){
                return $v;
            }else{
                $v = array_map(function ($v){
                    $user = model('XcxUser')
                        ->field(['nickName','avatarUrl'])
                        ->where('id','in',$v)
                        ->select();
                    return $user[0];
                },$v);
                return $v;
            }
        },$at_user_list);

        //  拼合返回的数据
        foreach ($article_list as $k => &$v){
            $res = [$v];
            array_push($res,$user_list[$k]);
            $v = $res;
        }
        return $article_list;
        // TODO: @功能暂未实现
    }

    // @我的列表
    public function atMine(){
        $user_id = input('user_id');
        if (!$user_id){
            return $this->error('用户id不存在','','','');
        }
        $page = input('page') ? input('page') : 0;
        $at_mine = model('XcxUserAt')
            ->field([
                'art.id',
                'art.image',
                'user.nickName',
                'user.avatarUrl'
            ])
            ->join('hutp_xcx_add art','art.id = hutp_xcx_user_at.add_id')
            ->join('hutp_xcx_user user','user.id = hutp_xcx_user_at.user_id')
            ->where('to_user_id',$user_id)
            ->page("$page,10")
            ->select();

        return $at_mine;
    }

    //  通知列表
    public function noticeList(){
        $user_id = input('user_id');
        if (!$user_id){
            return $this->error('参数错误','','','');
        }
        $page = input('page') ? input('page') : 0;

        //  关注用户的新文章
        $follow_arr = model('XcxGuanzhu')
            ->field('touser_id')
            ->where('user_id',$user_id)
            ->select();
        $follow_arr = array_map(function ($v){
            return intval($v['touser_id']);
        },$follow_arr);
        date_default_timezone_set('PRC');
        $current_date = date('Y-m-d H:i:s',time()-3600*24);
        $follow_arr = model('XcxAdd')
            ->field(['home_uaer_name','image','id','create_time'])
            ->where('user_id','in',$follow_arr)
            ->where('status',1)
            ->where('create_time','>',$current_date)
            ->page("$page,10")
            ->select();
        $follow_arr = array_map(function ($v){
            $v['type'] = 1;
            return $v;
        },$follow_arr);
        return $follow_arr;
    }
}