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

    /**
     * @var string The language of this object.
     */
    protected $language;

    public function __construct(Client $client, $attributes = [], $properties = [])
    {
        $this->client = $client;
        $this->attributes = $attributes;
        foreach ($properties as $property => $value) {
            $this->$property = $value;
        }

    }

}