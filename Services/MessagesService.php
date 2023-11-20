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
            echo '[M] Message received: ' . $data . PHP_EOL;
            $json = json_decode($data, true);
            if(isset($json['command']) && isset($json['server_token']))
            {
                echo '[M] Recognized command: ' . $json['command'] . PHP_EOL;
                $this->OnCommand($connection, $json['command'], $json['server_token'], isset($json['data']) ? $json['data'] : '');
                return;
            }

            if(isset($json['client_token']) && isset($json['from']) && isset($json['to']) && isset($json['type']))
            {
                if($json['type'] == 'text' && isset($json['text']))
                {
                    global $server;
                    $realToken = $server->services[Services::DB]->GetClient($json['from'])->Token;
                    $isTokenValid = $server->services[Services::TOKEN]->IsTokensValid($json['client_token'], $realToken);
                    if($isTokenValid)
                    {
                        $this->SendTextMessage($json['from'], $json['to'], $json['text']);
                    }
                }
            }
        };

        $this->socket->onClose = function($connection)
        {
            echo '[M] [-] Client disconnect process started..'. PHP_EOL;
            global $server;
            $client = $server->services[Services::CLIENTS]->GetClientByConnection($connection);
            if($client === null)
            {
                echo '[M] [-] Client not in local storage, disconnect completed'. PHP_EOL;
                return;
            }
            $this->OnDisconnect($client);
        };

        Worker::runAll();
    }

    private function OnDisconnect($client)
    {
        echo "[M] [-] Client for disconnect found in localstorage: `".$client->Login."`". PHP_EOL;
        global $server;
        $server->services[Services::CLIENTS]->Disconnect($client);
        $this->BroadcastConnection($client->Login, false);
        echo "[M] [-] Disconnecting client `".$client->Login."` completed".PHP_EOL;
        unset($client);
    }

    // REQUIRED SERVER TOKEN
    private function OnCommand($connection, $command, $serverToken, $data = '')
    {
        global $server;
        $isValid = $server->services[Services::TOKEN]->IsServerTokenValid($serverToken);
        if($isValid === false)
        {
            echo '[M] OnCommand - Server token is invalid.' . PHP_EOL;
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
            case 'is-client-online':
                $this->IsClientOnlineCommand($connection, $data);
                break;
        }
        
    }

    // region Commands
    private function IsClientOnlineCommand($connection, $data)
    {
        if($this->IsRequiredFieldsReceived($data, ['recepient_login']) === false)
        {
            $this->SendError($connection, 'Fields in `data`: [recepient_login] is required');
            return;
        }
        global $server;
        $status = $server->services[Services::CLIENTS]->IsClientOnline($data['recepient_login']);
        $response = json_encode(['status' => $status]);
        $this->_SendTextMessage($connection, $response);
        echo '[M] IsClientOnline status `'.$status.'` for client `'.$data['recepient_login'].'`'. PHP_EOL;
    }
    private function SignUpCommand($connection, $data)
    {
        if($this->IsRequiredFieldsReceived($data, ['login', 'password']) === false)
        {
            $this->SendError($connection, 'Fields in `data`: [login, password] is required');
            return;    
        }
        global $server;
        $result = $server->services[Services::CLIENTS]->SignUpClient($data['login'], $data['password']);
        if($result === false)
        {
            $this->SendError($connection, 'Client with login `'.$data['login'].'` already exists');
            return;
        }

        $this->CompleteSignUpIn($connection, $data['login'], $data['password']);
    }

    private function SignInCommand($connection, $data)
    {
        if($this->IsRequiredFieldsReceived($data, ['login', 'password']) === false)
        {
            $this->SendError($connection, 'Fields in `data`: [login, password] is required');
            return;    
        }
        
        global $server;
        $result = $server->services[Services::DB]->IsCredentialsValid($data['login'], $data['password']);
        if($result === false)
        {
            $this->SendError($connection, 'Invalid credentials');
            return;
        }

        $this->CompleteSignUpIn($connection, $data['login'], $data['password']);
    }

    private function CompleteSignUpIn($connection, $login, $password)
    {
        global $server;
        $client = $server->services[Services::CLIENTS]->CompleteAuthReturnClient($connection, $login, $password);
        $clientsOnline = $server->services[Services::CLIENTS]->GetOnlineLogins();

        $response = json_encode(['token' => $client->Token, 'clients-online' => $clientsOnline]);
        $this->_SendTextMessage($connection, $response);
        echo "[M] Auth for client with login `$login` is completed".PHP_EOL;
        $this->BroadcastConnection($login, true);
    }

    // endregion Commands

    private function IsRequiredFieldsReceived($data, $fields)
    {
        foreach($fields as $field)
        {
            if(!isset($data[$field]))
            {
                echo "[M] Required field not received `$field`".PHP_EOL;
                return false;
            }
        }
        return true;
    }

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

        if($recepient === null)
        {  
            echo "[M] Recepient with login `$to` not found".PHP_EOL;
            return;
        }

        $sender = $server->services[Services::CLIENTS]->GetClient($from);
        if($sender === null)
        {  
            echo "[M] Sender with login `$from` not found".PHP_EOL;
            return;
        }
        echo "[M] Message successfully send from `$from` to `$to`";
        $sToken = $server->services[Services::TOKEN]->GetServerToken();
        $this->_SendTextMessage($recepient->Connection, json_encode(['s_token' => $sToken, 'message_data' => ['from' => $sender->Login, 'text' => $text]]));
    }

    private function _SendTextMessage($connection, $msg)
    {
        $connection->send($msg);
    }

    public function BroadcastConnection($clientLogin, $connected)
    {
        global $server;
        $clients = $server->services[Services::CLIENTS]->GetOnlineClients();
        foreach($clients as $c)
        {
            if($c->Login !== $clientLogin)
            {
                $msg = json_encode([
                    's_token' => $server->services[Services::TOKEN]->GetServerToken(),
                    'command' => [
                        'command_name' => 'connection_info', 
                        'is_connected' => $connected,
                        'login' => $clientLogin
                    ]
                ]);
                $this->_SendTextMessage($c->Connection, $msg);
            }
        }
        echo "[M] Broadcast about new connection from `$clientLogin` successfuly send to all clients".PHP_EOL;
    }
}
?>