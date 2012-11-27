<?php

namespace EE\DataExporterBundle\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Doctrine\Common\Collections\ArrayCollection;

class DataExporter
{
    private $columns;
    private $columnsLabel;

    private $csv = array();
    private $separator;

    /**
     * @param string            $separator
     */
    public function __construct($separator = ',')
    {
        $this->separator =  isset($separator) ? $separator : ',';
    }

    /**
     * @param $data
     */
    public function loadData($data)
    {
        $this->data = $data;
    }

    public function setData($data)
    {
        foreach ($data as $row)
        {
            $tempRow = array();
            if (is_object($row)) {
                foreach ($this->columns as $key) {
                    $method = 'get'. ucfirst($key);
                    if (method_exists($row, $method)) {
                        $temp_val = $row->$method();
                        if ($temp_val == null)
                            $temp_val = ' ';
                        $temp_val = strip_tags(str_replace($this->separator, ' ', $temp_val));
                        $tempRow[] = $temp_val;
                    }
                }

            }
            else {
                foreach ($this->columns as $key)
                {

                    if (array_key_exists($key, $row)) {
                        $temp_val = $row[$key];
                        if ($temp_val == null)
                            $temp_val = ' ';
                        $temp_val = strip_tags(str_replace($this->separator, ' ', $temp_val));
                        $tempRow[] = $temp_val;
                    }

                }

            }
            $this->addToCSV($tempRow);
        }

    }

    /**
     * @param array $columns
     */
    public function setColumns(ArrayCollection $columns)
    {
        foreach ($columns as $column)
        {
            $this->columns[] = key($column);
            if ($column != $columns->last())
                $this->columnsLabel .= $column[key($column)].$this->separator;
            else
                $this->columnsLabel .= $column[key($column)];
        }
        $this->csv[] = $this->columnsLabel;
    }

    public function addColumn($column)
    {
        //klucze
        $this->columns[] = key($column);

        if (isset($this->csv[0]))
            $this->csv[0] = $this->csv[0] . $column[key($column)].$this->separator;
        else
            $this->csv[] = $column[key($column)].$this->separator;
    }

    public function addToCSV($data)
    {
        $this->csv[] = implode($this->separator, $data);
    }

    /**
     * @return string
     * @throws \LogicException
     */
    public function prepareCSV()
    {
        if (null === $this->columns)
             throw new \LogicException('Brak zdefiniowanych kolumn pliku CSV. Użyj setColumns()');

        return implode("\n", $this->csv);
    }

    /**
     * @param array $option
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render(Array $option = null)
    {
        $filename = isset($option['filename']) ? $option['filename'] : 'Plik CSV';
        $filename .= '.csv';

        $response = new Response;
        $response->headers->set('Cache-Control', 'public');
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');
        $response->setContent($this->prepareCSV());

        return $response;

    }
}
