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
        $logins = [];
        foreach($this->ClientsList as $client)
            $logins[] = $client->Login;
        return $logins;
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

    public function Disconnect($client)
    {
        foreach ($this->ClientsList as $key => $c) {
            if ($c->Login === $client->Login) {
                unset($this->ClientsList[$key]);
                break;
            }
        }
    }

    public function GetClientByConnection($connection)
    {
        foreach($this->ClientsList as $client)
        {
            if($client->Connection === $connection)
                return $client;
        }
        return null;
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
        $client = $server->services[Services::DB]->GetClientByToken($clientToken);
        $client->Connection = $connection;
        $this->ClientsList[] = $client;
        return $client;
    }
}
?>