<?php
namespace Bedd\BabelNet;

class Client
{
    private $api_key = '';
    
    public function __construct($api_key)
    {
        $this->api_key = $api_key;
    }
}
