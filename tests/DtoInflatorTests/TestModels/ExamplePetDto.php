<?php
namespace DtoInflatorTests\TestModels;

use DtoInflator\DtoInflatorAbstract;

/**
 * Class ExamplePetDto
 * @package DtoInflatorTests\TestModels
 */
class ExamplePetDto extends DtoInflatorAbstract
{
    /**
     * @var string
     */
    public $type;

    /**
     * @var string
     */
    public $name;

    /**
     * @var ExamplePetFoodDto[]
     */
    public $foods;

    protected $keyToClassMap = [
        'foods' => '\DtoInflatorTests\TestModels\ExamplePetFoodDto[]'
    ];
}
