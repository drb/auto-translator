# Auto translator

Simple utility to take a dictionary of strings in language X, and translate them to language(s) Y, Z using Google's Translate APIs (un-authed mode is available).

The source strings are placed in a new JSON object using the target language shortcode as the key.

## Example - this source file in English

```json
{
    "en": {
        "_comment": "This is a comment and won't be in the output",
        
        "test.foo": "Hello my name is John. I like to play tennis on grass.",
        "test.bar": "The man inside the moon does not like cheese.",
        "test.baz": "America is a country with a very large land mass.",
        "ticket.foo.bar": "Bike."
    }
}
```

Becomes...

```json
{
    "en": {
        "test.foo": "Hello my name is John. I like to play tennis on grass.",
        "test.bar": "The man inside the moon does not like cheese.",
        "test.baz": "America is a country with a very large land mass.",
        "ticket.foo.bar": "Bike."
    },
    "fr": {
        "test.foo": "Bonjour mon nom est John. Je veux jouer au tennis sur herbe.",
        "test.bar": "L'homme \u00e0 l'int\u00e9rieur de la lune ne aime pas le fromage .",
        "test.baz": "L'Am\u00e9rique est un pays avec une tr\u00e8s grande masse de terre .",
        "ticket.foo.bar": "Bicyclette."
    },
    "de": {
        "test.foo": "Hallo mein Name ist John . Ich mag Tennis auf Gras zu spielen.",
        "test.bar": "Der Mann in der Mond nicht wie K\u00e4se.",
        "test.baz": "Amerika ist ein Land mit einer sehr gro\u00dfen Landmasse .",
        "ticket.foo.bar": "Fahrrad."
    }
}
```

### With expanded namespaces:

```json
{
    "en": {
        "test": {
            "foo": "Hello my name is John. I like to play tennis on grass.",
            "bar": "The man inside the moon does not like cheese.",
            "baz": "America is a country with a very large land mass."
        },
        "ticket": {
            "foo": {
                "bar": "Bike"
            }
        }
    },
    "fr": {
        "test": {
            "foo": "Bonjour mon nom est John. Je veux jouer au tennis sur herbe.",
            "bar": "L'homme \u00e0 l'int\u00e9rieur de la lune ne aime pas le fromage .",
            "baz": "L'Am\u00e9rique est un pays avec une tr\u00e8s grande masse de terre ."
        },
        "ticket": {
            "foo": {
                "bar": "Bicyclette"
            }
        }
    },
    "de": {
        "test": {
            "foo": "Hallo mein Name ist John . Ich mag Tennis auf Gras zu spielen.",
            "bar": "Der Mann in der Mond nicht wie K\u00e4se.",
            "baz": "Amerika ist ein Land mit einer sehr gro\u00dfen Landmasse ."
        },
        "ticket": {
            "foo": {
                "bar": "Fahrrad"
            }
        }
    }
}
```

### Ignored values (whole key)

In some instances, it may be preferable to retain the original language string instead of attempting a translation - for example, a trademark or company name. 

Strings that should not be translated can be marked by prefixing the key with a dollar ($) sign.

```json
{   
    "en": {
        "$companyName": "Orange Computers" 
    }
}
```

### Ignored values (inline strings)

There may be cases where a string needs to be translated that contains a keyword that cannot be translated. Wrapping these with dollar-brackets will prevent the string from being translated e.g `$(don't translate me)`. This retains the context of the un translated keywords in the translated output.

Strings that should not be translated can be marked by wrapping parts of the value with brackets, prefixed with a dollar ($) sign.

```json
{   
    "en": {
        "partiallyTranslatedString": "I work for $(Orange Computers)" 
    }
}
```


### Options

```
-i          Input file (JSON).
-o          Output file path.
-s          Source language (using 2 letter ISO_639-1 code).
-l          Target languages. Specify multiple by separating with commas.
-v          Verbose mode (prints output to screen).
-e          Expand namespaces to nested objects.
-p          Pretty-print the JSON output (for readability).
-c          Don't remove _comment properties from the source data.
-a          Use the Google Translate API with an active key (requires [credentials setting up](#credentials))
```

### Installation:

* Clone the repo out
* Run `composer install`
* Define your keys that require translation in template.json
* Run `php json-translate.php -i <source.json> -o <output.json> -s <source language> -l <target languages> -v`
* e.g. `php json-translate.php -i template.json -o output.json -s en -l fr,de -v`

Using the above example, the input is taken from template.json, the English strings are translated to French and German, then the output is written to `output.json` and the console.

### Supported Languages

List mirrored from the official [Google Translate API Docs](https://cloud.google.com/translate/v2/using_rest).

| Language | Language code |
| ------------- | ----------- |
| Afrikaans |af |
| Albanian | sq |
| Arabic | ar |
| Azerbaijani | az |
| Basque | eu |
| Bengali | bn |
| Belarusian | be |
| Bulgarian |bg |
| Catalan | ca |
| Chinese Simplified |  zh-CN |
| Chinese Traditional | zh-TW |
| Croatian | hr |
| Czech |cs |
| Danish | da |
| Dutch |nl |
| English | en |
| Esperanto |eo |
| Estonian | et |
| Filipino | tl |
| Finnish | fi |
| French | fr |
| Galician | gl |
| Georgian | ka |
| German | de |
| Greek |el |
| Gujarati | gu |
| Haitian Creole |  ht |
| Hebrew | iw |
| Hindi |hi |
| Hungarian |hu |
| Icelandic |is |
| Indonesian | id |
| Irish |ga |
| Italian | it |
| Japanese | ja |
| Kannada | kn |
| Korean | ko |
| Latin |la |
| Latvian | lv |
| Lithuanian | lt |
| Macedonian | mk |
| Malay |ms |
| Maltese | mt |
| Norwegian |no |
| Persian | fa |
| Polish | pl |
| Portuguese | pt |
| Romanian | ro |
| Russian | ru |
| Serbian | sr |
| Slovak | sk |
| Slovenian |sl |
| Spanish | es |
| Swahili | sw |
| Swedish | sv |
| Tamil |ta |
| Telugu | te |
| Thai | th |
| Turkish | tr |
| Ukrainian |uk |
| Urdu | ur |
| Vietnamese | vi |
| Welsh |cy |
| Yiddish | yi |

### Credentials

If the -a flag is passed, the script will use Google's offical API endpoint. This requires an active, valid API key to work.

* Create an account with Google on the Google Cloud Platform and enable the Translate API
* Create and keep note of an API key
* Create file in the same directory as the cloned out project called `creds.ini`
* Add the following to the file:

```
[google_api]
key = YOUR_KEY_HERE
```



