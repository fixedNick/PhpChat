<?php
class ClientsService extends ServiceBase 
{
    private $ClientsList;
    public function GetOnlineClients() { return $this->ClientsList; }
    public function Run()
    {
        $ClientsList = [];
        echo '[+] ClientsService started' . PHP_EOL;
    }

    public function GetOnlineLogins()
    {
        return array_map(function($client) {
            return $client->Login;
        }, $this->ClientsList);
    }
    public function IsClientOnline($login)
    {
        foreach($this->ClientsList as $client)
        {
            if($client->Login === $login)
                return true;
        }
        return false;
    }

    public function GetClient($login)
    {
        foreach($this->ClientsList as $client)
        {
            if($client->Login === $login)
                return $client;
        }
        throw new Exception("Client not found in local storage");
    }

    public function SignUpClient($login, $password)
    {
        global $server;
        $loginFree = $server->services[Services::DB]->IsLoginFree($login);
        if($loginFree === false)
            return false;
        $server->services[Services::DB]->SaveClient($login, $password);
        return true;
    }

    public function CompleteAuthReturnClient($connection, $login, $password)
    {
        global $server;
        $clientToken = $server->services[Services::TOKEN]->GenerateToken();
        $server->services[Services::DB]->UpdateSignInInfo($login, $clientToken, time());
        $client = $server->services[Services::DB]->GetClient($clientToken);
        $client->Connection = $connection;
        $this->ClientsList[] = $client;
        return $client;
    }
}
?>