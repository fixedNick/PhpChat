<?php

class Tables 
{
    const SERVER = "server";
    const CLIENTS = "clients";   
}

class Server
{
    const Token = "token";
}

class Clients
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

    }
}
?>