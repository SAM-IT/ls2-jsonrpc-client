<?php


namespace SamIT\LimeSurvey\JsonRpc\Concrete;


use SamIT\LimeSurvey\Interfaces\AnswerInterface;

class Answer extends Base implements AnswerInterface
{

    /**
     * @return string Answer text
     */
    public function getText()
    {
        return $this->attributes['text'];
    }

    /**
     * @return string Answer code
     */
    public function getCode()
    {
        return $this->attributes['code'];    }
}