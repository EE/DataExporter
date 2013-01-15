<?php

namespace EE\DataExporterBundle\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @author Piotr Antosik <mail@piotrantosik.com>
 * @version 0.3
 */
class DataExporter
{
    protected $columns;
    protected $data;
    protected $format;
    protected $separator;
    protected $escape;
    protected $fileName;
    protected $supportedFormat = array('csv', 'xls', 'html', 'xml', 'json');

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
        else if ('html' === $format) {
            //options for html
            $this->openHTML();
        }
        else if ('xml' === $format) {
            //options for xml
            $this->openXML();
        }

        //fileName
        array_key_exists('fileName', $options) ? $this->fileName = $options['fileName'].'.'.$this->format : $this->fileName = 'Data export'.'.'.$this->format;
    }

    public function openXML()
    {
        $this->data = '<?xml version="1.0" encoding="UTF-8"?><table>';
    }

    public function closeXML()
    {
        $this->data .= "</table>";
    }

    public function openXLS()
    {
        $this->data = "<!DOCTYPE ><html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><meta name=\"ProgId\" content=\"Excel.Sheet\"><meta name=\"Generator\" content=\"https://github.com/EE/DataExporter\"></head><body><table>";
    }

    public function closeXLS() {
        $this->data .= "</table></body></html>";
    }

    public function openHTML()
    {
        $this->data = "<!DOCTYPE ><html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><meta name=\"Generator\" content=\"https://github.com/EE/DataExporter\"></head><body><table>";
    }

    public function closeHTML() {
        $this->data .= "</table></body></html>";
    }

    public function escape($data)
    {
        $data = mb_ereg_replace(
            sprintf('(%s)', $this->separator),
            sprintf('%s\1', $this->escape),
            $data
        );

        return $data;
    }

    /**
     * @param $rows
     */
    public function setData($rows)
    {
        if (empty($this->format)) {
            throw new \RuntimeException(sprintf('First use setOptions!'));
        }
        if (empty($this->columns)) {
            throw new \RuntimeException(sprintf('First use addColumns to set export column!'));
        }

        $accessor = PropertyAccess::getPropertyAccessor();

        foreach ($rows as $row)
        {
            switch ($this->format) {
                case 'csv':
                case 'json':
                    $tempRow = array();
                    break;
                case 'xls':
                case 'html':
                case 'xml':
                    $tempRow = '';
                    break;
            }

            $tempRow = array_map(function ($column) use ($row, $accessor) {
                    return DataExporter::escape($accessor->getValue($row, $column));
                }, $this->columns);

            switch ($this->format) {
                case 'csv':
                    $this->data[] = implode($this->separator, $tempRow);
                    break;
                case 'json':
                    $this->data[] = array_combine($this->data[0], $tempRow);
                    break;
                case 'xls':
                case 'html':
                    $this->data .= '<tr>';
                    foreach ($tempRow as $val) {
                        $this->data .= '<td>'.$val.'</td>';
                    }
                    $this->data .= '</tr>';
                    break;
                case 'xml':
                    $this->data .= '<row>';
                    $i = 0;
                    foreach ($tempRow as $val) {
                        $this->data .= '<column name="'.$this->columns[$i].'">'.$val.'</column>';
                        $i++;
                    }
                    $this->data .= '</row>';
                    break;
            }

        }

    }

    /**
     * @param array $columns
     */
    public function setColumns(Array $columns) {

        if (empty($this->format)) {
            throw new \RuntimeException(sprintf('First use setOptions!'));
        }

        foreach ($columns as $key => $column) {
            if (is_integer($key)) {
                $this->columns[] = $column;
            }
            else {
                $this->columns[] = $key;
            }

            if ('csv' === $this->format) {

                //last item
                if (isset($this->data[0])) {
                    //last item
                    end($columns);
                    if ($key != key($columns)) {
                        $this->data[0] = $this->data[0] . $column . $this->separator;
                    }
                    else {
                        $this->data[0] = $this->data[0] . $column;
                    }
                }
                else {
                    $this->data[] = $column . $this->separator;
                }
            }
            elseif ('xls' === $this->format || 'html' === $this->format) {
                //first item
                reset($columns);
                if ($key === key($columns))
                    $this->data .= '<tr>';

                $this->data .= sprintf('<td>%s</td>', $column);
                //last item
                end($columns);
                if ($key === key($columns))
                    $this->data .= '</tr>';
            }
            elseif ('json' === $this->format) {
                $this->data[0] = array_values($columns);
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
            case 'json':
                $response->headers->set('Content-Type', 'application/json');
                //remove first row from data
                unset($this->data[0]);
                $response->setContent(json_encode($this->data));
                break;
            case 'xls':
                //close tags
                $this->closeXLS();
                $response->headers->set('Content-Type', 'application/vnd.ms-excel');
                $response->setContent($this->data);
                break;
            case 'html':
                //close tags
                $this->closeHTML();
                $response->headers->set('Content-Type', 'text/html');
                $response->setContent($this->data);
                break;
            case 'xml':
                //close tags
                $this->closeXML();
                $response->headers->set('Content-Type', 'text/xml');
                $response->setContent($this->data);
                break;
        }

        $response->headers->set('Cache-Control', 'public');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$this->fileName.'"');

        return $response;

    }
}
