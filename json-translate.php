<?php

/**
 * Needs a mega re-factor to make this code maintainable and unit testable.
 * The project started as a tiny procedural script & quickly got big!
 */

// the source documents can get a bit big, so we allow a property _comment to comment the structure
// but we don't want this in the output, so they are removed.
define ('JSON_COMMENT',     '_comment');
define ('JSON_INCLUDE',     '_include');
define ('INI_PATH',         'creds.ini');
define ('INCLUDES_PATH',    'includes/');

// set encoding type to utf-8 by default
mb_internal_encoding("UTF-8");

// setup composer
require_once 'vendor/autoload.php';

// google api abstract
require_once('libs/api.translate.php');
require_once('libs/api.utils.php');

// imports...
use Best\DotNotation;                               // https://github.com/dmeybohm/dot-notation
use Stichoza\GoogleTranslate\TranslateClient;       // https://github.com/Stichoza/Google-Translate-PHP

//base variables
$template           = false;                        // the source file to get all translations from
$output             = false;                        // the output file once all translations are done
$seedLanguage       = false;                        // the seed language denoted by two-character ISO 3166-1 alpha-2 code
$targetLanguages    = false;                        // the languages we need denoted by two-character ISO 3166-1 alpha-2 codes (split by commas)
$options            = false;                        // parsed options coming from the CLI

$expandNamespace    = false;                        // creates nested objects in the JSON, using dot syntax to denote nesting
$verbose            = false;                        // print the output to the console
$stripComments      = true;                         // remove any custom comments from the source file (default is on)
$enforceUpperFirst  = true;                         // attempt to enforce upper case letters first when the original text has upper
$authEnabled        = false;                        // uses the google translate API directly using a real API key - key needs to bet set in file creds.ini 
$authCredentials    = false;                        // if auth is enabled, the api key is held here

$jsonOutput         = array();                      // the output
$jsonOutputProps    = null;                         // the output properties

// cli options
if (sizeof($argv) > 1) {

    // short options 
    $opts = '';

    // seed language -s
    $opts .= 's:';

    // locales to translate to -l
    $opts .= 'l:';

    // input path -i
    $opts .= 'i:';

    // output path -o
    $opts .= 'o:';

    // optional params (-p pretty print, -v verbose, -e expand namespaces)
    $opts .= 'pveca';

    // parse the options
    $options = getopt($opts);

    // get the input json
    if (array_key_exists('i', $options)) {
        $template = $options['i'];
    }

    // get the output json
    if (array_key_exists('o', $options)) {
        $output = $options['o'];
    }

    // re-assign the seed language
    if (array_key_exists('s', $options)) {
        $seedLanguage = $options['s'];
    }
    
    // set the target languages
    if (array_key_exists('l', $options)) {
        $targetLanguages = explode(',', $options['l']);
    }

    // expand namespaces
    if (array_key_exists('e', $options)) {
        $expandNamespace = true;
    }

    // print output to console
    if (array_key_exists('v', $options)) {
        $verbose = true;
    }

    // pretty print the json
    if (array_key_exists('p', $options)) {
        $jsonOutputProps = JSON_PRETTY_PRINT;
    }

    // keep comments
    if (array_key_exists('c', $options)) {
        $stripComments = false;
    }

    //
    if (array_key_exists('a', $options)) {
        $authEnabled = true;
    }
}

// check in/out params
if (!$template || !$output) {
    print ("You need to define an input and output file.\n");
    exit(0);
}

if ($template === $output) {
    print ("Output is the same file as input - this is a bad idea.\n");
    exit(0);
}

// check params
if (!$seedLanguage) {
    print ("No seed language was defined.\n");
    exit(0);
}

// did any target languages get set?
if (!$targetLanguages || sizeof($targetLanguages) === 0) {
    print ("No target languages were defined.\n");
    exit(0);
}

// check the source template exists
if (!file_exists($template)) {
    print ("Source file does not exist\n");
    exit(0);   
}

// auth has been requested 
if ($authEnabled) {
    
    if (file_exists(INI_PATH)) {

        // parse the config file
        $authCredentials = parse_ini_file(INI_PATH, true);

        if (array_key_exists('google_api', $authCredentials) && array_key_exists('key', $authCredentials['google_api'])) {
            // all good
        } else {
            print (sprintf("Key is missing from %s\n", INI_PATH));
            exit(0);    
        }

    } else {
        print (sprintf("Auth parameter was passed but there is no %s file", INI_PATH));
        exit(0);
    }
}

// load the template data (the text strings in the target language)
$string = file_get_contents($template);

// locate any includes
preg_match_all('/\"_include\"\:([ ]+)\"(.*)\"/', $string, $includes, PREG_SET_ORDER);

// were any includes found?
if (sizeof($includes) > 0) {

    $dir = pathinfo($template)['dirname'];

    // loop the found includes
    foreach($includes as $include) {

        // remove falsy values from the array
        $include = array_values(array_filter($include, 'trim'));

        // grab the directory context of the the source docs
        $pathInfo = pathinfo($template);

        // path to file
        $markup = $include[0];
        $path   = $include[1];

        $path = $pathInfo['dirname'] . '/' . INCLUDES_PATH . $path;

        // does it exist?
        if (file_exists($path)) {
            // put it on the output
            $includeContent = file_get_contents($path);

            // de-json the content (we only want the keys)
            $includeContent = trim($includeContent, "{}");

            // update the base content with the content from the include
            $string = str_replace($markup, $includeContent, $string);
        } else {

            // throw exception when the include is missing
            throw new Exception(sprintf("Missing include file found at %s", $path));
        }
    }
}

// normalise the string after injecting any includes
$string = str_replace(array("\r", "\n"), "", $string);

// remove all none single spaces
$string = preg_replace('!\s+!', ' ', $string);

// remove all accidental double commas
$string = preg_replace("/([!?,.])+/", "\\1", $string);

// relpace any lines terminated with commas when the property is the last one in the object
$string = str_replace(", }", " }", $string);

// parse the json
$json   = json_decode($string, true);

// trap error when parsing the json
switch (json_last_error()) {
    case JSON_ERROR_DEPTH:
    case JSON_ERROR_STATE_MISMATCH:
    case JSON_ERROR_CTRL_CHAR:
    case JSON_ERROR_SYNTAX:
    case JSON_ERROR_UTF8:
        print(sprintf('Cannot load target JSON document: %s%s', json_last_error_msg(), "\n"));
        exit(0);
    break;
}


// only continue if the seed langauge exists
if (array_key_exists($seedLanguage, $json)) {

    // remove the include tag
    if (array_key_exists(JSON_INCLUDE, $json[$seedLanguage])) {

        // remove the include
        unset($json[$seedLanguage][JSON_INCLUDE]);
    }

    // remove the temporary _comments properties if they exist - we don't need these translating
    if (array_key_exists(JSON_COMMENT, $json[$seedLanguage]) && $stripComments) {

        // remove the comments
        unset($json[$seedLanguage][JSON_COMMENT]);
    }

    // get the strings in the source language we want to translate
    $seedStrings = $json[$seedLanguage];

    // place the target strings into the output object
    array_unshift($targetLanguages, $seedLanguage);

    // loop over each target language, hit the translation API
    foreach ($targetLanguages as $lang) {

        // setup api
        if (!$authEnabled) {

            // use api via backdoor
            $tr = new TranslateClient($seedLanguage, $lang);

        } else {

            // use offical api with keys
            $tr = new GoogleTranslateClient(
                $authCredentials['google_api']['key'],
                $seedLanguage,
                $lang
            );
        }
        
        // create a new object if it's not there already
        if (!array_key_exists($lang, $jsonOutput)) {
            $jsonOutput[$lang] = array();    
        }

        // hit each string
        foreach ($seedStrings as $key=>$value) {

            // 
            $stringKey = $key;

            try {

                // test that the string should be translated - we can prevent entire strings from being 
                // converted by preceding the key with a dollar sign i.e. "$foo": "I should not be translated"
                if (preg_match('#^\$#i', $key) === 1) {

                    // just use the original string (don't translate)
                    $translated = $value;

                    // remove the dollar key
                    $stringKey = mb_substr($key, 1, mb_strlen($key), "UTF-8");

                } else {

                    // if the target language is the same as the translation language, simply write it back into the object
                    if ($seedLanguage === $lang) {

                        $translated = strip_tags(Utils::isolateIgnored($value));
                        
                    } else {

                        // hit the api - the string should be translated
                        $translated = $tr->translate($value);

                        // ensure that the translated string observes the source string's capitalisation,
                        // if that is the case
                        if ($enforceUpperFirst) {

                            // is the source string's first character upper case?
                            if ($value && preg_match('#^\p{Lu}#u', $value)) {

                                // ensure the output is upper too
                                $fc = mb_strtoupper(mb_substr($translated, 0, 1));
                                $translated = $fc.mb_substr($translated, 1);
                            }
                        }
                    }
                }

                // re-assign the property value
                $jsonOutput[$lang][$stringKey] = $translated;
            
            //
            } catch (Exception $e) {
                echo (sprintf('Failed to get translation: %s', $e->getMessage()));
            }
        }
    }

    // expand the flat keys into sub arrays if required
    if ($expandNamespace === true) {

        // hit each item and expand them
        foreach(array_merge(array($seedLanguage), $targetLanguages) as $lang) {
            $jsonOutput[$lang] = DotNotation::expand($jsonOutput[$lang]);
        }
    }



} else {
    print(sprintf("There is no key set for %s in the source JSON.\n", $seedLanguage));
    exit(0);
}

// write the translated JSON back to disk
try {
    $fp = fopen($output, 'w');
    fwrite($fp, json_encode($jsonOutput, $jsonOutputProps));
    fclose($fp);
} catch (Exception $e) {
    echo (sprintf('\nError writing output file %s. Error found: %s', $output, $e->getMessage()));
}

// output to console if verbose flag is set
if ($verbose) {
   print_r($jsonOutput);    
}
?>