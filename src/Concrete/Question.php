<?php


namespace SamIT\LimeSurvey\JsonRpc\Concrete;


use SamIT\LimeSurvey\Interfaces\AnswerInterface;
use SamIT\LimeSurvey\Interfaces\QuestionInterface;

class Question extends Base implements QuestionInterface
{

    protected $language;
    protected $surveyId;

    /** @var  QuestionInterface[] */
    protected $subQuestions = [];
    /**
     * @return int The unique ID for this survey.
     */
    public function getId()
    {
        return $this->attributes['id'];
    }

    /**
     * @return QuestionInterface[]
     */
    public function getQuestions()
    {
        return $this->subQuestions;
    }

    /**
     * @return string The current language for the object.
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @return string Question text
     */
    public function getText()
    {
        return $this->attributes['text'];
    }

    /**
     * @return AnswerInterface
     */
    public function getAnswers()
    {
        return $this->client->getAnswers($this->getId(), $this->language);
    }

    public function setSubQuestions(array $value) {
        $this->subQuestions = $value;
    }
}