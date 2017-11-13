<?php
/**
 * Created by PhpStorm.
 * User: irelance
 * Date: 17-11-10
 * Time: 上午10:28
 */

namespace Irelance\Ci3\Model;

use ArrayAccess;
use Iterator;
use Countable;
use JsonSerializable;

class Set implements ArrayAccess, Iterator, Countable, JsonSerializable
{
    protected $_data = [];
    protected $_key = 0;

    public function count()
    {
        return count($this->_data);
    }

    public function offsetExists($offset)
    {
        return isset($this->_data[$offset]);
    }

    public function offsetSet($offset, $value)
    {
        $this->_data[$offset] = $value;
    }

    public function offsetGet($offset)
    {
        return $this->_data[$offset];
    }

    public function offsetUnset($offset)
    {
        unset($this->_data[$offset]);
    }

    public function rewind()
    {
        $this->_key = 0;
    }

    public function valid()
    {
        return isset($this->_data[$this->_key]);
    }

    public function next()
    {
        $this->_key++;
    }

    public function current()
    {
        return $this->_data[$this->_key];
    }

    public function key()
    {
        return $this->_key;
    }

    public function toArray()
    {
        return $this->_data;
    }

    public function jsonSerialize()
    {
        return $this->toArray();
    }
}