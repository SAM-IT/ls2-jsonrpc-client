<?php


namespace SamIT\LimeSurvey\JsonRpc\Concrete;


use SamIT\LimeSurvey\Interfaces\GroupInterface;
use SamIT\LimeSurvey\Interfaces\LocaleAwareInterface;
use SamIT\LimeSurvey\Interfaces\SurveyInterface;
use SamIT\LimeSurvey\JsonRpc\Client;
use SamIT\LimeSurvey\JsonRpc\SerializeHelper;

class Survey extends Base implements SurveyInterface, LocaleAwareInterface, \JsonSerializable
{
    /**
     * @var GroupInterface[]
     */
    private $groups;

    public function __construct(Client $client, array $attributes, array $properties)
    {
        parent::__construct($client, $attributes, $properties);
    }


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
        if (!isset($this->groups)) {
            $this->groups = $this->client->getGroups($this->getId(), $this->getLanguage());
        }
        return $this->groups;
    }

    /**
     * @param string $code The code / title of the question.
     * @return Question|null The question if found.
     */
    public function getQuestionByCode($code)
    {
        /** @var Group $group */
        foreach($this->getGroups() as $group) {
            if (null !== $result = $group->getQuestionByCode($code)) {
                return $result;
            }
        }
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

    /**
     * @return string[] Languages in which the survey is available
     */
    public function getLanguages()
    {
        return $this->attributes['languages'];
    }

    /**
     * @return string The default language of the survey.
     */
    public function getDefaultLanguage()
    {
        return $this->attributes['language'];
    }

    /**
     * @return string The current language for the object.
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param string $language
     * @return self A copy of the object localized to the current locale.
     */
    public function getLocalized($language)
    {
        return $this->client->getSurvey($this->getId(), $language);
    }

    public function jsonSerialize()
    {
        return SerializeHelper::toArray($this);
    }


}