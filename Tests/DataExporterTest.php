<?php

namespace EE\DataExporterBundle\Test\Service;

use EE\DataExporterBundle\Service\DataExporter;

class DataExporterTest extends \PHPUnit_Framework_TestCase
{
    public function testCSVExport()
    {
        $exporter = new DataExporter();
        $exporter->setOptions('csv', array('fileName' => 'file', 'separator' => ';'));
        $exporter->addColumns(array('test1', 'test2', 'test3'));
        $exporter->setData(array(
                array('1a', '1b', '1c'),
                array('2a', '2b'),
            ));

        $result = "test1;test2;test3\n1a;1b;1c\n2a;2b";

        $this->assertEquals($result, $exporter->render()->getContent());
    }

    public function testXLSExport()
    {
        $exporter = new DataExporter();
        $exporter->setOptions('xls', array('fileName' => 'file'));
        $exporter->addColumns(array('test1', 'test2'));
        $exporter->setData(array(
                array('1a', '1b'),
                array('2a', '2b'),
            ));

        $result = '<!DOCTYPE ><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><meta name="ProgId" content="Excel.Sheet"><meta name="Generator" content="https://github.com/EE/DataExporter"></head><body><table><tr><td>test1</td><td>test2</td></tr><tr><td>1a</td><td>1b</td></tr><tr><td>2a</td><td>2b</td></tr></table></body></html>';

        $this->assertEquals($result, $exporter->render()->getContent());
    }

    public function testCSVExportFromObject()
    {
        $exporter = new DataExporter();
        $testObject = new TestObject();

        $exporter->setOptions('csv', array('fileName' => 'file', 'separator' => ';'));
        $exporter->addColumns(array('col1' => 'Label1', 'col2' => 'Label2'));
        $exporter->setData(array($testObject));

        $result = "Label1;Label2\n1a;1b";

        $this->assertEquals($result, $exporter->render()->getContent());
    }

    public function testXLSExportFromObject()
    {
        $exporter = new DataExporter();
        $testObject = new TestObject();

        $exporter->setOptions('xls', array('fileName' => 'file'));
        $exporter->addColumns(array('col1' => 'Label1', 'col2' => 'Label2'));
        $exporter->setData(array($testObject));

        $result = '<!DOCTYPE ><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><meta name="ProgId" content="Excel.Sheet"><meta name="Generator" content="https://github.com/EE/DataExporter"></head><body><table><tr><td>Label1</td><td>Label2</td></tr><tr><td>1a</td><td>1b</td></tr></table></body></html>';

        $this->assertEquals($result, $exporter->render()->getContent());
    }

    public function testHTMLExport()
    {
        $exporter = new DataExporter();
        $exporter->setOptions('html', array('fileName' => 'file'));
        $exporter->addColumns(array('test1', 'test2'));
        $exporter->setData(array(
                array('1a', '1b'),
                array('2a', '2b'),
            ));

        $result = '<!DOCTYPE ><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><meta name="Generator" content="https://github.com/EE/DataExporter"></head><body><table><tr><td>test1</td><td>test2</td></tr><tr><td>1a</td><td>1b</td></tr><tr><td>2a</td><td>2b</td></tr></table></body></html>';

        $this->assertEquals($result, $exporter->render()->getContent());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testBadFormatException()
    {
        $exporter = new DataExporter();
        $exporter->setOptions('none');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testSetDataException()
    {
        $exporter = new DataExporter();
        $exporter->setData(array());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testSetData2Exception()
    {
        $exporter = new DataExporter();
        $exporter->setOptions('csv');
        $exporter->setData(array());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testAddColumnsException()
    {
        $exporter = new DataExporter();
        $exporter->addColumns(array());
    }

}

class TestObject
{
    private $col1;
    private $col2;

    public function __construct() {
        $this->col1 = '1a';
        $this->col2 = '1b';
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

}