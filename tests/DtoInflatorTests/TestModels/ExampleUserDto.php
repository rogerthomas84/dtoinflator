<?php
namespace DtoInflatorTests\TestModels;

use DtoInflator\DtoInflatorAbstract;

/**
 * Class ExampleUserDto
 * @package DtoInflatorTests\TestModels
 */
class ExampleUserDto extends DtoInflatorAbstract
{
    /**
     * @var string
     */
    public $firstName;

    /**
     * @var string
     */
    public $lastName;

    /**
     * @var string
     */
    public $keyNotInShortArray;

    /**
     * @var ExampleUserFoodDto|null
     */
    public $food = null;

    /**
     * @var string[]
     */
    protected $longToShortKeys = [
        'firstName' => 'fn',
        'lastName' => 'ln'
    ];

    /**
     * @var string[]
     */
    protected $keyToClassMap = [
        'food' => ExampleUserFoodDto::class
    ];
}
