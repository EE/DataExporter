EEDataExporter
-------------

## Installation

### Download EEDataexporterBundle using composer
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

Usage example from array:

```php
$exporter = $this->get('ee.dataexporter');
$exporter->setOptions('csv', array('separator' => ',', 'fileName' => 'file'));
$exporter->addColumns(array('col1' => 'Column 1 label',
                      'col2' => 'Column 2 label'
                     ));

$exporter->setData(array(
        array('col1' => 'data1', 'col2' => 'data1a'),
        array('col1' => 'data2', 'col2' => 'data2a')
    ));

return $exporter->render();
```

And from object:

```php
$exporter = $this->get('ee.dataexporter');
$testObject = new TestObject();

$exporter->setOptions('xls', array('fileName' => 'file'));
$exporter->addColumns(array('col1' => 'Label1', 'col2' => 'Label2'));
$exporter->setData(array($testObject));
return $exporter->render();

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
```