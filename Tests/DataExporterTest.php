<?php

namespace EE\DataExporterBundle\Test\Service;

use EE\DataExporterBundle\Service\DataExporter;

class DataExporterTest extends \PHPUnit_Framework_TestCase
{
    public function testCSVExport()
    {
        $exporter = new DataExporter();
        $exporter->setOptions('csv', array('fileName' => 'file', 'separator' => ';'));
        $exporter->addColumns(array('test1', 'test2'));
        $exporter->setData(array(
                array('1a', '1b'),
                array('2a', '2b'),
            ));

        $result = "test1;test2\n1a;1b\n2a;2b";

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

}