<?php


namespace SamIT\LimeSurvey\JsonRpc\Concrete;


use SamIT\LimeSurvey\JsonRpc\Client;

class Base
{
    /**
     * @var Client
     */
    protected $client;

    protected $attributes;

    public function __construct(Client $client, $attributes = [])
    {
        $this->client = $client;
        $this->attributes = $attributes;

    }


}