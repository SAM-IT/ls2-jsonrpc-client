<?php


namespace SamIT\LimeSurvey\JsonRpc\Concrete;


use SamIT\LimeSurvey\Interfaces\GroupInterface;
use SamIT\LimeSurvey\Interfaces\LocaleAwareInterface;
use SamIT\LimeSurvey\Interfaces\QuestionInterface;

class Group extends Base implements GroupInterface
{

    protected $language;
    protected $surveyId;
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
        return $this->client->getQuestions($this->surveyId, $this->getId(), $this->getLanguage());
    }

    /**
     * @return string Description of the group
     */
    public function getDescription()
    {
        return $this->attributes['description'];
    }

    /**
     * @return string Title of the group
     */
    public function getTitle()
    {
        return $this->attributes['title'];
    }

    /**
     * @return string The current language for the object.
     */
    public function getLanguage()
    {
        return $this->language;
    }
}