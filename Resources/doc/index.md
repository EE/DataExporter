EEDataExporter
-------------

[![Build Status](https://api.travis-ci.org/Antek88/DataExporter.png?branch=master)](http://travis-ci.org/Antek88/DataExporter)

## Installation

### Download EEDataExporterBundle using composer
```js
{
    "require": {
        "ee/dataexporter-bundle": "*"
    }
}
```
### Enable the bundle in the kernel
```php
public function registerBundles()
{
    $bundles = array(
        // ...
        new EE\DataExporterBundle\EEDataExporterBundle(),
    );
}
```

## Using

It's possible to use DataExporter with nested array or object.
Example columns definition with array:
```php
[col1] is equals with accessing $array['col1']
eg. [col1][col2] is equals with $array['col1']['col2']
```
With object:
```php
col1 is equals with $object->getCol1(), hasCol1(), isCol1(), $object->col1 or magic method __get('col1')
```

### Render to memory
Maybe sometime you a need render data to variable.
EEDataExporter support this. You must set parameter `memory` into setOption eg.:
```php
$exporter->setOptions('csv', array('fileName' => 'file', 'separator' => ';', 'memory'));
//set data...
$var = $exporter->render();
```

### Skip header in CSV format
If you want skip columns name in CSV format use flag skip_header in setOptions eg.:
```php
$exporter->setOptions('csv', array('fileName' => 'file', 'separator' => ';', 'memory', 'skip_header'));
```

### Hook a column
Sometimes we may need customizing data before adding it to the document.
Our exporter support this! Just use the function addHook.
addHook expected two or one parameters.
 - first parameter is a function (object) that we want use, second is a column name, eg.:
```php
$exporter->addHook(array('EE\DataExporterBundle\Test\Service\DataExporterTest', 'hookTest'), '[col1]');
$exporter->addHook(array(&$this, 'hookTest2'), '[col3]');
```

 - EEDataExporter support closure as parameter eg.:
```php
$f = function($parm){
        if ($parm instanceof \DateTime) {
            return $parm->format('Y-m-d');
        }
        else {
            return '';
        }
    };

    $exporter->addHook($f, '[colName]');
```
```php
    $exporter->addHook(function($p){return ucfirst($p);}, '[colName]');
```

It is possible to set multiple hooks on multiple columns, but only one for each of them.

### Usage example from array:

```php
$exporter = $this->get('ee.dataexporter');
$exporter->setOptions('csv', array('fileName' => 'file', 'separator' => ';'));
$exporter->setColumns(array('[col1]', '[col2]', '[col3]'));
$exporter->setData(array(
        array('col1' => '1a', 'col2' => '1b', 'col3' => '1c'),
        array('col1' => '2a', 'col2' => '2b'),
    ));

return $exporter->render();
```

### And from object:

```php
$exporter = $this->get('ee.dataexporter');
$testObject = new TestObject();

$exporter->setOptions('xls', array('fileName' => 'file'));
$exporter->setColumns(array('col1' => 'Label1', 'col2' => 'Label2', 'col3.col1' => 'From object two'));
$exporter->setData(array($testObject));

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
```
