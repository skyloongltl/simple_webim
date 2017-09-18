<?php
namespace server;

class server{
    private $server = null;

    public function __construct()
    {
        $this->server = new \swoole_websocket_server('0.0.0.0', 9501);
        $this->server->set(
          array(
              'task_worker_num'     =>  8,
          )
        );

        $this->server->on('open', array($this, 'onOpen'));
        $this->server->on('message', array($this, 'onMessage'));
        $this->server->on('task', array($this, 'onTask'));
        $this->server->on('finish', array($this, 'onFinish'));
        $this->server->on('close', array($this, 'onClose'));
        $this->server->start();
    }

    public function onOpen($server, $request){
        $data = array(
            'task'  =>  'open',
            'fd'    =>  $request->fd,
        );
        $this->server->task(json_encode($data));
        echo "open\n";
    }

    public function onMessage(\swoole_websocket_server $server, \swoole_websocket_frame $frame){
        $data = json_decode($frame->data, true);
        switch ($data['type']){
            case '1':
                //登录
                $data = array(
                    'task'  =>  'login',
                    'name'  => $data['name'],
                    'email' => $data['email'],
                    'head_img' => $data['head_img'],
                    'fd'    =>  $frame->fd,
                );
                if(!isset($data['name']) || !isset($data['email'])){
                    $data['task'] = 'nologin';
                    $this->server->task(json_encode($data));
                    break;
                }
                $this->server->task(json_encode($data));
                break;
            case '2':
                //新消息
                $data = array(
                    'task' => 'new',
                    'name' => $data['name'],
                    'head_img' => $data['head_img'],
                    'content' => $data['content'],
                    'fd' => $frame->fd,
                );

                if(!isset($data['content']) || !isset($data['content'])){
                    $data['task'] = 'empty';
                    $this->server->task(json_encode($data));
                    break;
                }

                $this->server->task(json_encode($data));
                break;
            default :
                $this->server->push($frame->fd, json_encode(array('code'=>0, 'msg'=>'type error')));
                break;
        }
    }

    public function onTask(\swoole_websocket_server $server, $task_id, $from_id, $data){
        $data = json_decode($data, true);
        $pushMsg = array('code'=>0, 'msg'=>'', 'data'=>array());
        switch ($data['task']){
            case 'open':
                $open = \Chat::open();
                $pushMsg['data']['type'] = '3';
                $pushMsg['data']['userInfo'] = $open['userInfo'];
                $pushMsg['data']['history'] = $open['history'];
                $this->server->push($data['fd'], json_encode($pushMsg));
                return 'Finished';
            case 'login':
                $login = \Chat::login($data['fd'], array('name'=>$data['name'], 'head_img'=>$data['head_img']));
                $pushMsg['data']['type'] = '1';
                $pushMsg['data']['name'] = $data['name'];
                $pushMsg['data']['head_img'] = $data['head_img'];
                $pushMsg['data']['fd'] = $login['fd'];
                $pushMsg['data']['content'] = $login['content'];
                break;
            case 'new':
                $newMessage = \Chat::dealMessage(array('content'=>$data['content']));
                $pushMsg['data']['type'] = '2';
                $pushMsg['data']['name'] = $data['name'];
                $pushMsg['data']['head_img'] = $data['head_img'];
                $pushMsg['data']['fd'] = $data['fd'];
                $pushMsg['data']['content'] = $newMessage['content'];
                $pushMsg['data']['aiTeFd'] = $newMessage['aiTeFd'];
                break;
            case 'logout':
                $logout = \Chat::logout($data['fd'], $data['name']);
                $pushMsg['data']['type'] = 4;
                $pushMsg['data']['fd'] = $data['fd'];
                $pushMsg['data']['content'] = $logout['content'];
                break;
            case 'empty':
                $pushMsg['code'] = 1;
                $pushMsg['msg'] = '不能发送空信息';
                $this->server->push($data['fd'], $pushMsg);
                return 'Finished';
            case 'nologin':
                $pushMsg['code'] = 1;
                $pushMsg['msg'] = '请填写邮箱或昵称';
                $this->server->push($data['fd'], $pushMsg);
                return 'Finished';
        }
        $this->sendMessage($pushMsg);
        return 'Finished';
    }

    public function onClose(\swoole_websocket_server $server, $fd){

    }
}