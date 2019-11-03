<?php
namespace DtoInflatorTests;

use DtoInflatorTests\TestModels\ExamplePersonDto;
use DtoInflatorTests\TestModels\ExamplePetDto;
use DtoInflatorTests\TestModels\ExamplePetFoodDto;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * Class DtoInflatorFromObjectTest
 * @package DtoInflatorTests
 */
class DtoInflatorFromObjectTest extends TestCase
{
    /**
     * @var int
     */
    private $personCounter = 0;

    /**
     * @var int
     */
    private $petCounter = 0;

    /**
     * @var int
     */
    private $foodCounter = 0;

    protected function setUp(): void
    {
        $this->petCounter = 0;
        $this->personCounter = 0;
        $this->foodCounter = 0;
        parent::setUp();
    }

    public function testSingleRecordInflationFromObject()
    {
        $person = $this->getPersonDataObject();
        $inflated = ExamplePersonDto::inflateSingleObject($person);
        $this->assertEquals($person->name, $inflated->name);
        $this->assertEquals($person->age, $inflated->age);
    }

    public function testSingleRecordInflationWithSubDtoFromObject()
    {
        $person = $this->getPersonDataObject();
        $pet = $this->getPetDataObject();
        $person->favouritePet = $pet;
        $person->pets = [$pet];
        $inflated = ExamplePersonDto::inflateSingleObject($person);
        $this->assertEquals($person->name, $inflated->name);
        $this->assertEquals($person->age, $inflated->age);
        $this->assertInstanceOf(ExamplePetDto::class, $inflated->favouritePet);
        $this->assertEquals($pet->name, $inflated->favouritePet->name);
        $this->assertEquals($pet->type, $inflated->favouritePet->type);
        $this->assertCount(1, $inflated->pets);
        $this->assertCount(count($pet->foods), $inflated->pets[0]->foods);
        foreach ($inflated->pets[0]->foods as $foodKey => $foodDto) {
            $this->assertEquals($person->pets[0]->foods[$foodKey]->ingredient, $foodDto->ingredient);
        }
    }

    public function testSingleRecordInflationWithMultipleSubDtosFromObject()
    {
        $person = $this->getPersonDataObject();
        $pets = [
            $this->getPetDataObject(),
            $this->getPetDataObject(),
            $this->getPetDataObject(),
            $this->getPetDataObject(),
            $this->getPetDataObject(),
            $this->getPetDataObject(),
            $this->getPetDataObject()
        ];
        $person->favouritePet = $pets[0];
        $person->pets = $pets;
        $inflated = ExamplePersonDto::inflateSingleObject($person);
        $this->assertEquals($person->name, $inflated->name);
        $this->assertEquals($person->age, $inflated->age);
        $this->assertInstanceOf(ExamplePetDto::class, $inflated->favouritePet);
        $this->assertEquals($pets[0]->name, $inflated->favouritePet->name);
        $this->assertEquals($pets[0]->type, $inflated->favouritePet->type);
        $this->assertCount(count($pets), $inflated->pets);
        foreach ($inflated->pets as $petKey => $petDto) {
            $this->assertInstanceOf(ExamplePetDto::class, $petDto);
            $this->assertEquals($pets[$petKey]->name, $petDto->name);
            $this->assertEquals($pets[$petKey]->type, $petDto->type);
            foreach ($petDto->foods as $foodKey => $foodDto) {
                $this->assertEquals($pets[$petKey]->foods[$foodKey]->ingredient, $foodDto->ingredient);
            }
        }
    }

    public function testMultipleRecordInflationWithMultipleSubDtosFromObject()
    {
        $personOne = $this->getPersonDataObject();
        $personOne->favouritePet = $this->getPetDataObject();
        $personOne->pets = [
            $this->getPetDataObject(),
            $this->getPetDataObject()
        ];
        $personTwo = $this->getPersonDataObject();
        $personTwo->favouritePet = $this->getPetDataObject();
        $personTwo->pets = [
            $this->getPetDataObject(),
            $this->getPetDataObject()
        ];
        $personThree = $this->getPersonDataObject();
        $personThree->favouritePet = $this->getPetDataObject();
        $personThree->pets = [
            $this->getPetDataObject(),
            $this->getPetDataObject()
        ];

        $people = [
            $personOne,
            $personTwo,
            $personThree
        ];
        $inflated = ExamplePersonDto::inflateMultipleObjects($people);
        $this->assertCount(count($people), $inflated);

        foreach ($people as $peopleKey => $peopleObj) {
            $this->assertInstanceOf(ExamplePersonDto::class, $inflated[$peopleKey]);
            $this->assertInstanceOf(ExamplePetDto::class, $inflated[$peopleKey]->favouritePet);
            $this->assertCount(count($peopleObj->pets), $inflated[$peopleKey]->pets);
            $this->assertEquals($peopleObj->favouritePet->name, $inflated[$peopleKey]->favouritePet->name);
            $this->assertEquals($peopleObj->favouritePet->type, $inflated[$peopleKey]->favouritePet->type);
            $this->assertCount(count($peopleObj->favouritePet->foods), $inflated[$peopleKey]->favouritePet->foods);
            foreach ($peopleObj->pets as $petKey => $petObj) {
                $this->assertInstanceOf(ExamplePetDto::class, $inflated[$peopleKey]->pets[$petKey]);
                $this->assertEquals($petObj->name, $inflated[$peopleKey]->pets[$petKey]->name);
                $this->assertEquals($petObj->type, $inflated[$peopleKey]->pets[$petKey]->type);
                foreach ($petObj->foods as $foodKey => $foodObj) {
                    $this->assertInstanceOf(ExamplePetFoodDto::class, $inflated[$peopleKey]->pets[$petKey]->foods[$foodKey]);
                    $this->assertEquals($foodObj->ingredient, $inflated[$peopleKey]->pets[$petKey]->foods[$foodKey]->ingredient);
                }
            }
        }
    }

    /**
     * Get the next person object from the data
     * @return object
     */
    private function getPersonDataObject()
    {
        $names = ['Jane', 'Joe', 'Bill', 'John'];
        $counter = $this->personCounter;
        if ($counter > (count($names)-1)) {
            $counter = 0;
            $this->personCounter = 0;
        } else {
            $this->personCounter++;
        }

        $obj = new stdClass();
        $obj->name = $names[$counter] . ' Bloggs';
        $obj->age = rand(18, 50);
        return $obj;
    }

    /**
     * Get the next pet object from the data.
     * @return object
     */
    private function getPetDataObject()
    {
        $names = ['Chilli', 'Kitty', 'Shark Face', 'Nay Nay', 'Oinky', 'Maaa', 'Baaa'];
        $types = ['Dog', 'Cat', 'Fish', 'Horse', 'Pig', 'Goat', 'Sheep'];
        $counter = $this->petCounter;
        if ($counter > (count($names)-1)) {
            $counter = 0;
            $this->petCounter = 0;
        } else {
            $this->petCounter++;
        }
        $obj = new stdClass();
        $obj->name = $names[$counter];
        $obj->type = $types[$counter];
        $obj->foods = [
            $this->getPetFoodDataObject(),
            $this->getPetFoodDataObject()
        ];
        return $obj;
    }

    /**
     * Get the next pet food array from the data.
     * @return object
     */
    private function getPetFoodDataObject()
    {
        $ingredients = ['Beef', 'Chicken', 'Fish', 'Rice', 'Oats', 'Water', 'Milk', 'Pork', 'Mouse', 'Rat', 'Bugs'];
        $counter = $this->foodCounter;
        if ($counter > (count($ingredients)-1)) {
            $counter = 0;
            $this->foodCounter = 0;
        } else {
            $this->foodCounter++;
        }
        $obj = new stdClass();
        $obj->ingredient = $ingredients[$counter];
        return $obj;
    }
}
