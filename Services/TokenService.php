<?php
class TokenService extends ServiceBase 
{
    private $ServerToken;
    public function Run()
    {
        echo '[+] TokenService started' . PHP_EOL;

        global $server;
        $token = $this->GenerateServerToken();
        $server->services[Services::DB]->UpdateServerToken($token);
    }

    private function GenerateServerToken()
    {
        return bin2hex(rand(16));
    }
}
?>