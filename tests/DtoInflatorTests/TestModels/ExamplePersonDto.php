<?php
namespace DtoInflatorTests\TestModels;

use DtoInflator\DtoInflatorAbstract;

/**
 * Class ExamplePersonDto
 * @package DtoInflatorTests\TestModels
 */
class ExamplePersonDto extends DtoInflatorAbstract
{
    /**
     * @var string
     */
    public $firstName;

    /**
     * @var string
     */
    public $age;

    /**
     * @var ExamplePetDto[]
     */
    public $pets = [];

    /**
     * @var ExamplePetDto
     */
    public $favouritePet;

    protected $fieldToFieldMap = [
        'name' => 'firstName'
    ];

    protected $keyToClassMap = [
        'pets' => '\DtoInflatorTests\TestModels\ExamplePetDto[]',
        'favouritePet' => '\DtoInflatorTests\TestModels\ExamplePetDto'
    ];
}
