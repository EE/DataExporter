<?php

namespace EE\DataExporterBundle\Tests;

use EE\DataExporterBundle\Tests\TestObject2;

class TestObject
{
    private $col1;
    private $col2;
    private $col3;

    public function __construct()
    {
        $this->col1 = '1a';
        $this->col2 = '1b';
        $this->col3 = new TestObject2;
    }

    public function setCol2($col2)
    {
        $this->col2 = $col2;
    }

    public function getCol2()
    {
        return $this->col2;
    }

    public function setCol1($col1)
    {
        $this->col1 = $col1;
    }

    public function getCol1()
    {
        return $this->col1;
    }

    public function setCol3($col3)
    {
        $this->col3 = $col3;
    }

    public function getCol3()
    {
        return $this->col3;
    }
}
