<?php
namespace SamIT\LimeSurvey\JsonRpc;

use Concrete\Survey;
use SamIT\LimeSurvey\Interfaces\SurveyInterface;

class LimeSurvey
{
    /**
     * A cache that is only kept for the current request.
     * @var mixed[]
     */
    protected $requestCache = array();

    public $url;

    public $username;

    public $password;

    private $sessionKey;

    /**
     *3
     * @var JsonRpcClient
     */
    protected $client;

    private $cache;

    public function __construct(JsonRpcClient $client, $username, $password)
    {
        $this->client = $client;
        $this->username = $username;
        $this->password = $this->password;
    }


    /**
     * Sets caching functions that this component may use for caching.
     * Note that this is only used between different requests, within 1 request all requests are cached by default.
     * @param \Closure $setter Signature: function($key, $value, $duration)
     * @param \Closure $getter Signature: function($key) should return false if item is not set.
     */
    public function setCache(\Closure $setter, \Closure $getter)
    {
        $this->cache['set'] = $setter;
        $this->cache['get'] = $getter;
    }
    protected function getSessionKey()
    {
        if (!isset($this->sessionKey)) {
            $response = $this->client->get_session_key($this->username, $this->password);
            if (is_string($response)) {
                $this->sessionKey = $response;
            }
        }

        if (!isset($this->sessionKey)) {
            throw new \Exception("Failed to obtain session key: {$response['status']}");
        }
        return $this->_sessionKey;
    }

    /**
     * Send a request using JsonRPC
     * @param $function
     * @return mixed
     * @throws \Exception
     */
    protected function executeRequest($function)
    {
        $params = func_get_args();
        $params[0] = $this->getSessionKey();
        $key = "LimeSurvey" . $function . md5(json_encode(func_get_args()));
        if (!array_key_exists($key, $this->requestCache)) {
            $this->requestCache[$key] = call_user_func_array(array($this->client, $function), $params);
        }
        /**
         * @todo remove this when limesurvey api starts sending proper error codes.
         */
        if (isset($this->requestCache[$key]['status']) && $this->requestCache[$key]['status'] == "Invalid session key")
        {
            unset($this->requestCache[$key]);
            $this->requestCache[$key] = call_user_func_array(array($this, 'executeRequest'), func_get_args());
        }
        return $this->requestCache[$key];
    }

    /**
     * @param int $id
     * @param string $language The language in which to get the survey, use null for the surveys' default language.
     * @return SurveyInterface
     */
    public function getSurvey($id, $language = null)
    {
        $result = new Survey($this);
        $data = $this->getLanguageProperties($id, $language);
        vdd($data);

    }

    /**
     * Create a new token.
     * @param int $surveyId The survey id.
     * @param array $participantData Key - value array for the token information.
     * @param boolean $createToken If false: don't create a token. This is automatically set to false if a token is supplied
     * in the $participantData array.
     * @return mixed
     */
    public function createToken($surveyId, array $tokenData, $generateToken = true)
    {
        $generateToken = $generateToken && !isset($tokenData['token']);
        return $this->executeRequest('add_participants', $surveyId, array($tokenData), $generateToken);
    }


    protected function cacheGet($key)
    {
        if ($this->cache['get']) {
            return $this->cache['get']($key);
        } else {
            return false;
        }
    }

    protected function cacheSet($key, $value, $duration)
    {
        if ($this->cache['set']) {
            $this->cache['set']($key, $value, $duration);
        }
    }



    public function getLanguageProperties($surveyId, array $properties, $language = null)
    {
        $key = __CLASS__ . __FUNCTION__ . $surveyId;
        foreach($properties as &$property) {
            if (strpos($property, 'surveyls_') !== 0) {
                $property = "surveyls_$property";
            }
        }
        if (false === $result = $this->cacheGet($key)) {
            $data = $this->executeRequest('get_language_properties', $surveyId, $properties);
            if (isset($data['status'])) {
                throw new \Exception($data['status']);
            }
            $result = [];
            foreach ($data as $key => $value) {
                $result[str_replace('surveyls_', '', $key)] = $value;
            }
            $this->cacheSet($key, $result, 3600);
        }
        return $result;
    }


    public function getSurveyProperties($id, array $properties)
    {
        $key = __CLASS__ . __FUNCTION__ . $id;
        if (false && false !== $cached = $this->cacheGet($key))
        {
            return $cached;
        }
        else
        {
            try {
                $result = $this->executeRequest('get_survey_properties', $id, $properties);
                $this->cacheSet($key, $result, 3600);
                if (isset($result['status']) && $result['status'] == "No surveys found")
                {
                    return [];
                }
            }
            catch (Exception $e)
            {
                return array();
            }
            return $result;
        }
    }

    public function listSurveys($user = null)
    {
        $key = __CLASS__ . __FUNCTION__ . (isset($user) ? $user : "");
        if (false !== $cached = $this->cacheGet($key))
        {
            return $cached;
        }
        else
        {
            try {
                $result = $this->executeRequest('list_surveys', $user);
                $this->cacheSet($key, $result, 3600);
                if (isset($result['status']) && $result['status'] == "No surveys found")
                {
                    return array();
                }
            }
            catch (Exception $e)
            {
                return array();
            }
            return $result;
        }
    }


public function listGroups($surveyId)
{
    $key = __CLASS__ . __FUNCTION__ . (isset($user) ? $user : "");
    if (false === $result = $this->cacheGet($key)) {
        $result = $this->executeRequest('list_groups', $surveyId);
        $this->cacheSet($key, $result, 3600);
    }
    return $result;
}

public function listUsers()
{
    $key = __CLASS__ . __FUNCTION__;
    if (false === $result = $this->cacheGet($key)) {
        $result = $this->executeRequest('list_users');
        $this->cacheSet($key, $result, 3600);
    }
    return $result;
}



    public function getResponseByToken($surveyId, $token)
    {
        $jsonResponse = $this->executeRequest('export_responses_by_token', $surveyId, 'json', $token);
        if (is_array($jsonResponse))
        {
            return $jsonResponse;
        }
        else
        {
            $result = json_decode(base64_decode($jsonResponse), 'assoc');
            return reset($result['responses'][0]);
        }
    }

    public function getResponses($surveyId)
    {
            return $this->executeRequest('export_responses', $surveyId, 'json');
    }

    public function getTitle($surveyId)
    {
        $key = "LimeSurvey.getTitle." . $surveyId;
        if (false === $result = $this->cacheGet($key)) {
            foreach ($this->listSurveys() as $survey) {
                if (isset($survey['sid']) && $survey['sid'] == $surveyId) {
                    $this->cacheSet($survey['surveyls_title'], 3600);
                    $result = $survey['surveyls_title'];
                }
            }
        }
        return $result;
    }

    public function getAdminUrl()
    {
        return str_replace('/remotecontrol', '', $this->url);
    }

    public function getUrl($surveyId, array $params = null)
    {
        $baseUrl = str_replace('admin/remotecontrol', '', $this->url);
        $url = $baseUrl . "survey/index/sid/$surveyId";
        if (isset($params['lang'])) {
            $params['lang'] = $this->normalizeLanguage($params['lang']);
        }
        foreach ($params as $key => $value)
        {
            $url .= "/$key/$value";
        }
        return $url;
    }

    protected function normalizeLanguage($language)
    {
        $parts = explode('_', $language);
        if (count($parts) == 2) {
            return $parts[0] . "-" . ucfirst($parts[1]);
        }
        return $language;
    }

}
?>
