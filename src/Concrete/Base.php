<?php


namespace SamIT\LimeSurvey\JsonRpc\Concrete;


use Carbon\Carbon;
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
     * @param string $value The current time.
     * @return \DateTimeInterface | null
     */
    protected function constructDateTimeInterface($value)
    {
        // A valid date time will contain at the very least 8 characters.
        if (empty($value)
            || strlen($value) < 8
            // And contains at least one non zero digit.
            || !preg_match('/[1-9]/', $value)
        ) {
            return null;
        }
        // Optionally use Carbon, if it is available.
        if (class_exists(Carbon::class)) {
            $class = Carbon::class;
        } else {
            $class = \DateTimeImmutable::class;
        }

        return new $class($value);
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


    public function __debugInfo()
    {
        $array = (array) $this;
        unset($array["\0*\0client"]);
        return $array;
    }


}