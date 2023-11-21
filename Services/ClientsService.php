<?php
class ClientsService extends ServiceBase 
{
    private static $ClientsList;
    public function GetOnlineClients() { return self::$ClientsList; }
    public function Run()
    {
        $ClientsList = [];
        global $server;
        $server->services[Services::LOGGER]->Write('[+] ClientsService started');
    }

    public function GetOnlineLogins()
    {
        $logins = [];
        foreach(self::$ClientsList as $client)
            $logins[] = $client->Login;

        global $server;
        $server->services[Services::LOGGER]->Write('[C] GetOnlineLogins returned: ' . $logins . ', size: ' . count($logins));
        return $logins;
    }
    public function IsClientOnline($login)
    {
        foreach(self::$ClientsList as $client)
        {
            if($client->Login === $login)
                return true;
        }
        return false;
    }

    public function Disconnect($client)
    {
        foreach (self::$ClientsList as $key => $c) {
            if ($c->Login === $client->Login) {
                unset(self::$ClientsList[$key]);
                break;
            }
        }
    }

    public function GetClientByConnection($connection)
    {
        foreach(self::$ClientsList as $client)
        {
            if($client->Connection === $connection)
                return $client;
        }
        return null;
    }
    public function GetClient($login)
    {
        foreach(self::$ClientsList as $client)
        {
            if($client->Login === $login)
                return $client;
        }
        global $server;
        $server->services[Services::LOGGER]->Write('[X][Exception] Client not found in local storage');
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
        self::$ClientsList[] = $client;
        $server->services[Services::LOGGER]->Write("[C] Count of clients online now is: " . count(self::$ClientsList));
        return $client;
    }
}
?>
