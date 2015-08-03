<?php

/**
 * Simple wrapper to consume the Google Translate API. 
 */

require_once('libs/api.utils.php');

class GoogleTranslateClient {

    private $url = 'https://www.googleapis.com/language/translate/v2';

    private $source;
    private $target;
    private $key;


    /**
     * [__construct description]
     * 
     * @param [type] $key            [description]
     * @param [type] $seedLanguage   [description]
     * @param [type] $targetLanguage [description]
     */
    public function __construct($key, $seedLanguage, $targetLanguage) {
        
        $this->source = $seedLanguage;
        $this->target = $targetLanguage;
        $this->key = $key;
    }


    /**
     * prepares the string to be translated
     * 
     * @param  [type] $string [description]
     * @return [type]         [description]
     */
    public function translate ($string) {

        // locates any strings that are not meant to be converted and wraps them in notranslate spans
        $prepared = Utils::isolateIgnored($string);

        // make the call
        return $this->call($prepared);
    }


    /**
     * calls the api 
     * 
     * @param  [type] $string [description]
     * @return [type]         [description]
     */
    private function call ($string) {

        $params = array(
            'q' => $string,
            'source' => $this->source,
            'target' => $this->target,
            'key' => $this->key,
            'format' => 'html'
        );

        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $this->url . '?' . http_build_query($params)); 
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $translated = curl_exec($curl); 
        $status     = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl); 

        // ok response
        if ($status == 200) {

            // decode the json
            $translated = json_decode($translated, true);

            // get the goods
            $translated = $translated['data']['translations'][0]['translatedText'];

            // remove any markup (notranslate spans in particular)
            $translated = strip_tags($translated);

        } else {

            // fail
            var_dump($translated);
            return $string;
        }

        return $translated;
    }
}
?>