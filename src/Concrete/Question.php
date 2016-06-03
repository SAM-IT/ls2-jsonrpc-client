<?php


namespace SamIT\LimeSurvey\JsonRpc\Concrete;


use SamIT\LimeSurvey\Interfaces\AnswerInterface;
use SamIT\LimeSurvey\Interfaces\QuestionInterface;

class Question extends Base implements QuestionInterface
{

    protected $language;
    protected $surveyId;

    protected $answers;

    /** @var  QuestionInterface[] */
    protected $subQuestions = [];


    /**
     * The depth of this question.
     * @var int
     */
    protected $depth = 0;



    /**
     * @return int The unique ID for this survey.
     */
    public function getId()
    {
        return $this->attributes['id'];
    }

    /**
     * @param int $dimension
     * @return QuestionInterface[]
     */
    public function getQuestions($dimension)
    {
        return array_map(function(SubQuestion $question) {
            $result = clone $question;
            $result->depth = $this->depth + 1;
            return $result;
        }, $this->subQuestions[$dimension]);

    }

    /**
     * @param string $code The code / title of the question.
     * @param int $limitDimension Only search the given dimension.
     * @return QuestionInterface|null The question if found.
     */
    public function getQuestionByCode($code, $limitDimension = null)
    {
        if (isset($limitDimension)) {
            foreach($this->subQuestions[$limitDimension] as $subQuestion) {
                if ($subQuestion->getTitle() === $code) {
                    $result = clone $subQuestion;
                    $result->depth = $this->depth + 1;
                    return $result;
                }
            }
        } else {
            foreach ($this->subQuestions as $dimension => $subQuestions) {
                foreach($subQuestions as $subQuestion) {
                    if ($subQuestion->getTitle() === $code) {
                        $result = clone $subQuestion;
                        $result->depth = $this->depth + 1;

                        return $result;
                    }
                }
            }
        }
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
     * @return AnswerInterface[]
     */
    public function getAnswers()
    {
        return isset($this->answers) ? $this->answers : null;
    }

    public function setSubQuestions(array $value) {
        $this->subQuestions = $value;
    }

    /**
     * @return string Question code
     */
    public function getTitle()
    {
        return $this->attributes['title'];
    }

    /**
     * @return int The number of axes for this question.
     */
    public function getDimensions()
    {
        return count($this->subQuestions);
    }

    /**
     * @return int The index of this question.
     */
    public function getIndex()
    {
        return $this->attributes['index'];
    }
}