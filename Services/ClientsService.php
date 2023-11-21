<?php
class ClientsService extends ServiceBase 
{
    private $server;
    public function __construct($server) {
        $this->server = $server;
    }

    public function GetOnlineClients() { return $this->server->clients; }
    public function Run()
    {
        $this->server->services[Services::LOGGER]->Write('[+] ClientsService started');
    }

    public function GetOnlineLogins()
    {
        $logins = [];
        foreach($this->server->clients as $client)
            $logins[] = $client->Login;

        
        $this->server->services[Services::LOGGER]->Write('[C] GetOnlineLogins returned: ' . $logins . ', size: ' . count($logins));
        return $logins;
    }
    public function IsClientOnline($login)
    {
        foreach($this->server->clients as $client)
        {
            if($client->Login === $login)
                return true;
        }
        return false;
    }

    public function Disconnect($client)
    {
        foreach ($this->server->clients as $key => $c) {
            if ($c->Login === $client->Login) {
                unset($this->server->clients[$key]);
                break;
            }
        }
    }

    public function GetClientByConnection($connection)
    {
        foreach($this->server->clients as $client)
        {
            if($client->Connection === $connection)
                return $client;
        }
        return null;
    }
    public function GetClient($login)
    {
        foreach($this->server->clients as $client)
        {
            if($client->Login === $login)
                return $client;
        }
        
        $this->server->services[Services::LOGGER]->Write('[X][Exception] Client not found in local storage');
        throw new Exception("Client not found in local storage");
    }

    public function SignUpClient($login, $password)
    {
        $loginFree = $this->server->services[Services::DB]->IsLoginFree($login);
        if($loginFree === false)
            return false;
        $this->server->services[Services::DB]->SaveClient($login, $password);
        return true;
    }

    public function CompleteAuthReturnClient($connection, $login, $password)
    {
        $clientToken = $this->server->services[Services::TOKEN]->GenerateToken();
        $this->server->services[Services::DB]->UpdateSignInInfo($login, $clientToken, time());
        $client = $this->server->services[Services::DB]->GetClientByToken($clientToken);
        $client->Connection = $connection;
        $this->server->clients[] = $client;
        $this->server->services[Services::LOGGER]->Write("[C] Count of clients online now is: " . count($this->server->clients));
        return $client;
    }
}
?>
