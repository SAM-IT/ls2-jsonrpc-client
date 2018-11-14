<?php


namespace SamIT\LimeSurvey\JsonRpc\Concrete;


use SamIT\LimeSurvey\Interfaces\QuestionInterface;
use SamIT\LimeSurvey\JsonRpc\Client;

class SubQuestion extends Question implements QuestionInterface
{
    /**
     * @var Question
     */
    protected $parent;

    /**
     * @var int
     */
    protected $dimension = 0;

    public function __construct(Client $client, array $attributes, array $properties)
    {
        parent::__construct($client, $attributes, $properties);
        /**
         * Register this subquestion with the parent.
         */
        if (!isset($this->parent)) {
            throw new \Exception("Parent is required.");
        }
        $this->parent->subQuestions[$this->dimension][] = $this;
    }


    public function getQuestions($dimension)
    {
        return array_map(function(SubQuestion $question) {
            $result = clone $question;
            $result->depth = $this->depth + 1;
            if (isset($this->answers)) {
                $result->answers = $this->answers;
            }
            return $result;
        }, $this->parent->subQuestions[$dimension < $this->dimension ? $dimension : $dimension + 1]);
    }

    public function getDimensions()
    {
        return $this->parent->getDimensions() - $this->depth;
    }

    public function getAnswers()
    {
        return isset($this->answers) ? $this->answers : $this->parent->getAnswers();
    }


}