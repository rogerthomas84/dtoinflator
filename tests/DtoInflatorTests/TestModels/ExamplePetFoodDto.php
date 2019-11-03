<?php
namespace DtoInflatorTests\TestModels;

use DtoInflator\DtoInflatorAbstract;

/**
 * Class ExamplePetFoodDto
 * @package DtoInflatorTests\TestModels
 */
class ExamplePetFoodDto extends DtoInflatorAbstract
{
    /**
     * @var string
     */
    public $ingredient;
}
