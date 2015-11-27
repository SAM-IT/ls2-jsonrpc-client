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

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
//    public function jsonSerialize()
//    {
//        $result = [];
//        // Iterate over all getters.
//        foreach(get_class_methods($this) as $method) {
//            if (strncmp('get', $method, 3) == 0) {
//                $reflector = new \ReflectionMethod($this, $method);
//                if ($reflector->isPublic() && $reflector->getNumberOfParameters() == 0) {
//                    $key = lcfirst(substr($method, 3));
//                    $value = $this->$method();
//                    $result[$key] = $value;// instanceof Base ? $value->jsonSerialize() : $value;
//                }
//            }
//        }
//        return $result;
//    }
}