<?php
class GoogleTranslateClient {

    private $url = 'https://www.googleapis.com/language/translate/v2';

    private $source;
    private $target;
    private $key;

    public function __construct($key, $seedLanguage, $targetLanguage) {
        
        $this->source = $seedLanguage;
        $this->target = $targetLanguage;
        $this->key = $key;
    }


    public function translate ($string) {

        return $this->call($string);

    }

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

        } else {

            // fail
            var_dump($translated);
            return $string;

        }

        return $translated;
    }
}
?>