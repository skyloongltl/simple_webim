<?php
namespace server\classes;

class ChatUserInfo{

    public function storageUserInfo($fd, $info){
        Predis::getInstance()->set($fd . ':client', json_encode($info));
        Predis::getInstance()->sAdd(':online', $fd);
        Predis::getInstance()->set($info['name'], $fd);
    }

    public function removeUserInfo($fd, $name){
        Predis::getInstance()->del($fd . ':client');
        Predis::getInstance()->sRemove(':online', $fd);
        Predis::getInstance()->del($name);
    }

    public function getOneUserInfo($fd){
        return json_decode(Predis::getInstance()->get($fd . ':client'), true);
    }

    public function getAllUserInfo($fds){
        $info = array();
        foreach ($fds as $val){
            $ret = Predis::getInstance()->get($val . ':client');
            $info[] = json_decode($ret, true);
        }
        return $info;
    }

    public function getAllOnlineUser(){
        return Predis::getInstance()->sMembers(':online');
    }

    public function getAllHistory(){
        $sql = "SELECT * from webim";
        $stmt = ImMysql::getInstance()->query($sql);
        $ret = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $ret;
    }

    public function addHistory($chatRecord){
        $sql = "INSERT INTO webim (`name`, `head_img`, `content`, `time`) VALUES(?, ?, ?, ?) LIMIT 1,30";
        $stmt = ImMysql::getInstance()->prepare($sql);
        if($stmt->execute($chatRecord) === false){
            //TODO
        }
        return ImMysql::getInstance()->lastInsertId();
    }
}