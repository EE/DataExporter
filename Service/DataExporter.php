<?php

namespace EE\DataExporterBundle\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * @author Piotr Antosik <mail@piotrantosik.com>
 * @version 0.2
 */
class DataExporter
{

    protected $columns;
    protected $data;
    protected $format;
    protected $separator;
    protected $escape;
    protected $fileName;
    protected $supportedFormat = array('csv', 'xls');

    /**
     * @param string            $separator
     */
    public function __construct() {
    }

    /**
     * @param       $format
     * @param array $options
     *
     * @throws \RuntimeException
     */
    public function setOptions($format, $options = array())
    {
        if (!in_array($format, $this->supportedFormat)) {
            throw new \RuntimeException(sprintf('The format %s is not supported', $format));
        }

        $this->format = $format;

        if ('csv' === $format) {
            //options for csv
            array_key_exists('separator', $options) ? $this->separator = $options['separator'] : $this->separator = ',';
            array_key_exists('escape', $options) ? $this->escape = $options['escape'] : '\\';
            $this->data = array();
        }
        else if ('xls' === $format) {
            //options for xls
            $this->openXLS();
        }

        //fileName
        array_key_exists('fileName', $options) ? $this->fileName = $options['fileName'].'.'.$this->format : $this->fileName = 'Data export'.'.'.$this->format;
    }

    public function openXLS()
    {
        $this->data = "<!DOCTYPE ><html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><meta name=\"ProgId\" content=\"Excel.Sheet\"><meta name=\"Generator\" content=\"https://github.com/EE/DataExporter\"></head><body><table>";
    }

    public function closeXLS() {
        $this->data .= "</table></body></html>";
    }

    /**
     * @param $data
     */
    public function setData($data)
    {
        if (empty($this->format)) {
            throw new \RuntimeException(sprintf('First use setOptions!'));
        }
        if (empty($this->columns)) {
            throw new \RuntimeException(sprintf('First use addColumns to set export column!'));
        }

        foreach ($data as $row)
        {
            switch ($this->format) {
                case 'csv':
                    $tempRow = array();
                    break;
                case 'xls':
                    $tempRow = '';
                    break;
            }

            if (is_object($row)) {
                foreach ($this->columns as $key) {
                    $method = 'get'. ucfirst($key);
                    if (method_exists($row, $method)) {
                        $temp_val = $row->$method();
                        if ($temp_val === null)
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
                        if ($temp_val === null)
                            $temp_val = ' ';

                        $temp_val = strip_tags(str_replace($this->separator, ' ', $temp_val));
                        $tempRow[] = $temp_val;
                    }

                }

            }

            switch ($this->format) {
                case 'csv':
                    $this->data[] = implode($this->separator, $tempRow);
                    break;
                case 'xls':
                    $this->data .= '<tr>';
                    foreach ($tempRow as $val) {
                        $this->data .= '<td>'.$val.'</td>';
                    }
                    $this->data .= '</tr>';
                    break;
            }

        }

    }

    /**
     * @param array $columns
     */
    public function addColumns(Array $columns) {

        if (empty($this->format)) {
            throw new \RuntimeException(sprintf('First use setOptions!'));
        }

        foreach ($columns as $key => $column) {
            $this->columns[] = $key;

            if ('csv' === $this->format) {
                if (isset($this->data[0]))
                    $this->data[0] = $this->data[0] . $column.$this->separator;
                else
                    $this->data[] = $column.$this->separator;
            }
            elseif ('xls' === $this->format) {

                reset($columns);
                if ($key === key($columns))
                    $this->data .= '<tr>';

                $this->data .= sprintf('<td>%s</td>', $column);

                end($columns);
                if ($key === key($columns))
                    $this->data .= '</tr>';
            }
        }

    }


    /**
     * @return string
     */
    public function prepareCSV()
    {
        return implode("\n", $this->data);
    }

    /**
     * @param array $option
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render()
    {

        $response = new Response;

        switch ($this->format) {
            case 'csv':
                $response->headers->set('Content-Type', 'text/csv');
                $response->setContent($this->prepareCSV());
                break;
            case 'xls':
                //close tags
                $this->closeXLS();
                $response->headers->set('Content-Type', 'application/vnd.ms-excel');
                $response->setContent($this->data);
                break;
        }

        $response->headers->set('Cache-Control', 'public');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$this->fileName.'"');

        return $response;

    }
}
