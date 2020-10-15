DTO Inflator
=======

DtoInflator is a helpful library for converting arrays or generic objects into DTOs.

This was originally written for helping ease the management of responses from API services.


Usage
-----

Create your model, extending `DtoInflatorAbstract`

If you require a sub model, add the property of `protected $keyToClassMap` to your parent model mapping the key name 
to the fully qualified class name. This tells the library to identify the key and that it needs to inflate a specific 
model. 

**All models should extend `DtoInflatorAbstract`.**

If you have a property within a DTO called 'favourite' and it requires a sub model, you could map it with this:

```
protected $keyToClassMap = [
    'favourite' => '\MyNamespace\Favourite'
];
```

If you require an array of models (for example someone can have various favourite items), you can simply
append `[]` onto the class name in the map.

So, if you have a property called 'favourites' and it's an array of child models, the key to class map
should look like this:

```
protected $keyToClassMap = [
    'favourites' => '\MyNamespace\Favourite[]'
];
```

Sometimes you might want to change the name of a property to something else, for example in the case of API
responses, you might want to change underscored keys with camelcase. To do this, simply expose the `fieldToFieldMap`
variable in your model. Where the key is the original name, and the value is the new key to use in the DTO.

**Please note, the newly named key is only used in inflation.**

```
protected $fieldToFieldMap = [
    'my_underscore_key' => 'myUnderscoreKey'
];
```

You might also want to shorten keys every now and then. To do this, you can pass a second parameter into the
inflate methods, defining where these keys need to be mapped.

```
protected $longToShortKeys = [
    'user_first_name' => 'fn', // would map the key 'user_first_name' to use 'fn'
    'user_last_name' => 'ln' // would map the key 'user_last_name' to use 'ln'
];
```

Examples
--------

There are example models in the `tests/DtoInflatorTests/TestModels` directory.


Inflating
---------

You can inflate a single record by calling `inflateSingleArray` passing the array of data or alternatively, 
if you've got an object (for example `stdClass`) you can use `inflateSingleObject`
 
```php
<?php
namespace MyNamespace;

class Person extends \DtoInflator\DtoInflatorAbstract
{
    public $name;
    public $age;
}

$data = [
    'name' => 'Joe',
    'age' => 35
];
$inflated = Person::inflateSingleArray($data);
```

Likewise, you can inflate a multiple records by calling `inflateMultipleArrays` passing the array of arrays, 
or again if you're using objects like above, the `inflateMultipleObjects` method.
 
```php
<?php
namespace MyNamespace;

class Person extends \DtoInflator\DtoInflatorAbstract
{
    public $name;
    public $age;
}

$data = [
    [
        'name' => 'Joe',
        'age' => 35
    ],
    [
        'name' => 'Jane',
        'age' => 34
    ]
];
$inflated = Person::inflateSingleArray($data);
```

Mapping fields
--------------

If a key passed into the inflate methods isn't found in the object, it gets added to the `unmappedFields` array.
Likewise, if you try to request data from the object after inflating and the property isn't found, internally the
abstract method `__get($name)` will check the `unmappedFields` for a corresponding value.

Theoretically you don't have to declare variables. But this obviously isn't advised. It does however mean that
properties don't actually have to be `public`, they could be `protected` but never (ever) `private`.

More advanced models
--------------------

If you needed more advanced models, you could use something like this:

```php
<?php
namespace MyNamespace;

class Favourite extends \DtoInflator\DtoInflatorAbstract
{
    /**
     * @var string
     */
    public $candy;
}

class ColorItem extends \DtoInflator\DtoInflatorAbstract
{
    /**
     * @var string
     */
    public $name;
}

class Person extends \DtoInflator\DtoInflatorAbstract
{
    /**
     * @var string
     */
    public $firstName;

    /**
     * @var int
     */
    public $age;

    /**
     * @var Favourite
     */
    public $favs;

    /**
     * @var ColorItem[]
     */
    public $colors;

    /**
     * @param array
     */
    protected $fieldToFieldMap = [
        'name' => 'firstName' // maps the source key of `name` to the object property of `firstName`
    ];

    /**
     * @param array
     */
    protected $keyToClassMap = [
        'favs' => '\MyNamespace\Favourite',  // maps the object property of `favs` to an instance of the `Favourite` object
        'colors' => '\MyNamespace\ColorItem[]'   // maps the object property of `colors` to an array of `ColorItem` objects
    ];
}

$data = [
    'name' => 'Joe',
    'age' => 35,
    'favs' => [
        'candy' => 'chocolate'
    ],
    'colors' => [
        [
            'name' => 'blue'
        ],
        [
            'name' => 'red'
        ]
    ]
];
$inflated = Person::inflateSingleArray($data);

// Or, if you're using an object initially.
$candyBar = new stdClass();
$candyBar->candy = 'chocolate';
$colorOne = new stdClass();
$colorOne->name = 'blue';
$colorTwo = new stdClass();
$colorTwo->name = 'red';

$data = new stdClass();
$data->name = 'Joe';
$data->age = 35;
$data->favs = [
    $candyBar
];
$data->colors = [
    $colorOne,
    $colorTwo
];
$inflated = Person::inflateSingleObject($data);
```


Running unit tests
------------------

`./vendor/bin/phpunit -c phpunit.xml`
