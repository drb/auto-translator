<?php

class Utils {
    
    /**
     * [isolateIgnored description]
     *
     * finds any strings wrapped in $() tags - these should be ignored by the service
     *
     * i.e. I should be translated $(but I want to stay in the original language). 
     * 
     * will be sent to the service as 'I should be translated <span class="notranslate">ut I want to stay in the original language</span>.'
     * 
     * @param  [type] $string [description]
     * @return [type]         [description]
     */
    public static function isolateIgnored ($string) {

        preg_match_all('/\$\((.*?)\)/', $string, $matches, PREG_SET_ORDER);

        if ($matches && !empty($matches) && !empty($matches[0])) {

            foreach ($matches as $key => $value) {
                $string = str_replace($value[0], "<span class='notranslate'>" . $value[1] . "</span>", $string);
            }
        }

        return $string;
    }



    /**
     * removes markup and whitespace from the translated string
     * 
     * @param  [type] $string [description]
     * @return [type]         [description]
     */
    public static function processString ($string) {

        return trim(strip_tags($string, '<a><strong><em>'));
    }
}
?>