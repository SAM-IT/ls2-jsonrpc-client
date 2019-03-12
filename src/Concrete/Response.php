<?php


namespace SamIT\LimeSurvey\JsonRpc\Concrete;

use SamIT\LimeSurvey\Interfaces\ResponseInterface;

class Response implements ResponseInterface
{
    protected $surveyId;
    private $data = [];

    public function __construct(int $surveyId, array $data)
    {
        $this->surveyId = $surveyId;

        $this->data = $data;
    }

    public function getSurveyId(): int
    {
        return $this->surveyId;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->data['id'];
    }

    /**
     * @return \DateTimeInterface
     */
    public function getSubmitDate()
    {
        return $this->constructDateTimeInterface($this->data['submitdate']);
    }

    /**
     * @return [] Array with all response data.
     */
    public function getData()
    {
        return $this->data;
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
        return new \DateTimeImmutable($value);
    }
}