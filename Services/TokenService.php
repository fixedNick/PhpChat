<?php
class TokenService extends ServiceBase 
{
    private $ServerToken = null;
    public function GetServerToken() { return $this->ServerToken; }

    public function Run()
    {
        echo '[+] TokenService started' . PHP_EOL;

        global $server;
        $token = $this->GenerateToken();
        echo '[T] ServerToken Generated' . PHP_EOL;

        $this->ServerToken = $token;
        $server->services[Services::DB]->UpdateServerToken($token);

    }

    public function GenerateToken($size = 16)
    {
        if($size == 0) return 0;

        if($size % 2 != 0)
            $size = $size == 1 ? $size + 1 : $size - 1; 
        return bin2hex(random_bytes($size / 2));
    }
    public function IsServerTokenValid($token)
    {
        return $this->IsTokensValid($token, $this->ServerToken);
    }

    public function IsTokensValid($token1, $token2)
    {
        return hash_equals($token1, $token2);
    }

}
?>