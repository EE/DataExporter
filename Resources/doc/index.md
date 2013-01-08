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

Usage example:

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