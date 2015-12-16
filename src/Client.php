<?php
namespace SamIT\LimeSurvey\JsonRpc;

use SamIT\LimeSurvey\Interfaces\ResponseInterface;
use SamIT\LimeSurvey\Interfaces\QuestionInterface;
use SamIT\LimeSurvey\Interfaces\TokenInterface;
use SamIT\LimeSurvey\Interfaces\WritableTokenInterface;
use SamIT\LimeSurvey\JsonRpc\Concrete\Answer;
use SamIT\LimeSurvey\JsonRpc\Concrete\Group;
use SamIT\LimeSurvey\JsonRpc\Concrete\Question;
use SamIT\LimeSurvey\JsonRpc\Concrete\Response;
use SamIT\LimeSurvey\JsonRpc\Concrete\SubQuestion;
use SamIT\LimeSurvey\JsonRpc\Concrete\Survey;
use SamIT\LimeSurvey\Interfaces\SurveyInterface;
use SamIT\LimeSurvey\JsonRpc\Concrete\Token;
use yii\helpers\ArrayHelper;

class Client
{
    /**
     * A cache that is only kept for the current request.
     * @var mixed[]
     */
    protected $requestCache = array();

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
        $this->password = $password;

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
                // Unset the password as soon as we have a session key.
                unset($this->password);
            }
        }

        if (!isset($this->sessionKey)) {
            throw new \Exception("Failed to obtain session key: {$response['status']}");
        }
        return $this->sessionKey;
    }

    protected function getCacheKey($function, array $args) {
        return  "LimeSurvey" . $function . md5(json_encode($args));
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
        $key = $this->getCacheKey($function, func_get_args());
        if (!array_key_exists($key, $this->requestCache)) {
//            echo "<pre>Calling $function with params: " . print_r($params, true) . "</pre>";
            $this->requestCache[$key] = call_user_func_array(array($this->client, $function), $params);
        }
        /**
         * @todo remove this when limeSurvey api starts sending proper error codes.
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
        $data = $this->getLanguageProperties($id, [
            'title',
            'description'
        ], $language);
        $data2 = $this->getSurveyProperties($id, [
            'language',
            'additional_languages'
        ], $language);
        $data['id'] = (int)$id;
        $data['language'] = $language;
        if (!empty($data2['additional_languages'])) {
            $data['languages'] = explode(' ', $data2['additional_languages']);
        }
        $data['languages'][] = $data2['language'];

        $data['language'] = $data2['language'];

        return new Survey($this, $data, [
            'language' => in_array($language, $data['languages']) ? $language : $data2['language']
        ]);
    }

    public function getGroups($surveyId, $language) {
        $result = [];
        // First filter by language.
        foreach ($this->listGroups($surveyId) as $data) {
            if ($data['language'] === $language) {
                $result[] = new Group($this, [
                    'id' => (int)$data['gid'],
                    'title' => $data['group_name'],
                    'description'=> $data['description']
                ], [
                    'language'=> $language,
                    'surveyId' => $surveyId
                ]);
            }

        }
        return $result;
    }

    public function getQuestions($surveyId, $groupId, $language) {
        $result = [];
        $subQuestions = [];
        // First create parent questions.
        $questions = array_filter($this->listQuestions($surveyId, null, $language), function ($question) use ($groupId) {
            return $question['gid'] == $groupId;
        });
        foreach ($questions as $data) {
            $answers = [];

            if ($data['parent_qid'] == 0) {

                $answers = $this->getAnswersForData($data, $language, 0);

                // Special handling for dual scale array question.
                if ($data['type'] == '1') {
                    $answers1 = $answers;
                    $answers2 = $this->getAnswersForData($data, $language, 1);
                    $answers = null;
                } elseif ($data['type'] == 'R') {
                    $answers1 = $answers;
                    $answers = null;
                }
                $result[(int)$data['qid']] = $question = new Question($this, [
                    'id' => $data['qid'],
                    'text' => $data['question'],
                    'title' => $data['title'],
                ], [
                    'language' => $language,
                    'surveyId' => $surveyId,
                    'answers' => $answers
                ]);

                // Special handling for array dual scale.
                if ($data['type'] == '1') {

                    new SubQuestion($this, [
                        'id' => $data['qid'] . '-1',
                        'text' => "Scale 1",
                        'title' => "Scale 1"
                    ], [
                        'dimension' => 1,
                        'answers' => $answers1,
                        'parent' => $question
                    ]);
                    new SubQuestion($this, [
                        'id' => (int) $data['qid'] . '-2',
                        'text' => "Scale 2",
                        'title' => "Scale 2",
                    ], [
                        'dimension' => 1,
                        'answers' => $answers2,
                        'parent' => $question
                    ]);

                } elseif ($data['type'] == 'R') {
                    // Special handling for ranking.
                    for($i = 1; $i <= count($answers1); $i++) {
                        new SubQuestion($this, [
                            'id' => (int) $data['qid'] . '-R' . $i,
                            'text' => "Rank $i",
                            'title' => "Rank $i",
                        ], [
                            'dimension' => 0,
                            'answers' => $answers1,
                            'parent' => $question
                        ]);
                    }

                }
           }
        }

        foreach($questions as $data) {
            if ($data['parent_qid'] != 0) {

                /** @var QuestionInterface $parent */
                $parent = $result[$data['parent_qid']];
                $sub = new SubQuestion($this, [
                    'id' => (int) $data['qid'],
                    'text' => $data['question'],
                    'title' => $data['title'],
                ], [
                    'language' => $language,
                    'surveyId' => $surveyId,
                    'parent' => $parent,
                    'dimension' => (int) $data['scale_id']
                ]);
            }
        }

        return array_values($result);
    }

    /**
     * Gets the answers for question data provided by the limeSurvey api.
     * @param array $data
     * @throws \Exception
     */
    private function getAnswersForData(array $data, $language, $scale = 0)
    {
        switch($data['type']) {
            case 'E': // array increase same decrease
                $answers = [
                    new Answer($this, [
                        'text' => 'Increase',
                        'code' => 'I'
                    ]),
                    new Answer($this, [
                        'text' => 'Same',
                        'code' => 'S'
                    ]),
                    new Answer($this, [
                        'text' => 'Decrease',
                        'code' => 'D'
                    ])
                ];
                break;
            case 'B': // array 10pt choice
                for ($i = 1; $i <= 10; $i++) {
                    $answers[] = new Answer($this, [
                        'text' => $i,
                        'code' => $i
                    ]);
                }
                break;
            case 'A': // array 5pt choice.
            case '5': // 5 point choice.
                for ($i = 1; $i <= 5; $i++) {
                    $answers[] = new Answer($this, [
                        'text' => $i,
                        'code' => $i
                    ]);
                }
                break;
            case '!': // List dropdown
            case 'L': // List radio
            case 'O': // List with comments.
            case 'F': // Array
            case 'H': // Array by column
                foreach ($this->getQuestionProperties($data['qid'], ['answeroptions'], $language)['answeroptions'] as $key => $answerData) {
                    $answers[] = new Answer($this, [
                        'text' => $answerData['answer'],
                        'code' => $key
                    ]);
                }
                break;
            case 'R': // Ranking
            case '1': // Array dual scale
                foreach ($this->getQuestionProperties($data['qid'], ['answeroptions'], $language)['answeroptions'] as $key => $answerData) {
                    if ($scale == $answerData['scale_id']) {
                        $answers[] = new Answer($this, [
                            'text' => $answerData['answer'],
                            'code' => $key
                        ]);
                    }
                }
                break;
            case 'D': // Date Time
            case '*': // Equation
            case ':': // Array numbers
            case '|': // File upload
            case ';': // Array texts
            case 'U': // Huge text
            case 'T': // Long text
            case 'K': // mULTIPLE Numerical
            case 'N': // Numerical
            case 'Q': // Multiple short texts
            case 'X': // Text display
            case 'S': // Short text
                $answers = null;
                break;
            case 'G': // Gender
                $answers = [
                    new Answer($this, [
                        'text' => 'Male',
                        'code' => 'M'
                    ]),
                    new Answer($this, [
                        'text' => 'Female',
                        'code' => 'F'
                    ])
                ];
                break;
            case 'C': // array yes no uncertain
                $answers = [
                    new Answer($this, [
                        'text' => 'Yes',
                        'code' => 'Y'
                    ]),
                    new Answer($this, [
                        'text' => 'No',
                        'code' => 'N'
                    ]),
                    new Answer($this, [
                        'text' => 'Uncertain',
                        'code' => 'U'
                    ])
                ];
                break;
            case 'P': // Multiple choice with comments
            case 'Y': // Yes / NO
            case 'M': // Multiple choice
                $answers = [
                    new Answer($this, [
                        'text' => 'Yes',
                        'code' => 'Y'
                    ]),
                    new Answer($this, [
                        'text' => 'No',
                        'code' => 'N'
                    ])
                ];
                break;
            case 'I': // Language switch
                $answers = [];
                foreach (explode(' ', implode(' ', $this->getSurveyProperties($data['sid'], [
                    'language',
                    'additional_languages'
                ], $language))) as $language) {
                    $answers[] = new Answer($this, [
                        'text' => $language,
                        'code' => $language
                    ]);
                }
                break;
            default:
                vdd($data);
                $answers = null;
        }
        return $answers;
    }

    /**
     * Create a new token.
     * @param int $surveyId The survey id.
     * @param array $participantData Key - value array for the token information.
     * @param boolean $createToken If false: don't create a token. This is automatically set to false if a token is supplied
     * in the $participantData array.
     * @return array|null The created token, or null if creation failed.
     */
    public function createToken($surveyId, array $tokenData, $generateToken = true)
    {
        $generateToken = $generateToken && !isset($tokenData['token']);
        $data = $this->executeRequest('add_participants', $surveyId, array($tokenData), $generateToken);
        if (isset($data['errors'])) {
            return null;
        }
        return $data;
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
        $key = __CLASS__ . __FUNCTION__ . serialize(func_get_args());
        foreach($properties as &$property) {
            if (strpos($property, 'surveyls_') !== 0) {
                $property = "surveyls_$property";
            }
        }
        if (false === $result = $this->cacheGet($key)) {
            $data = $this->executeRequest('get_language_properties', $surveyId, $properties, $language);

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

    public function getQuestionProperties($questionId, array $properties, $language = null)
    {
        $key = __CLASS__ . __FUNCTION__ . $questionId;
        if (false === $result = $this->cacheGet($key)) {
            $data = $this->executeRequest('get_question_properties', $questionId, $properties, $language);
            if (isset($data['status'])) {
                throw new \Exception($data['status']);
            }
            $result = $data;
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
        if (isset($result['status'])) {
            return [];
        }
        $this->cacheSet($key, $result, 3600);
    }
    return $result;
}

public function listQuestions($surveyId, $groupId, $language)
{
    $key = __CLASS__ . __FUNCTION__ . serialize(func_get_args());
    if (false === $result = $this->cacheGet($key)) {
        $result = $this->executeRequest('list_questions', $surveyId, $groupId, $language);
        if (isset($result['status'])) {
            return [];
        }
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


    /**
     * @param int $surveyId
     * @param int|null $limit
     * @param int|null $offset
     * @return ResponseInterface[]
     */
    public function getResponsesByToken($surveyId, $token)
    {
        $data = $this->executeRequest('export_responses_by_token', $surveyId, 'json', $token);
        $responses = [];
        if (is_string($data)) {

            foreach(json_decode(base64_decode($data), true)['responses'] as $responseData) {
                $responses[] = new Response($this, array_pop($responseData), [
                    'surveyId' => intval($surveyId)
                ]);
            }

        }
        return $responses;
    }

    /**
     * @param int $surveyId
     * @param int|null $limit
     * @param int|null $offset
     * @return ResponseInterface[]
     */
    public function getResponses($surveyId, $limit = null, $offset = null)
    {
        $result = json_decode(base64_decode($this->executeRequest('export_responses', $surveyId, 'json', null, 'all', 'code', 'short', $offset, $limit)), true);
        $responses = [];
        if (is_array($result) && isset($result['responses'])) {
            foreach($result['responses'] as $responseData) {
                $responses[] = new Response($this, array_pop($responseData), [
                    'surveyId' => intval($surveyId)
                ]);
            }
        }
        return $responses;
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
        return str_replace('/remotecontrol', '', $this->client);
    }

    public function getUrl($surveyId, array $params = [])
    {
        $baseUrl = str_replace('admin/remotecontrol', '', $this->client->getUrl());
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


    /**
     * @param int $surveyId The survey ID
     * @param string $token The token
     * @param int $attributeCount An upper bound for the custom attributes (we request them blindly, larger number cause larger requests).
     * @return WritableTokenInterface|null
     */
    public function getToken($surveyId, $token, $attributeCount = 20)
    {
        $tokens = $this->getTokens($surveyId, ['token' => $token], $attributeCount, 1);
        vd($token);
        vdd($tokens);
        if (!empty($tokens)) {
            return $tokens[0];
        }

    }

    public function getTokens($surveyId, array $attributesConditions = null, $attributeCount = 20, $limit = 10000)
    {
        $attributes = [
            'emailstatus',
            'token',
            'language',
            'sent',
            'completed',
            'usesleft',
            'validfrom',
            'validuntil',
            'remindersent',
            'remindercount'
        ];
        for ($i = 1; $i < $attributeCount; $i++) {
            $attributes[] = 'attribute_' . $i;
        }
        $data = $this->executeRequest('list_participants', $surveyId, 0, $limit, true, $attributes, $attributesConditions);
        $descriptions = $this->getTokenAttributeDescriptions($surveyId);
        $result = [];
        if (isset($data[0])) {
            foreach($data as $item) {
                $itemAttributes = [];
                foreach ($item as $key => $value) {

                    if (is_array($value)) {
                        $itemAttributes = array_merge($itemAttributes, $value);
                    } elseif (isset($descriptions[$key])) {
                        $itemAttributes['custom'][$descriptions[$key]['description']] = $value;
                    } else {
                        $itemAttributes[$key] = $value;
                    }
                }

                $result[] = new Token($this, $itemAttributes, [
                    'surveyId' => $surveyId
                ]);

            }
        }
        return $result;
    }

    /**
     * Updates the specified token using $attributes.
     * Will translate custom attributes to attribute_x, will ignore extra keys.
     * @param int $surveyId
     * @param int $tokenId
     * @param aray $attributes
     */
    public function updateToken($surveyId, $tokenId, array $attributes)
    {
        $map = array_flip(ArrayHelper::getColumn($this->getTokenAttributeDescriptions($surveyId), 'description'));

        $translated = [];
        foreach($attributes as $key => $value) {
            if (isset($map[$key])) {
                $translated[$map[$key]] = $value;
            } else {
                $translated[$key] = $value;
            }
        }


        $result = $this->executeRequest('set_participant_properties', $surveyId, $tokenId, $translated);
        // Clear any list_participants calls from the request cache.
        foreach($this->requestCache as $key => $value) {
            if (strpos($key, 'list_participants') !== false) {
                unset($this->requestCache[$key]);
            }
        }
        return !isset($result['status']);
    }

    public function getTokenAttributeDescriptions($surveyId)
    {
        $data = $this->getSurveyProperties($surveyId, [
            'attributedescriptions'
        ]);
        if (isset($data['status'])) {
            throw new \Exception($data['status']);
        }

        // Try json_decode first.
        if (null === $descriptions = json_decode($data['attributedescriptions'], true)) {
            throw new \Exception("This survey seems to store token attributes using PHP serialize. This is insecure and therefore not supported. Please update any of the custom token fields / attributes to have LimeSurvey automatically save it in the new format.");
        }
        return $descriptions;

    }
}