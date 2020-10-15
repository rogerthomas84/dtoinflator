<?php
namespace DtoInflatorTests;

use DtoInflatorTests\TestModels\ExamplePersonDto;
use DtoInflatorTests\TestModels\ExamplePetDto;
use DtoInflatorTests\TestModels\ExamplePetFoodDto;
use DtoInflatorTests\TestModels\ExampleUserDto;
use PHPUnit\Framework\TestCase;

/**
 * Class DtoInflatorFromArrayTest
 * @package DtoInflatorTests
 */
class DtoInflatorFromArrayTest extends TestCase
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

    public function testShortKeyedUserSingleArray()
    {
        $array = [
            'fn' => 'Joe',
            'ln' => 'Bloggs',
            'keyNotInShortArray' => 'FooBar',
            'food' => [
                'ingredient' => 'Grain',
                'c' => 'Red'
            ]
        ];
        $dto = ExampleUserDto::inflateSingleArray($array, true);
        $this->assertEquals('Joe', $dto->firstName);
        $this->assertEquals('Bloggs', $dto->lastName);
        $this->assertEquals('FooBar', $dto->keyNotInShortArray);
        $this->assertEquals('Grain', $dto->food->ingredient);
        $this->assertEquals('Red', $dto->food->colour);
    }

    public function testShortKeyedUserSingleMultipleArrays()
    {
        $array = [
            'fn' => 'Joe',
            'ln' => 'Bloggs',
            'keyNotInShortArray' => 'FooBar',
            'food' => [
                'ingredient' => 'Grain',
                'c' => 'Orange'
            ]
        ];

        $arrayTwo = [
            'fn' => 'Jane',
            'ln' => 'Doe',
            'keyNotInShortArray' => 'Abc',
            'food' => [
                'ingredient' => 'Rice',
                'c' => 'Brown'
            ]
        ];
        $dtos = ExampleUserDto::inflateMultipleArrays(
            [
                $array,
                $arrayTwo
            ],
            true
        );
        $this->assertEquals('Joe', $dtos[0]->firstName);
        $this->assertEquals('Bloggs', $dtos[0]->lastName);
        $this->assertEquals('FooBar', $dtos[0]->keyNotInShortArray);
        $this->assertEquals('Grain', $dtos[0]->food->ingredient);
        $this->assertEquals('Orange', $dtos[0]->food->colour);

        $this->assertEquals('Jane', $dtos[1]->firstName);
        $this->assertEquals('Doe', $dtos[1]->lastName);
        $this->assertEquals('Abc', $dtos[1]->keyNotInShortArray);
        $this->assertEquals('Rice', $dtos[1]->food->ingredient);
        $this->assertEquals('Brown', $dtos[1]->food->colour);
    }

    public function testSingleRecordInflationFromArray()
    {
        $person = $this->getPersonDataArray();
        $inflated = ExamplePersonDto::inflateSingleArray($person);
        $this->assertEquals($person['name'], $inflated->firstName);
        $this->assertEquals($person['age'], $inflated->age);
    }

    public function testSingleRecordInflationWithSubDtoFromArray()
    {
        $person = $this->getPersonDataArray();
        $pet = $this->getPetDataArray();
        $person['favouritePet'] = $pet;
        $inflated = ExamplePersonDto::inflateSingleArray($person);
        $this->assertEquals($person['name'], $inflated->firstName);
        $this->assertEquals($person['age'], $inflated->age);
        $this->assertInstanceOf(ExamplePetDto::class, $inflated->favouritePet);
        $this->assertCount(count($pet['foods']), $inflated->favouritePet->foods);
        $this->assertInstanceOf(ExamplePetFoodDto::class, $inflated->favouritePet->foods[0]);
        $this->assertInstanceOf(ExamplePetFoodDto::class, $inflated->favouritePet->foods[1]);

        $this->assertEquals($pet['foods'][0]['ingredient'], $inflated->favouritePet->foods[0]->ingredient);
        $this->assertEquals($pet['foods'][1]['ingredient'], $inflated->favouritePet->foods[1]->ingredient);

        $this->assertEquals($pet['name'], $inflated->favouritePet->name);
        $this->assertEquals($pet['type'], $inflated->favouritePet->type);
    }

    public function testSingleRecordInflationWithMultipleSubDtosFromArray()
    {
        $person = $this->getPersonDataArray();
        $pets = [
            $this->getPetDataArray(),
            $this->getPetDataArray(),
            $this->getPetDataArray(),
            $this->getPetDataArray(),
            $this->getPetDataArray(),
            $this->getPetDataArray(),
            $this->getPetDataArray()
        ];
        $person['favouritePet'] = $pets[0];
        $person['pets'] = $pets;
        $inflated = ExamplePersonDto::inflateSingleArray($person);
        $this->assertEquals($person['name'], $inflated->firstName);
        $this->assertEquals($person['age'], $inflated->age);
        $this->assertInstanceOf(ExamplePetDto::class, $inflated->favouritePet);
        foreach ($inflated->pets as $k => $petDto) {
            $this->assertCount(count($pets[$k]['foods']), $petDto->foods);
            $this->assertEquals($pets[$k]['name'], $petDto->name);
            $this->assertEquals($pets[$k]['type'], $petDto->type);
            foreach ($petDto->foods as $foodKey => $foodDto) {
                $this->assertInstanceOf(ExamplePetFoodDto::class, $foodDto);
                $this->assertEquals($pets[$k]['foods'][$foodKey]['ingredient'], $foodDto->ingredient);
            }
        }
    }

    public function testMultipleRecordInflationWithMultipleSubDtosFromArray()
    {
        $personOne = $this->getPersonDataArray();
        $personOne['favouritePet'] = $this->getPetDataArray();
        $personOne['pets'] = [
            $this->getPetDataArray(),
            $this->getPetDataArray()
        ];
        $personTwo = $this->getPersonDataArray();
        $personTwo['favouritePet'] = $this->getPetDataArray();
        $personTwo['pets'] = [
            $this->getPetDataArray(),
            $this->getPetDataArray()
        ];
        $personThree = $this->getPersonDataArray();
        $personThree['favouritePet'] = $this->getPetDataArray();
        $personThree['pets'] = [
            $this->getPetDataArray(),
            $this->getPetDataArray()
        ];

        $people = [
            $personOne,
            $personTwo,
            $personThree
        ];
        $inflated = ExamplePersonDto::inflateMultipleArrays($people);
        $this->assertCount(3, $inflated);
        foreach ($inflated as $pKey => $aPerson) {
            $this->assertInstanceOf(ExamplePersonDto::class, $aPerson);
            $this->assertInstanceOf(ExamplePetDto::class, $aPerson->favouritePet);

            $this->assertEquals($people[$pKey]['name'], $aPerson->firstName);
            $this->assertEquals($people[$pKey]['age'], $aPerson->age);

            $this->assertEquals($people[$pKey]['favouritePet']['name'], $aPerson->favouritePet->name);
            $this->assertEquals($people[$pKey]['favouritePet']['type'], $aPerson->favouritePet->type);

            $this->assertCount(count($people[$pKey]['pets']), $aPerson->pets);
            foreach ($aPerson->pets as $petKey => $petObject) {
                $this->assertInstanceOf(ExamplePetDto::class, $petObject);
                $this->assertEquals($people[$pKey]['pets'][$petKey]['name'], $petObject->name);
                $this->assertEquals($people[$pKey]['pets'][$petKey]['type'], $petObject->type);
                $this->assertCount(count($people[$pKey]['pets'][$petKey]['foods']), $petObject->foods);

                foreach ($petObject->foods as $foodKey => $foodDto) {
                    $this->assertEquals($people[$pKey]['pets'][$petKey]['foods'][$foodKey]['ingredient'], $foodDto->ingredient);
                }
            }
        }
    }

    /**
     * Get the next person array from the data
     * @return array
     */
    private function getPersonDataArray()
    {
        $names = ['Jane', 'Joe', 'Bill', 'John'];
        $counter = $this->personCounter;
        if ($counter > (count($names)-1)) {
            $counter = 0;
            $this->personCounter = 0;
        } else {
            $this->personCounter++;
        }
        return [
            'name' => $names[$counter],
            'age' => rand(18, 50)
        ];
    }

    /**
     * Get the next pet array from the data.
     * @return array
     */
    private function getPetDataArray()
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
        return [
            'name' => $names[$counter],
            'type' => $types[$counter],
            'foods' => [
                $this->getPetFoodDataArray(),
                $this->getPetFoodDataArray()
            ]
        ];
    }

    /**
     * Get the next pet food array from the data.
     * @return array
     */
    private function getPetFoodDataArray()
    {
        $ingredients = ['Beef', 'Chicken', 'Fish', 'Rice', 'Oats', 'Water', 'Milk', 'Pork', 'Mouse', 'Rat', 'Bugs'];
        $counter = $this->foodCounter;
        if ($counter > (count($ingredients)-1)) {
            $counter = 0;
            $this->foodCounter = 0;
        } else {
            $this->foodCounter++;
        }
        return [
            'ingredient' => $ingredients[$counter]
        ];
    }
}
