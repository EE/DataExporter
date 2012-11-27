EEDataExporter
-------------
-------------

Usage example:

```php
$exporter = $this->get('ee.dataexporter');
$exporter->addColumn(array('col1' => 'Kolumna 1'));
$exporter->addColumn(array('col2' => 'Kolumna 2'));
$exporter->setData(array(
        array('col1' => 'data1', 'col2' => 'data1a'),
        array('col1' => 'data2', 'col2' => 'data2a')
    ));

return $exporter->render();
```