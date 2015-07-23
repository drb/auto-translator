# Auto translator

Simple utility to take a dictionary of strings in language X, and translate them to language(s) Y, Z.

The source strings are placed in a new JSON object using the target language shortcode as the key.

## Example - this source file in English

```
{
    "en": {
        "test.foo": "Hello my name is John. I like to play tennis on grass.",
        "test.bar": "The man inside the moon does not like cheese.",
        "test.baz": "America is a country with a very large land mass.",
        "ticket.foo.bar": "Bike."
    }
}
```

Becomes...

```
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

```
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

### Options

```
-s          Source language (using 2 letter ISO_639-1 code).
-l          Target languages. Specify multiple by separating with commas.
-v          Verbose mode (prints output to screen).
-e          Expand namespaces to nested objects.
```

### Installation:

* Clone the repo out
* Run `composer install`
* Define your keys that require translation in template.json
* Set target languages in json-translate.php
* Run `php json-translate.php -s en -l fr,de,es -v`

The output is written to `output.json`.


