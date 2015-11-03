<?php


namespace Concrete;


use SamIT\LimeSurvey\Interfaces\GroupInterface;
use SamIT\LimeSurvey\Interfaces\SurveyInterface;

class Survey extends Base implements SurveyInterface
{

    /**
     * @return int The unique ID for this survey.
     */
    public function getId()
    {
        return $this->attributes['id'];
    }

    /**
     * @return GroupInterface[]
     */
    public function getGroups()
    {
        // TODO: Implement getGroups() method.
    }

    /**
     * @return string Description of the survey
     */
    public function getDescription()
    {
        return $this->attributes['description'];
    }

    /**
     * @return string Title of the survey
     */
    public function getTitle()
    {
        return $this->attributes['title'];
    }
}