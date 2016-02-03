<?php


namespace SamIT\LimeSurvey\JsonRpc;


use SamIT\LimeSurvey\Interfaces\SurveyInterface;
use SamIT\LimeSurvey\JsonRpc\Concrete\Question;

/**
 * Class SerializeHelper
 * This class allows you to serialize any Survey object that implements the SurveyInterface, to native PHP arrays (and thus also json).
 *
 * @package SamIT\LimeSurvey\JsonRpc
 */
class SerializeHelper
{
    public static function gettersToArray($object)
    {
        if (!is_object($object)) {
            throw new \InvalidArgumentException('$object must be an object.');
        }

        $result = [];
        // Iterate over all getters.
        foreach(get_class_methods($object) as $method) {
            if (strncmp('get', $method, 3) == 0) {
                $reflector = new \ReflectionMethod($object, $method);
                if ($reflector->isPublic() && $reflector->getNumberOfParameters() == 0) {
                    $key = lcfirst(substr($method, 3));
                    $value = $object->$method();
                    if (is_array($value)) {
                        foreach($value as &$entry) {
                            if (is_object($entry)) {
                                $entry = static::gettersToArray($entry);
                            }
                        }
                    }
                    $result[$key] = is_object($value) ? static::gettersToArray($value) : $value;
                }
                // Special handling of subquestions.
                elseif ($object instanceOf Question && $method = 'getQuestions') {
                    for ($i = 0; $i < $object->getDimensions(); $i++) {
                        foreach($object->getQuestions($i) as $question) {
                            $result['questions'][$i][] = self::gettersToArray($question);
                        }
                    }

                }
            }
        }
        return $result;
    }
    public static function toArray(SurveyInterface $survey)
    {
        $result = static::gettersToArray($survey);
        if ($survey->getLanguage() == $survey->getDefaultLanguage()) {
            // Serialize other languages.
            $localized = [];
            foreach($survey->getLanguages() as $language)
            {
                if ($language != $survey->getLanguage()) {
                    $localized[$language] = $survey->getLocalized($language);
                }
            }
            $result['localized'] = $localized;
        }
        return $result;
    }
}