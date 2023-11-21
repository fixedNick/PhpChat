<?php
require_once __DIR__ . '/Sql/pwd.php';

class Tables 
{
    const SERVER = "server";
    const CLIENTS = "clients";   
}

class TServer
{
    const Token = "token";
}

class TClients
{
    const ID = 'Id';
    const LOGIN = 'Login';
    const PASSWORD = 'Password';
    const LASTLOGIN = 'LastLoginTime';
    const LASTMESSAGE = 'LastMessageTime';
    const TOKEN = 'Token';
    const TOKENEXPIRED = 'TokenExpired';
}

class DbService extends ServiceBase 
{
    private $db; 
    private $server;

    public function __construct($server) {
        $this->server = $server;
    }
    public function Run()
    {
        
        $this->server->services[Services::LOGGER]->Write('[+] DbService started');
        // 
        $host = "127.0.0.1";
        $user = "root";
        $password = Passwords::$remotePassword;
        $database = "maindb";
        //
        $this->db = new mysqli($host, $user, $password, $database);

        if ($this->db->connect_error) {
        $this->server->services[Services::LOGGER]->Write("Connection error: " . $this->db->connect_error);
        }
    }

    public function UpdateServerToken($token)
    {   
        
        $this->server->services[Services::LOGGER]->Write("[DB] Updated server Token: $token");
        $query = "UPDATE " . Tables::SERVER ." SET " . TServer::Token ."='$token' WHERE ID = 0";
        $this->db->query($query);
    }

    public function IsLoginFree($login)
    {
        
        $login = $this->db->real_escape_string($login);
        $this->server->services[Services::LOGGER]->Write("[DB] Checking is login `$login` free...");
        $query = "SELECT * FROM ". Tables::CLIENTS ." WHERE " .TClients::LOGIN. "='$login'";
        $result = $this->db->query($query);

        $isExists = $result->num_rows > 0 ? 'exists' : 'free';
        $this->server->services[Services::LOGGER]->Write("[DB] Login `$login` is [".$isExists."]");
        return $result->num_rows <= 0;
    }

    public function SaveClient($login, $password)
    {
        
        $this->server->services[Services::LOGGER]->Write("[DB] Saving client `$login`");
        $login = $this->db->real_escape_string($login);
        $password = $this->db->real_escape_string($password);
        
        $query = "INSERT INTO ".Tables::CLIENTS." (`".TCLients::LOGIN."`, `".TCLients::PASSWORD."`) VALUES ('$login','$password')";
        print($query);
        $result = $this->db->query($query);
        $this->server->services[Services::LOGGER]->Write("[DB] Saved in db, result: $result");
    }

    public function IsCredentialsValid($login, $password)
    {
        
        $this->server->services[Services::LOGGER]->Write("[DB] Check credentials for `$login`");
        $login = $this->db->real_escape_string($login);
        $password = $this->db->real_escape_string($password);

        $query = "SELECT * FROM ".Tables::CLIENTS." WHERE `".TClients::LOGIN."`='$login' AND `".TClients::PASSWORD."`='$password'";
        $result = $this->db->query($query);
        return $result->num_rows > 0;
    }

    public function UpdateSignInInfo($login, $token, $authTime)
    {
        
        $this->server->services[Services::LOGGER]->Write("[DB] Updating sign-in info for `$login`");
        $query = "UPDATE ".Tables::CLIENTS." SET `".TClients::TOKEN."`='$token', `".TClients::LASTLOGIN."`='$authTime' WHERE `".TClients::LOGIN."`='$login'";
        $result = $this->db->query($query);
        $this->server->services[Services::LOGGER]->Write("[DB] Update table Clients, result: $result");
    }

    public function GetClient($login)
    {
        return $this->GetClientBy($login, TClients::LOGIN);
    }

    public function GetClientByToken($token)
    {
        return $this->GetClientBy($token, TClients::TOKEN);
    }

    private function GetClientBy($sendingData, $by)
    {
        
        $this->server->services[Services::LOGGER]->Write("[DB] Receiving client by `$by`...");
        $query = "SELECT * FROM `".Tables::CLIENTS."` WHERE `".$by."`='$sendingData'";
        $result = $this->db->query($query);
        if($result->num_rows <= 0)
            return null;

        $c = $result->fetch_assoc();
        $client = new Client();
        $client->Login = $c[TClients::LOGIN];
        $client->Id = $c[TClients::ID];
        $client->Token = $c[TClients::TOKEN];
        $client->LastLogin = $c[TClients::LASTLOGIN];

        $this->server->services[Services::LOGGER]->Write("[DB] Client `".$client->Login."` with id `".$client->Id."` and Token `".$client->Token."` successfully received form db");
        return $client;
    }
}
?>
