<?php

require_once __DIR__ . '/autoload.php';


class Services 
{
    const DB = 'Database';
    const CLIENTS = 'Clients';
    const MESSAGES = 'Messages';
    const TOKEN = 'Token';

}
class Server 
{
    public $services = [];

    public function Run()
    {
        $this->services = [
            Services::DB => new DbService(),
            Services::CLIENTS => new ClientsService(),
            Services::TOKEN => new TokenService(),
            Services::MESSAGES => new MessagesService()
        ];

        foreach($this->services as $service)
            $service->Run();
    }
}
global $server;
$server = new Server();
$server->Run();

?>