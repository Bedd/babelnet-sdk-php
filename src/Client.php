<?php
namespace Bedd\BabelNet;

/**
 * Client for the BabelNet HTTP Api
 * 
 * @see http://babelnet.org/guide
 */
class Client
{
    /**
     * API Key
     * 
     * @var string
     */
    private $api_key = '';
    
    /**
     * Base URL
     * 
     * @var string
     */
    private $base_url = 'https://babelnet.io/v4/';
    
    /**
     * @var array
     */
    private $defaultParams = [];
    
    /**
     * Constructor
     * 
     * @param string $api_key
     * @param array $defaultParams
     */
    public function __construct($api_key, array $defaultParams = [])
    {
        $this->api_key = $api_key;
        $this->defaultParams = $defaultParams;
    }

    /**
     * Executes an api call
     * @param string $service
     * @param array $params
     * @throws \Exception
     * @return mixed
     */
    private function exec($service, $params = array())
    {
        //add key
        $params['key'] = $this->api_key;
        //make request
        $url = $this->base_url.$service.'?'.http_build_query($params);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = json_decode(curl_exec($ch), true);
        $info = curl_getinfo($ch);
        if ($info['http_code'] !== 200) {
            throw new \Exception($response['message'], $info['http_code']);
        }
        curl_close($ch);
        return $response;
    }
    
    /**
     * Returns a param list from a method-reflection and his params
     * 
     * @param string $method
     * @param array $args
     * @return array
     */
    private function getParamsByArguments($method, $args)
    {
        $params = [];
        $m = new \ReflectionMethod($method);
        foreach ($m->getParameters() as $param) {
            $value = isset($args[$param->getPosition()]) ? $args[$param->getPosition()] : null;
            $name = $param->getName();
            if ($value === null && $param->isOptional()) {
                if ($param->isDefaultValueAvailable() && ($new_value = $param->getDefaultValue()) !== null) {
                    $value = $new_value;
                } else if (isset($this->defaultParams[$name])) {
                    $value = $this->defaultParams[$name];
                }
            }
            if ($value === null) {
                continue;
            }
            $params[$name] = $value;
        }
        return $params;
    }
    
    /**
     * @see http://babelnet.org/guide#RetrieveBabelNetversion
     * @throws \Exception
     * @return string
     */
    public function getVersion()
    {
        $res = $this->exec('getVersion');
        return isset($res['version']) ? $res['version'] : false;
    }
    
    /**
     * @see http://babelnet.org/guide#RetrievetheIDsoftheBabelsynsets(concepts)denotedbyagivenword
     * @param string $word
     * @param string $lang
     * @param string $filterLangs
     * @param string $pos
     * @param string $source
     * @param string $normalizer
     * @return string[]
     */
    public function getSynsetIds($word, $lang = null, $filterLangs = null, $pos = null, $source = null, $normalizer = null)
    {
        return array_column($this->exec('getSynsetIds', $this->getParamsByArguments(__METHOD__, func_get_args())), 'id');
    }
    
    /**
     * @see http://babelnet.org/guide#Retrievetheinformationofagivensynset
     * @param string $id
     * @param string $filterLangs
     * @return array
     */
    public function getSynsetById($id, $filterLangs = null) {
        return $this->exec('getSynset', $this->getParamsByArguments(__METHOD__, func_get_args()));
    }
    
    /**
     * @see http://babelnet.org/guide#Retrievethesensesofagivenword
     * @param string $word
     * @param string $lang
     * @param string $filterLangs
     * @param string $pos
     * @param string $source
     * @param string $normalizer
     * @return array
     */
    public function getSenses($word, $lang = null, $filterLangs = null, $pos = null, $source = null, $normalizer = null)
    {
        return $this->exec('getSenses', $this->getParamsByArguments(__METHOD__, func_get_args()));
    }
    
    /**
     * @see http://babelnet.org/guide#RetrieveedgesofagivenBabelNetsynset6
     * @param string $id
     * @return array
     */
    public function getEdges($id)
    {
        return $this->exec('getEdges', $this->getParamsByArguments(__METHOD__, func_get_args()));
    }
}
