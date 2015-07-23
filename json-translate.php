<?php

// setup composer
require 'vendor/autoload.php';

// import...
use Stichoza\GoogleTranslate\TranslateClient;

//base variables
$template           = './template.json';            // the source file to get all translations from
$output             = './output.json';              // the output file once all translations are done
$seedLanguage       = 'en';                         // the seed language
$targetLanguages    = array('fr', 'de');            // the languages we need

// load the template data (the text strings in english/target language)
$string = file_get_contents($template);
$json   = json_decode($string, true);


// only continue if the 
if (array_key_exists($seedLanguage, $json)) {

    // get the strings in the source language we want to translate
    $seedStrings = $json[$seedLanguage];

    // loop over each target language, hit the transaltion API
    foreach ($targetLanguages as $lang) {

        // setup api
        $tr = new TranslateClient($seedLanguage, $lang);

        if (!array_key_exists($lang, $json)) {
            $json[$lang] = array();    
        }

        // hit each string
        foreach ($seedStrings as $key => $value) {
            try {
                $json[$lang][$key] = $tr->translate($value);    
            } catch (Exception $e) {
                echo (sprintf('Failed to get translation: %s', $e->getMessage()));
            }
        }
    }
}

// write the translated json to disk
try {
    $fp = fopen($output, 'w');
    fwrite($fp, json_encode($json, JSON_PRETTY_PRINT));
    fclose($fp);
} catch (Exception $e) {
    echo (sprintf('Error writing output file %s. Error found: %s', $output, $e->getMessage()));
}

// output to console
print_r($json);
?>