<?php

require_once __DIR__ . '/autoload.php';


class Services 
{
    const DB = 'Database';
    const CLIENTS = 'Clients';
    const MESSAGES = 'Messages';
    const TOKEN = 'Token';
    const LOGGER = 'Logger';

}
class Server 
{
    public $services = [];
    public $clients = [];
    public function Run()
    {
        $this->services = [
            Services::LOGGER => new LoggerService($this),
            Services::CLIENTS => new ClientsService($this),
            Services::DB => new DbService($this),
            Services::TOKEN => new TokenService($this),
            Services::MESSAGES => new MessagesService($this)
        ];

        foreach($this->services as $service)
            $service->Run();

        $this->services[Services::LOGGER]->Write('Server Run completed');
    }
}
global $server;
$server = new Server();
$server->Run();

?>