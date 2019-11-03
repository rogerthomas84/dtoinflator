<?php
namespace DtoInflator;

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
     * @var array
     */
    protected $keyToClassMap = [];

    /**
     * An array holding all keys to values that weren't found in the object.
     *
     * @var array
     */
    protected $unmappedFields = [];

    /**
     * @return array
     */
    protected function getKeyToClassMap()
    {
        return $this->keyToClassMap;
    }

    /**
     * Set a value via magic method.
     *
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        if (property_exists($this, $name)) {
            $this->{$name} = $value;
        }
        $this->unmappedFields[$name] = $value;
    }

    /**
     * Get a value via magic method.
     *
     * @param string $name
     * @return mixed|null
     */
    public function __get($name)
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
    protected function getPropertyExclusions()
    {
        return ['keyToClassMap', 'unmappedFields'];
    }

    /**
     * Get an array export representation of this model and all child models.
     *
     * @return array
     */
    public function asArray()
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
     * @param array $data
     * @return $this[]
     */
    public static function inflateMultipleArrays(array $data)
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
            $final[] = $cl::inflateSingleArray($datum);
        }
        return $final;
    }

    /**
     * Inflate a single DTO from an object instance.
     *
     * @param object $obj
     * @return $this
     */
    public static function inflateSingleObject(object $obj)
    {
        return self::inflateSingleArray(
            json_decode(
                json_encode($obj),
                true
            )
        );
    }

    /**
     * Inflate an array of DTOs from an array of objects.
     *
     * @param object[] $data
     * @return $this[]
     */
    public static function inflateMultipleObjects(array $data)
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
            $final[] = $cl::inflateSingleObject($datum);
        }
        return $final;
    }

    /**
     * Inflate a DTO from an array of data.
     *
     * @param array $data
     * @return $this
     */
    public static function inflateSingleArray(array $data)
    {
        $cl = get_called_class();
        $inst = new $cl();
        if (!$inst instanceof DtoInflatorAbstract) {
            return null;
        }
        $classMap = $inst->getKeyToClassMap();

        foreach ($data as $k => $v) {
            if (is_int($k)) {
                continue;
            }
            if (in_array($k, $inst->getPropertyExclusions())) {
                continue;
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
                        $v = $subClassName::inflateMultipleArrays($v);
                    } else {
                        $v = $subClassName::inflateSingleArray($v);
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
