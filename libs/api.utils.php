<?php

class Utils {
    
    /**
     * isolateIgnored
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

        return self::isolateTokens($string);
    }


    /**
     * isolateTokens
     *
     * protects any tokens found in the resources i.e. this is a sentence about a company called %[companyName].
     * 
     * @param  [type] $string [description]
     * @return [type]         [description]
     */
    public static function isolateTokens ($string) {

        preg_match_all('/\%\[(.*?)\]/', $string, $matches, PREG_SET_ORDER);

        if ($matches && !empty($matches) && !empty($matches[0])) {

            foreach ($matches as $key => $value) {
                $string = str_replace($value[0], "<span class='notranslate'>" . $value[0] . "</span>", $string);
            }
        }

        return $string;
    }



    /**
     * processString
     * 
     * removes markup, multiple spaces and whitespace from the translated string. also tries to preserve tokenised
     * strings that may have got mangled
     * 
     * @param  [type] $string [description]
     * @return [type]         [description]
     */
    public static function processString ($string) {

        $string = self::fixTokenised($string);

        return trim(strip_tags($string, '<a><strong><em>'));
    }


     /**
     * fixTokenised
     * 
     * attempts to fix instances where the translation API has mangled the tokenised strings - they shuold look like
     * %[this.is.a.string] but can end up like % [this.is.a.string ]
     * 
     * @param  [type] $string [description]
     * @return [type]         [description]
     */
    public static function fixTokenised ($string) {

        preg_match_all('/\%(\s+)?\[(.*?)\]/', $string, $matches, PREG_SET_ORDER);

        if ($matches && !empty($matches) && !empty($matches[0])) {

            foreach ($matches as $key => $value) {
                $string = str_replace($value[0], trim(preg_replace('!\s+!', '', $value[0])), $string);
            }
        }

        return $string;
    }


    /**
     * validateJSON
     *
     * does a quick parse and catches the json errors if invlaid
     * returns true if valid
     * 
     * @param  [type] $json [description]
     * @return [type]       [description]
     */
    public static function validateJSON ($string) {

        // parse the json
        $json = json_decode($string, true);

        // trap error when parsing the json
        switch (json_last_error()) {
            case JSON_ERROR_DEPTH:
            case JSON_ERROR_STATE_MISMATCH:
            case JSON_ERROR_CTRL_CHAR:
            case JSON_ERROR_SYNTAX:
            case JSON_ERROR_UTF8:
                return json_last_error_msg();
            break;
        }

        return true;
    }
}
?>