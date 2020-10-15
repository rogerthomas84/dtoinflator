<?php
namespace DtoInflatorTests\TestModels;

use DtoInflator\DtoInflatorAbstract;

/**
 * Class ExampleUserFoodDto
 * @package DtoInflatorTests\TestModels
 */
class ExampleUserFoodDto extends DtoInflatorAbstract
{
    /**
     * @var string
     */
    public $ingredient;

    /**
     * @var string
     */
    public $colour;

    /**
     * @var string[]
     */
    protected $longToShortKeys = [
        'colour' => 'c'
    ];
}
