<?php


namespace SamIT\LimeSurvey\JsonRpc\Concrete;


use SamIT\LimeSurvey\Interfaces\WritableTokenInterface;

class Token extends Base implements WritableTokenInterface
{
    protected $surveyId;

    /**
     * @return int The unique ID for this token.
     */
    public function getId()
    {
        return $this->attributes['tid'];
    }

    /**
     * @return int The unique ID for the survey.
     */
    public function getSurveyId()
    {
        return $this->surveyId;
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->attributes['token'];
    }

    /**
     * @return string
     */
    public function getFirstName()
    {
        return $this->attributes['firstname'];
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->attributes['lastname'];
    }

    /**
     * @return \DateTimeInterface
     */
    public function  getValidFrom()
    {
        return $this->constructDateTimeInterface($this->attributes['validfrom']);
    }

    /**
     * @return \DateTimeInterface
     */
    public function getValidUntil()
    {
        return $this->constructDateTimeInterface($this->attributes['validuntil']);
    }

    /**
     * @return int
     */
    public function getUsesLeft()
    {
        return $this->attributes['usesleft'];
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->attributes['email'];
    }

    /**
     * @return \DateTimeInterface|null Returns the timestamp of completion, or null if not completed.
     */
    public function getCompleted()
    {
        return $this->constructDateTimeInterface($this->attributes['completed']);
    }

    /**
     * @return \DateTimeInterface|null Returns the timestamp of invitation, or null if not completed.
     */
    public function getInvitationSent()
    {
        return $this->constructDateTimeInterface($this->attributes['sent']);

    }

    /**
     * @return int The number of reminders sent
     */
    public function getReminderCount()
    {
        return $this->attributes['remindercount'];
    }

    /**
     * @return \DateTimeInterface|null Returns the timestamp of reminder, or null if not completed.
     */
    public function getReminderSent()
    {
        return $this->constructDateTimeInterface($this->attributes['remindersent']);

    }

    /**
     * @return string The default language of the survey.
     */
    public function getLanguage()
    {
        return $this->attributes['language'];
    }


    /**
     * @return string[] An array of custom attribute name to value. Keys must be the name from LS not the "attribute_x" database fields.
     */
    public function getCustomAttributes()
    {
        return isset($this->attributes['custom']) ? $this->attributes['custom'] : [];
    }

    public function save()
    {
        // Save the token attributes.
        return $this->client->updateToken($this->surveyId, $this->getId(), array_merge($this->attributes, $this->attributes['custom']));
    }

    /**
     * @param string $value
     * @return void
     */
    public function setFirstName($value)
    {
        $this->attributes['firstname'] = (string) $value;
    }

    /**
     * @param string $value
     * @return void
     */
    public function setLastName($value)
    {
        $this->attributes['lastname'] = (string) $value;
    }

    /**
     * @param string $value
     * @return void
     */
    public function setToken($value)
    {
        $this->attributes['token'] = (string) $value;
    }

    /**
     * @param \DateTimeInterface $value The valid from datetime for this token, pass null to not use a valid from datetime.
     * @return void
     */
    public function setValidFrom(\DateTimeInterface $value = null)
    {
        $this->attributes['validfrom'] = $value;
    }

    /**
     * @param \DateTimeInterface $value The valid until datetime for this token, pass null to not use a valid until datetime.
     * @return void
     */
    public function setValidUntil(\DateTimeInterface $value = null)
    {
        $this->attributes['validuntil'] = $value;
    }

    /**
     * @param int $value The number of uses left for the token.
     * @return void
     */
    public function setUsesLeft($value)
    {
        $this->attributes['usesleft'] = (int) $value;
    }

    /**
     * @param string $value
     * @return void
     */
    public function setEmail($value)
    {
        $this->attributes['email'] = (string) $value;
    }

    /**
     * @param \DateTimeInterface $value The completion datetime for this token, pass null to mark token as incomplete.
     * @return void
     */
    public function setCompleted(\DateTimeInterface $value = null)
    {
        $this->attributes['completed'] = $value;
    }

    /**
     * @param \DateTimeInterface $value The datetime on which an invitation was sent to this token, set to null to mark as not invited.
     * @return void
     */
    public function setInvitationSent(\DateTimeInterface $value = null)
    {
        $this->attributes['sent'] = $value;
    }

    /**
     * @param int $value The number of reminders that have been sent for the token.
     * @return void
     */
    public function setReminderCount($value)
    {
        $this->attributes['remindercount'] = (int) $value;
    }

    /**
     * @param \DateTimeInterface $value The datetime on which the last reminder was sent to this token, set to null to mark as no reminder sent.
     * @return void
     */
    public function setReminderSent(\DateTimeInterface $value = null)
    {
        $this->attributes['remindersent'] = $value;
    }

    /**
     * @param string $value The language to use for this token.
     * @return void
     */
    public function setLanguage($value)
    {
        $this->attributes['language'] = (string) $value;
    }

    /**
     * @param string $name The name of the attribute.
     * @param string $value The value of this attribute, it is always stored as a string.
     * @return void
     * @throws \InvalidArgumentException When $name is an unknown custom attribute.
     */
    public function setCustomAttribute($name, $value)
    {
        if (!array_key_exists($name, $this->attributes['custom'])) {
            throw new \InvalidArgumentException("Unknown custom attribute: $name");
        }
        $this->attributes['custom'][(string) $name] = $value;
    }
}
