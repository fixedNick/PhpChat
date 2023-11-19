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
}
?>