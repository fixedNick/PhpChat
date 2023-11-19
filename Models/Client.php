<?php

class Client
{
    public $Id;
    public $Login;
    public $Connection;
    public $LastLogin;
    public function GetConnection() { return $this->Connection; }
    public $Token;

    public function __construct() {}
    public function Create($id)
    {
        $client = new Client();
        $client->Id = $id;
        return $client;
    }
    public function SendTextMessage($login, $text)
    {
        global $server;
        $server->services[Services::MESSAGES]->SendTextMessage($this->Login, $login, $text);
    }
}

?>