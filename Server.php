
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

    public function Run()
    {
        $this->services = [
            Services::DB => new DbService(),
            Services::CLIENTS => new ClientsService(),
            Services::TOKEN => new TokenService(),
            Services::MESSAGES => new MessagesService(),
            Services::LOGGER => new LoggerService(),
        ];
	$this->services[Services::LOGGER]->Write("Server::Run called");
        foreach($this->services as $service)
            $service->Run();

        $this->services[Services::LOGGER]->Write('Server Run completed');
    }
}
global $server;
$server = new Server();
$server->Run();

?>
