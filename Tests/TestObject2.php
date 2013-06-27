<?php

namespace EE\DataExporterBundle\Tests;

class TestObject2
{
    private $col1;

    public function __construct()
    {
        $this->col1 = 'Object two';
    }

    public function setCol1($col1)
    {
        $this->col1 = $col1;
    }

    public function getCol1()
    {
        return $this->col1;
    }
}
