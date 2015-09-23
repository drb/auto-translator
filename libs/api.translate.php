<?php

/**
 * Simple wrapper to consume the Google Translate API. 
 */

require_once('libs/api.utils.php');

define ('CACHE_PATH', 'cache.tmp');

class GoogleTranslateClient {

    private $url = 'https://www.googleapis.com/language/translate/v2';

    private $source;
    private $target;
    private $key;
    private $resourceKey;
    private $useCache = true;
    private $cacheDict = array();
    private $preferredEncoding = 'utf-8';  

    private $stats = array(
        'total'=>0,
        'new'=>0,
        'cache'=>0
    );


    /**
     * [__construct description]
     * 
     * @param [type] $key            [description]
     * @param [type] $seedLanguage   [description]
     * @param [type] $targetLanguage [description]
     */
    public function __construct($key, $seedLanguage, $targetLanguage, $ignoreCache=false) {
        
        $this->source = $seedLanguage;
        $this->target = $targetLanguage;
        $this->key = $key;

        // 
        $this->useCache = !$ignoreCache;

        if ($this->useCache) {
            //
            if (file_exists(CACHE_PATH)) {

                // cache dictionary
                $contents = file_get_contents(CACHE_PATH);  

                if (strlen($contents) > 0) {

                    $this->cacheDict = Utils::parseJSON($contents);    
                }
            }
        }
    }


    /**
     * prepares the string to be translated
     * 
     * @param  [type] $string [description]
     * @return [type]         [description]
     */
    public function translate ($string) {

        $this->stats['total']++;

        // locates any strings that are not meant to be converted and wraps them in notranslate spans
        $prepared = Utils::isolateIgnored($string);

        // make the call
        return $this->call($prepared);
    }


    /**
    * sets the key this string will be logged against for internal cache 
    * 
    * @param [type] $keyId [description]
    */
    public function setKey ($keyId) {

        $this->resourceKey = $keyId;

        return $this;
    }



    /**
     * calls the api 
     * 
     * @param  [type] $string [description]
     * @return [type]         [description]
     */
    private function call ($string) {

        $stringHash     = md5($string);
        $cachedValue    = false;

        if ($this->useCache) {

            if (array_key_exists($this->resourceKey, $this->cacheDict)) {

                if (array_key_exists($this->target, $this->cacheDict[$this->resourceKey])) {

                    // do we already have this value in the dictionary, and does the string has match?
                    if ($this->cacheDict[$this->resourceKey]['hash'] === $stringHash) {

                        // return the cached value
                        $cachedValue = $this->cacheDict[$this->resourceKey][$this->target];
                    } else {
                        $this->cacheDict[$this->resourceKey] = array();
                    }
                }
            }
        }

        // already hit this string, so just return from cache
        if ($cachedValue !== false) {

            // update stats
            $this->stats['cache']++;

            return $cachedValue;

        } else {

            // update stats
            $this->stats['new']++;
            //return Utils::fixTokenised(Utils::processString("test %[tokenised.string.hete] and %     [weird.broken.string]"));

            $params = array(
                'q' => $string,
                'source' => $this->source,
                'target' => $this->target,
                'key' => $this->key,
                'format' => 'text'
            );

            $curl = curl_init();

            curl_setopt($curl, CURLOPT_URL, $this->url . '?' . http_build_query($params)); 
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
            $translated = curl_exec($curl); 
            $status     = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl); 

            // ok response
            if ($status == 200) {

                // detect the inbound encoding
                $encoding = mb_detect_encoding($translated);

                // if encoding is not utf, make it so
                if (strtolower($encoding) != $this->preferredEncoding) {
                    $translated = iconv($encoding, $this->preferredEncoding, $translated);
                }

                // decode the json
                $translated = json_decode($translated, true);

                // get the goods
                $translated = $translated['data']['translations'][0]['translatedText'];

                // rewrite the cache keys 
                if ($this->useCache) {

                    // create a new key if it doesn't exist
                    if (!array_key_exists($this->resourceKey, $this->cacheDict)) {

                        // create the new key
                        $this->cacheDict[$this->resourceKey] = array();
                    }

                    // update the hash
                    $this->cacheDict[$this->resourceKey]['hash'] = $stringHash;
                    $this->cacheDict[$this->resourceKey]['en'] = $string;

                    // add to the translated key (sanitise first)
                    $this->cacheDict[$this->resourceKey][$this->target] = Utils::processString($translated);
                }

            } else {

                // fail
                //var_dump($translated);
                print (sprintf("[error][%s > %s][%s] %s", $this->source, $this->target, $status, $translated));

                // return the original string
                $translated = $string;
            }

            return Utils::processString($translated);
        }
    }


    /**
     * save
     *
     * dumps the file to disk
     * 
     * @return [type] [description]
     */
    public function save () {

        if ($this->useCache) {
            try {
                $fp = fopen(CACHE_PATH, 'w');
                fwrite($fp, json_encode($this->cacheDict, JSON_PRETTY_PRINT));
                fclose($fp);
            } catch (Exception $e) {
                echo (sprintf('Error writing cacheDict file %s. Error found: %s', $output, $e->getMessage()));
            }
        }
    }


    /**
     * printStats
     *
     * writes out the usage stats from the local cache
     * 
     * @return [type] [description]
     */
    public function printStats () {

        print(sprintf("Finished converting [%s resources][%s from cache] %s > %s\n", $this->stats['total'], $this->stats['cache'], strtoupper($this->source), strtoupper($this->target)));
    }
}
?>