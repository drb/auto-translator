<?php

// the source documents can get a bit big, so we allow a property _comment to comment the structure
// but we don't want this in the output, so they are removed.
define ('JSON_COMMENT', '_comment');

//
mb_internal_encoding("UTF-8");

// setup composer
require 'vendor/autoload.php';

// imports...
use Best\DotNotation;                               // https://github.com/dmeybohm/dot-notation
use Stichoza\GoogleTranslate\TranslateClient;       // https://github.com/Stichoza/Google-Translate-PHP

//base variables
$template           = false;                        // the source file to get all translations from
$output             = false;                        // the output file once all translations are done
$seedLanguage       = false;                        // the seed language denoted by two-character ISO 3166-1 alpha-2 code
$targetLanguages    = false;                        // the languages we need denoted by two-character ISO 3166-1 alpha-2 codes (split by commas)
$options            = false; 

$expandNamespace    = false;                        // creates nested objects in the JSON, using dot syntax to denote nesting
$verbose            = false;                        // print the output to the console
$stripComments      = true;                         // remove any custom comments from the source file (default is on)
$jsonOutput         = null;                         // the output

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
    $opts .= 'pvec';

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
        $jsonOutput = JSON_PRETTY_PRINT;
    }

    // keep comments
    if (array_key_exists('c', $options)) {
        $stripComments = false;
    }
}

// check in/out params
if (!$template || !$output) {
    print ("\nYou need to define an input and output file.\n");
    exit(0);
}

if ($template === $output) {
    print ("\nOutput is the same file as input - this is a bad idea.\n");
    exit(0);
}

// check params
if (!$seedLanguage) {
    print ("\nNo seed language was defined.\n");
    exit(0);
}

// did any target languages get set?
if (!$targetLanguages || sizeof($targetLanguages) === 0) {
    print ("\nNo target languages were defined.\n");
    exit(0);
}

// check the source template exists
if (!file_exists($template)) {
    print ("\nSource file does not exist\n");
    exit(0);   
}

// load the template data (the text strings in the target language)
$string = file_get_contents($template);

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

    // remove the temporary _comments properties if they exist - we don't need these translating
    if (array_key_exists(JSON_COMMENT, $json[$seedLanguage]) && $stripComments) {
        unset($json[$seedLanguage][JSON_COMMENT]);
    }

    // get the strings in the source language we want to translate
    $seedStrings = $json[$seedLanguage];

    // loop over each target language, hit the translation API
    foreach ($targetLanguages as $lang) {

        // setup api
        $tr = new TranslateClient($seedLanguage, $lang);

        // create a new object if it's not there already
        if (!array_key_exists($lang, $json)) {
            $json[$lang] = array();    
        }

        // hit each string
        foreach ($seedStrings as $key => $value) {

            try {

                // test that the string should be translated - we can prevent strings from being 
                // converted by preceding the original value with a dollar sign i.e. "foo": "$I should not be translated"
                if (preg_match('#^\$#i', $value) === 1) {

                    // just use the original string (don't translate)
                    $translated = mb_substr($value, 1, mb_strlen($value), "UTF-8");
                } else {

                    // hit the api - the string should be translated
                    $translated = $tr->translate($value);    
                }

                // re-assign the property value
                $json[$lang][$key] = $translated;
                
            } catch (Exception $e) {
                echo (sprintf('Failed to get translation: %s', $e->getMessage()));
            }
        }
    }

    // expand the flat keys into sub arrays if required
    if ($expandNamespace === true) {
        foreach(array_merge(array($seedLanguage), $targetLanguages) as $lang) {
            $json[$lang] = DotNotation::expand($json[$lang]);
        }
    }
} else {
    print(sprintf("\nThere is no key set for %s in the source JSON.\n", $seedLanguage));
    exit(0);
}

// write the translated JSON back to disk
try {
    $fp = fopen($output, 'w');
    fwrite($fp, json_encode($json, $jsonOutput));
    fclose($fp);
} catch (Exception $e) {
    echo (sprintf('\nError writing output file %s. Error found: %s', $output, $e->getMessage()));
}

// output to console if verbose flag is set
if ($verbose) {
    print_r($json);    
}
?>