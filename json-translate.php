<?php

// setup composer
require 'vendor/autoload.php';

// imports...
use Best\DotNotation;                               // https://github.com/dmeybohm/dot-notation
use Stichoza\GoogleTranslate\TranslateClient;       // https://github.com/Stichoza/Google-Translate-PHP


//base variables
$template           = false;                        // the source file to get all translations from
$output             = false;                        // the output file once all translations are done
$seedLanguage       = false;                        // the seed language
$targetLanguages    = false;                        // the languages we need
$options            = false; 

$expandNamespace    = false;
$verbose            = false; 
$jsonOutput         = null; 

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
    $opts .= 'pve';

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
}

// check in/out params
if (!$template || !$output) {
    print ("\nYou need to define an input and output file.\n");
    exit(0);
}

// check params
if (!$seedLanguage) {
    print ("\nNo seed language was defined.\n");
    exit(0);
}

// did any target languages get set?
if (sizeof($targetLanguages) === 0) {
    print ("\nNo target languages were defined.\n");
    exit(0);
}

// load the template data (the text strings in the target language)
$string = file_get_contents($template);
$json   = json_decode($string, true);

// only continue if the seed langauge exists
if (array_key_exists($seedLanguage, $json)) {

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
                // hit the api
                $translated = $tr->translate($value);    

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