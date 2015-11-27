<?php


namespace SamIT\LimeSurvey\JsonRpc\Concrete;


use Carbon\Carbon;
use SamIT\LimeSurvey\Interfaces\ResponseInterface;
use SebastianBergmann\Comparator\DateTimeComparatorTest;

class Response extends Base implements ResponseInterface
{
    protected $surveyId;
    /**
     * @return int
     */
    public function getSurveyId()
    {
        return $this->surveyId;
    }

    /**
     * @return string
     */
    public function getId()
    {
        if (!isset($this->attributes['id'])) {
            vdd($this->attributes);
        }
        return $this->attributes['id'];
    }

    /**
     * @return \DateTimeImmutable
     */
    public function getSubmitDate()
    {
        // Optionally use Carbon, if it is available.
        if (class_exists(Carbon::class)) {
            $class = Carbon::class;
        } else {
            $class = \DateTimeImmutable::class;
        }

        return new $class($this->attributes['submitdate']);
    }

    /**
     * @return [] Array with all response data.
     */
    public function getData()
    {
        return $this->attributes;
    }
}