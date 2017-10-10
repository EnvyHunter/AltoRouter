<?php
namespace HakimCh\Http\Tests;

use Iterator;

class SimpleTraversable implements Iterator
{
    protected $position = 0;

    protected $data = array(
        array('GET', '/foo', 'foo_action', null),
        array('POST', '/bar', 'bar_action', 'second_route')
    );

    public function current()
    {
        return $this->data[$this->position];
    }
    public function key()
    {
        return $this->position;
    }
    public function next()
    {
        ++$this->position;
    }
    public function rewind()
    {
        $this->position = 0;
    }
    public function valid()
    {
        return isset($this->data[$this->position]);
    }
}
