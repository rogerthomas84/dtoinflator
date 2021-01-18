<?php
namespace DtoInflator;

use ReflectionClass;
use ReflectionException;
use stdClass;

/**
 * Class DtoInflatorAbstract
 * @package DtoInflator
 */
abstract class DtoInflatorAbstract
{
    /**
     * An array holding data on which keys need to be coerced into sub DTOs.
     * @example [
     *      'property' => '\Full\Class\Name'
     * ]
     *
     * @var string[]
     */
    protected $keyToClassMap = [];

    /**
     * An array holding all keys to values that weren't found in the object.
     *
     * @var array
     */
    protected $unmappedFields = [];

    /**
     * An array holding original key to new keys.
     * @example [
     *      'some_underscore_key' => 'someCamelCaseKey'
     * ]
     * @var string[]
     */
    protected $fieldToFieldMap = [];

    /**
     * An array holding long keys to new shorter keys.
     * This array gets processed AFTER the `fieldToFieldMap` array.
     *
     * @example [
     *      'user_first_name' => 'fn'
     * ]
     * @var string[]
     */
    protected $longToShortKeys = [];

    /**
     * @return array
     */
    protected function getKeyToClassMap(): array
    {
        return $this->keyToClassMap;
    }

    /**
     * Set a value via magic method.
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set(string $name, $value)
    {
        if (property_exists($this, $name)) {
            $this->{$name} = $value;
            return;
        }
        $this->unmappedFields[$name] = $value;
    }

    /**
     * Get a value via magic method.
     *
     * @param string $name
     * @return mixed|null
     */
    public function __get(string $name)
    {
        if (property_exists($this, $name)) {
            return $this->{$name};
        }
        if (isset($this->unmappedFields[$name])) {
            return $this->unmappedFields[$name];
        }
        return null;
    }

    /**
     * Holds an array of protected variable names.
     *
     * @return array
     */
    protected function getPropertyExclusions(): array
    {
        return ['keyToClassMap', 'unmappedFields', 'longToShortKeys', 'fieldToFieldMap'];
    }

    /**
     * Get an array export representation of this model and all child models.
     *
     * @return array
     */
    public function asArray(): array
    {
        $data = [];
        foreach (get_object_vars($this) as $k => $v) {
            if (in_array($k, $this->getPropertyExclusions())) {
                continue;
            }
            if (array_key_exists($k, $this->getKeyToClassMap())) {
                if (substr($this->getKeyToClassMap()[$k], -2) === '[]') {
                    /* @var $v DtoInflatorAbstract[] */
                    $subs = [];
                    foreach ($v as $datum) {
                        $subs[] = $datum->asArray();
                    }
                    $v = $subs;
                } else {
                    /* @var $v DtoInflatorAbstract */
                    $v = $v->asArray();
                }
            }
            $data[$k] = $v;
        }
        if (!empty($this->unmappedFields)) {
            $data = array_merge_recursive($this->unmappedFields, $data);
        }
        return $data;
    }

    /**
     * Inflate an array of DTOs from an array of arrays containing data.
     *
     * @param array $data
     * @param bool $fromShortKeys
     * @return $this[]
     * @example
     * MyDto::inflateMultipleArrays(
     *      [
     *          [
     *          'name' => 'Joe',
     *              'age' => 30
     *          ],
     *          [
     *              'name' => 'Jane',
     *              'age' => 32
     *          ]
     *      ]
     * )
     */
    public static function inflateMultipleArrays(array $data, $fromShortKeys = false): array
    {
        if (empty($data)) {
            return [];
        }
        $cl = get_called_class();
        $inst = new $cl();
        /* @var $cl DtoInflatorAbstract - it's not really. */
        if (!$inst instanceof DtoInflatorAbstract) {
            return [];
        }
        $final = [];
        foreach ($data as $_ => $datum) {
            $final[] = $cl::inflateSingleArray($datum, $fromShortKeys);
        }
        return $final;
    }

    /**
     * Inflate a single DTO from an object instance.
     *
     * @param stdClass|object $obj
     * @param bool $fromShortKeys
     * @return $this
     */
    public static function inflateSingleObject($obj, $fromShortKeys = false)
    {
        return self::inflateSingleArray(
            self::objectToArray($obj),
            $fromShortKeys
        );
    }

    /**
     * Helper method to convert an object (with potential arrays or objects inside) into a normal array.
     *
     * @param stdClass|object $obj
     * @return array
     */
    protected static function objectToArray($obj): array
    {
        $props = get_object_vars($obj);
        $array = [];
        foreach ($props as $k => $v) {
            if (is_array($v)) {
                $v = self::arrayToArray($v);
            }
            if (is_object($v)) {
                $v = self::objectToArray($v);
            }
            $array[$k] = $v;
        }
        return $array;
    }

    /**
     * Helper method to convert an array (with potential objects) into a normal array.
     *
     * @param array $arr
     * @return array
     */
    protected static function arrayToArray(array $arr): array
    {
        $data = [];
        $isIntArray = false;
        $i = 0;
        foreach ($arr as $k => $v) {
            if ($k === $i) {
                $isIntArray = true;
            }
            if (is_array($v)) {
                $v = self::arrayToArray($v);
            }
            if (is_object($v)) {
                $v = self::objectToArray($v);
            }
            if ($isIntArray === true) {
                $data[] = $v;
            } else {
                $data[$k] = $v;
            }
            $i++;
        }
        return $data;
    }

    /**
     * Inflate an array of DTOs from an array of objects.
     *
     * @param object[] $data
     * @param bool $fromShortKeys
     * @return $this[]
     */
    public static function inflateMultipleObjects(array $data, $fromShortKeys = false): array
    {
        if (empty($data)) {
            return [];
        }
        $cl = get_called_class();
        $inst = new $cl();
        /* @var $cl DtoInflatorAbstract - it's not really. */
        if (!$inst instanceof DtoInflatorAbstract) {
            return [];
        }
        $final = [];
        foreach ($data as $_ => $datum) {
            $final[] = $cl::inflateSingleObject($datum, $fromShortKeys);
        }
        return $final;
    }

    /**
     * Inflate a DTO from an array of data.
     *
     * @param array $data
     * @param bool $fromShortKeys
     * @return $this
     */
    public static function inflateSingleArray(array $data, $fromShortKeys = false)
    {
        $cl = get_called_class();
        $inst = new $cl();
        if (!$inst instanceof DtoInflatorAbstract) {
            return null;
        }
        $classMap = $inst->getKeyToClassMap();
        if ($fromShortKeys === true) {
            $tmpKeys = $inst->longToShortKeys;
            $shortToLong = [];
            foreach ($tmpKeys as $k => $v) {
                $shortToLong[$v] = $k;
            }
            $newData = [];
            foreach ($data as $k => $v) {
                if (array_key_exists($k, $shortToLong)) {
                    $newData[$shortToLong[$k]] = $v;
                } else {
                    $newData[$k] = $v;
                }
            }
            $data = $newData;
        }

        foreach ($data as $k => $v) {
            if (is_int($k)) {
                continue;
            }
            if (in_array($k, $inst->getPropertyExclusions())) {
                continue;
            }
            if (array_key_exists($k, $inst->fieldToFieldMap)) {
                $k = $inst->fieldToFieldMap[$k];
            }

            if (is_array($v) && array_key_exists($k, $classMap)) {
                $subClassName = $classMap[$k];
                $isMulti = false;
                if (substr($subClassName, -2) === '[]') {
                    $isMulti = true;
                    $subClassName = substr($subClassName, 0, -2);
                }
                if (class_exists($subClassName)) {
                    /* @var $subClassName DtoInflatorAbstract */
                    if ($isMulti) {
                        $v = $subClassName::inflateMultipleArrays($v, $fromShortKeys);
                    } else {
                        $v = $subClassName::inflateSingleArray($v, $fromShortKeys);
                        if ($v === null) {
                            continue;
                        }
                    }
                } else {
                    // Class didn't exist, so adding to unmapped.
                    $inst->unmappedFields[$k] = $v;
                }
            }
            $inst->__set($k, $v);
        }
        return $inst;
    }
}
