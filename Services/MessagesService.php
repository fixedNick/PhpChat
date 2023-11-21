<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Workerman\Worker;

class MessagesService extends ServiceBase 
{
    private $socket;
    private $server;
    public function __construct($server) {
        $this->server = $server;
    }
    public function Run()
    {
        
        $this->server->services[Services::LOGGER]->Write('[+] MessagesService started');
        $this->server->services[Services::LOGGER]->Write('[M] Waiting for server Token');
        while($this->server->services[Services::TOKEN]->GetServerToken() === null)
        {
            sleep(1);
        }
        $this->server->services[Services::LOGGER]->Write('[M] Server Token received, starting socket');
        $this->socket = new Worker("websocket://127.0.0.1:1234");

        $this->socket->count = 16;
        $this->socket->onMessage = function($connection, $data)
        {
            
            $this->server->services[Services::LOGGER]->Write('[M] Message received: ' . $data);
            $json = json_decode($data, true);
            if(isset($json['command']) && isset($json['server_token']))
            {
                $this->server->services[Services::LOGGER]->Write('[M] Recognized command: ' . $json['command']);
                $this->OnCommand($connection, $json['command'], $json['server_token'], isset($json['data']) ? $json['data'] : '');
                return;
            }

            if(isset($json['client_token']) && isset($json['from']) && isset($json['to']) && isset($json['type']))
            {
                if($json['type'] == 'text' && isset($json['text']))
                {
                    
                    $realToken = $this->server->services[Services::DB]->GetClient($json['from'])->Token;
                    $isTokenValid = $this->server->services[Services::TOKEN]->IsTokensValid($json['client_token'], $realToken);
                    if($isTokenValid)
                    {
                        $this->SendTextMessage($json['from'], $json['to'], $json['text']);
                    }
                }
            }
        };

        $this->socket->onClose = function($connection)
        {
            
            $this->server->services[Services::LOGGER]->Write('[M] [-] Client disconnect process started..');
            
            $client = $this->server->services[Services::CLIENTS]->GetClientByConnection($connection);
            if($client === null)
            {
                $this->server->services[Services::LOGGER]->Write('[M] [-] Client not in local storage, disconnect completed');
                return;
            }
            $this->OnDisconnect($client);
        };

        Worker::runAll();
        $this->server->services[Services::LOGGER]->Write('[M] Socket successfully started');
    }   

    private function OnDisconnect($client)
    {
        
        $this->server->services[Services::LOGGER]->Write("[M] [-] Client for disconnect found in localstorage: `".$client->Login."`");
        $this->server->services[Services::CLIENTS]->Disconnect($client);
        $this->BroadcastConnection($client->Login, false);
        $this->server->services[Services::LOGGER]->Write("[M] [-] Disconnecting client `".$client->Login."` completed");
        unset($client);
    }

    // REQUIRED SERVER TOKEN
    private function OnCommand($connection, $command, $serverToken, $data = '')
    {
        
        $isValid = $this->server->services[Services::TOKEN]->IsServerTokenValid($serverToken);
        if($isValid === false)
        {
            $this->server->services[Services::LOGGER]->Write('[M] OnCommand - Server token is invalid.');
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
        
        $status = $this->server->services[Services::CLIENTS]->IsClientOnline($data['recepient_login']);
        $response = json_encode(['status' => $status]);
        $this->_SendTextMessage($connection, $response);
        $this->server->services[Services::LOGGER]->Write('[M] IsClientOnline status `'.$status.'` for client `'.$data['recepient_login'].'`');
    }
    private function SignUpCommand($connection, $data)
    {
        if($this->IsRequiredFieldsReceived($data, ['login', 'password']) === false)
        {
            $this->SendError($connection, 'Fields in `data`: [login, password] is required');
            return;    
        }
        
        $result = $this->server->services[Services::CLIENTS]->SignUpClient($data['login'], $data['password']);
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
        
        
        $result = $this->server->services[Services::DB]->IsCredentialsValid($data['login'], $data['password']);
        if($result === false)
        {
            $this->SendError($connection, 'Invalid credentials');
            return;
        }

        $this->CompleteSignUpIn($connection, $data['login'], $data['password']);
    }

    private function CompleteSignUpIn($connection, $login, $password)
    {
        
        $client = $this->server->services[Services::CLIENTS]->CompleteAuthReturnClient($connection, $login, $password);
        $clientsOnline = $this->server->services[Services::CLIENTS]->GetOnlineLogins();

        $response = json_encode(['token' => $client->Token, 'clients-online' => $clientsOnline]);
        $this->_SendTextMessage($connection, $response);
        $this->server->services[Services::LOGGER]->Write("[M] Auth for client with login `$login` is completed");
        $this->BroadcastConnection($login, true);
    }

    // endregion Commands

    private function IsRequiredFieldsReceived($data, $fields)
    {
        
        foreach($fields as $field)
        {
            if(!isset($data[$field]))
            {
                $this->server->services[Services::LOGGER]->Write("[M] Required field not received `$field`");
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
        
        $recepient = $this->server->services[Services::CLIENTS]->GetClient($to);

        if($recepient === null)
        {  
            $this->server->services[Services::LOGGER]->Write("[M] Recepient with login `$to` not found");
            return;
        }

        $sender = $this->server->services[Services::CLIENTS]->GetClient($from);
        if($sender === null)
        {  
            $this->server->services[Services::LOGGER]->Write("[M] Sender with login `$from` not found");
            return;
        }
        $this->server->services[Services::LOGGER]->Write("[M] Message successfully send from `$from` to `$to`");
        $sToken = $this->server->services[Services::TOKEN]->GetServerToken();
        $this->_SendTextMessage($recepient->Connection, json_encode(['s_token' => $sToken, 'message_data' => ['from' => $sender->Login, 'text' => $text]]));
    }

    private function _SendTextMessage($connection, $msg)
    {
        $connection->send($msg);
    }

    public function BroadcastConnection($clientLogin, $connected)
    {
        
        $clients = $this->server->services[Services::CLIENTS]->GetOnlineClients();
        foreach($clients as $c)
        {
            if($c->Login !== $clientLogin)
            {
                $msg = json_encode([
                    's_token' => $this->server->services[Services::TOKEN]->GetServerToken(),
                    'command' => [
                        'command_name' => 'connection_info', 
                        'is_connected' => $connected,
                        'login' => $clientLogin
                    ]
                ]);
                $this->_SendTextMessage($c->Connection, $msg);
            }
        }
        $this->server->services[Services::LOGGER]->Write("[M] Broadcast about new connection from `$clientLogin` successfuly send to all clients");
    }
}
?>
