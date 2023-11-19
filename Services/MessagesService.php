<?php
require_once __DIR__ . '\../vendor/autoload.php';

use Workerman\Worker;

class MessagesService extends ServiceBase 
{
    private $socket;
    public function Run()
    {
        global $server;
        echo '[+] MessagesService started' . PHP_EOL;
        echo '[M] Waiting for server Token' . PHP_EOL;
        while($server->services[Services::TOKEN]->GetServerToken() === null)
        {
            sleep(1);
        }
        echo '[M] Server Token received, starting socket' . PHP_EOL;
        $this->socket = new Worker("websocket://127.0.0.1:1234");
        $this->socket->count = 16;
        $this->socket->onMessage = function($connection, $data)
        {
            echo 'NEW MESSAGE: ' . $data . PHP_EOL;
            $json = json_decode($data, true);
            if(isset($json['command']) && isset($json['server-token']))
            {
                $this->OnCommand($connection, $json['command'], $json['server-token'], isset($json['data']) ? $json['data'] : '');
                return;
            }

            if(isset($json['client-token']) && isset($json['from']) && isset($json['to']) && isset($json['type']))
            {
                if($json['type'] == 'text' && isset($json['text']))
                {
                    // text message
                }
            }
        };

        $this->socket->onClose = function($connection)
        {
            echo 'CLIENT DISCONNECTED';
        };

        Worker::runAll();
    }

    // REQUIRED SERVER TOKEN
    private function OnCommand($connection, $command, $serverToken, $data = '')
    {
        global $server;
        $isValid = $server->services[Services::TOKEN]->IsServerTokenValid($serverToken);
        if($isValid === false)
        {
            $this->SendError($connection, "Invalid s-token");
            return;
        }

        switch($command)
        {
            case 'sign-up':
                $this->SignUpCommand($connection, $data);
                break;
            case 'sign-in':
                $this->SignInCommand($connection, $data);
                break;
        }
        
    }

    // region Commands
    private function SignUpCommand($connection, $data)
    {
        if(!isset($data['data']['login']) || !isset($data['data']['password']))
        {
            $this->SendError($connection, 'Fields in `data`: [login, password] is required');
            return;    
        }
        global $server;
        $result = $server->services[Services::CLIENTS]->SignUpClient($data['data']['login'], $data['data']['password']);
        if($result === false)
        {
            $this->SendError($connection, 'Client with login `'.$data['data']['login'].'` already exists');
            return;
        }

        $this->CompleteSignUpIn($connection, $data['data']['login'], $data['data']['password']);
    }

    private function SignInCommand($connection, $data)
    {
        if(!isset($data['data']['login']) || !isset($data['data']['password']))
        {
            $this->SendError($connection, 'Fields in `data`: [login, password] is required');
            return;    
        }
        
        global $server;
        $result = $server->services[Services::DB]->IsCredentialsValid($data['data']['login'], $data['data']['password']);
        if($result === false)
        {
            $this->SendError($connection, 'Invalid credentials');
            return;
        }

        $this->CompleteSignUpIn($connection, $data['data']['login'], $data['data']['password']);
    }

    private function CompleteSignUpIn($connection, $login, $password)
    {
        global $server;
        $client = $server->services[Services::CLIENTS]->CompleteAuthReturnClient($connection, $login, $password);
        $clientsOnline = $server->services[Services::CLIENTS]->GetOnlineLogins();

        $response = json_encode(['token' => $client->Token, 'clients-online' => $clientsOnline]);
        $this->_SendTextMessage($connection, $response);
    }

    // endregion Commands

    // REQUIRED CLIENT TOKEN
    private function OnMessage($connection, $data)  
    {

    }
    
    private function SendError($connection, $errorText)
    {
        $json = json_encode(['error' => $errorText]);
        $this->_SendTextMessage($connection, $json);
    }

    public function SendTextMessage($from, $to, $text)
    {
        global $server;
        $recepient = $server->services[Services::CLIENTS]->GetClient($to);
        $sender = $server->services[Services::CLIENTS]->GetClient($from);

        $this->_SendTextMessage($recepient->Connection, json_encode(['from' => $sender->Login, 'text' => $text]));
    }

    private function _SendTextMessage($connection, $msg)
    {
        $connection->send($msg);
    }
}
?>