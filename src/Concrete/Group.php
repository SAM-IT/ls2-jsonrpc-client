<?php


namespace SamIT\LimeSurvey\JsonRpc\Concrete;


use SamIT\LimeSurvey\Interfaces\GroupInterface;
use SamIT\LimeSurvey\Interfaces\LocaleAwareInterface;
use SamIT\LimeSurvey\Interfaces\QuestionInterface;

class Group extends Base implements GroupInterface
{
    /**
     * @var QuestionInterface[]
     */
    private $questions;

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
        if (!isset($this->questions)) {
            /** @var QuestionInterface $question */
            foreach ($this->client->getQuestions($this->surveyId, $this->getId(), $this->getLanguage()) as $question) {
                $this->questions[$question->getTitle()] = $question;
            }
        }
        return $this->questions;
    }

    /**
     * @param string $code The code / title of the question.
     * @return QuestionInterface|null The question if found.
     */
    public function getQuestionByCode($code)
    {
        $this->getQuestions();
        return isset($this->questions[$code]) ? $this->questions[$code] : null;
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

    /**
     * @return int The index of this question.
     */
    public function getIndex()
    {
        return $this->attributes['index'];
    }
}