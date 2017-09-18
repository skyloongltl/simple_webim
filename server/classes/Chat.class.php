<?php
namespace server\classes;

class Chat{
    public static $chatUser;
    public static $redis;
    private static $emotion;

    public function __construct()
    {
        self::$chatUser = new ChatUserInfo();
        self::$redis = Predis::getInstance();
        self::$emotion = require DOMAIN . '/emotion.config.php';
    }

    public static function login($fd, $info){
        if(self::$redis->get($info['name']) !== false){
            return false;
        }
        self::$chatUser->storageUserInfo($fd, $info);
        return array(
            'fd' => $fd,
            'content' => "{$info['name']}加入了群聊"
        );
    }

    public static function logout($fd, $name){
        self::$chatUser->removeUserInfo($fd, $name);
        return array(
            'fd' => $fd,
            'content' => "{$name}退出了群聊",
        );
    }

    public static function dealMessage($data){
        preg_match_all('%@\S*%', $data['content'], $match);
        if(isset($match[0][0])){
            $aiTe = explode('@', $match[0][0]);
            foreach ($aiTe as $val){
                $data['aiTeFd'][] = self::$redis->get($val);
            }
        }else{
            $data['aiTeFd'] = NULL;
        }

        $data['content'] = preg_replace_callback_array(array(
            '%[a-zA-z]+://[^\s]*%' => function ($match){
                return '<img src="' . $match[0] .'" alt="图片"/>';
                //TODO
            },
            '%@\S*%'               => function ($match){
                return '<span class="aiTe" style="color: #2a57ff">' . $match[0] . '</span>';
                //TODO
            },
            '%\[\S*\]%'            => function ($match){
                return self::$emotion[$match[0]];
                //TODO
            }
        ), $data['content']);
        $data['time'] = time();

        return $data;
    }

    public static function open(){
        $ret = self::$chatUser->getAllHistory();
        $fds = self::$chatUser->getAllOnlineUser();
        $usersInfo = self::$chatUser->getAllUserInfo($fds);
        return array(
            'usersInfo' => $usersInfo,
            'history' => $ret,
        );
    }

    public static function addHistory($info){
        self::$chatUser->addHistory($info);
    }
}