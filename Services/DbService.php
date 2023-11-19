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

    public function Run()
    {
        echo '[+] DbService started' . PHP_EOL;
        // 
        $host = "127.0.0.1";
        $user = "root";
        $password = Passwords::$localPassword;
        $database = "maindb";
        //
        $this->db = new mysqli($host, $user, $password, $database);

        if ($this->db->connect_error) {
            die("Connection error: " . $this->db->connect_error);
        }
    }

    public function UpdateServerToken($token)
    {   
        echo "[DB] Updated server Token: $token" . PHP_EOL;
        $query = "UPDATE " . Tables::SERVER ." SET " . TServer::Token ."='$token' WHERE ID = 0";
        $this->db->query($query);
    }

    public function IsLoginFree($login)
    {
        $login = $this->db->real_escape_string($login);
        echo "[DB] Is Login Free Called for `$login`" . PHP_EOL;
        $query = "SELECT * FROM ". Tables::CLIENTS ." WHERE " .TClients::LOGIN. "='$login'";
        $result = $this->db->query($query);
        return $result->num_rows <= 0;
    }

    public function SaveClient($login, $password)
    {
        echo "[DB] Saving client `$login`". PHP_EOL;
        $login = $this->db->real_escape_string($login);
        $password = $this->db->real_escape_string($password);

        $query = "INSER INTO ".Tables::CLIENTS." (`".TCLients::LOGIN."`, `".TCLients::PASSWORD."`) VALUES ('$login','$password')";
        $result = $this->db->query($query);
        echo "[DB] Saved in db `$result->num_rows` rows". PHP_EOL;
    }

    public function IsCredentialsValid($login, $password)
    {
        echo "[DB] Check credentials for `$login`".PHP_EOL;
        $login = $this->db->real_escape_string($login);
        $password = $this->db->real_escape_string($password);

        $query = "SELECT * FROM ".Tables::CLIENTS." WHERE `".TClients::LOGIN."`='$login' AND `".TClients::PASSWORD."`='$password'";
        $result = $this->db->query($query);
        return $result->num_rows > 0;
    }

    public function UpdateSignInInfo($login, $token, $authTime)
    {
        echo "[DB] Updating sign-in info for `$login`". PHP_EOL;
        $query = "UPDATE ".Tables::CLIENTS." SET `".TClients::TOKEN."`='$token', `".TClients::LASTLOGIN."`='$authTime' WHERE `".TClients::LOGIN."`='$login'";
        $result = $this->db->query($query);
        echo "[DB] Update table Clients, affected `$result->num_rows` rows". PHP_EOL;
    }

    public function GetClient($token)
    {
        echo "[DB] Receiving client..." . PHP_EOL;
        $query = "SELECT * FROM `".Tables::CLIENTS."` WHERE `".TClients::TOKEN."`='$token'";
        $result = $this->db->query($query);
        if($result->num_rows <= 0)
            throw new Exception("Undefined exception, cannot receive client from database by token: `$token`");

        $c = $result->fetch_assoc();
        $client = new Client();
        $client->Login = $c[TClients::LOGIN];
        $client->Id = $c[TClients::ID];
        $client->Token = $c[TClients::TOKEN];
        $client->LastLogin = $c[TClients::LASTLOGIN];
        return $client;
    }
}
?>