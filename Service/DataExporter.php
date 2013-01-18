<?php

namespace EE\DataExporterBundle\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @author  Piotr Antosik <mail@piotrantosik.com>
 * @version 0.4
 */
class DataExporter
{

    /**
     * @var array
     */
    private $columns = array();
    private $data;
    /**
     * @var string
     */
    private $format;
    /**
     * @var string
     */
    private $separator;
    /**
     * @var string
     */
    private $escape;
    /**
     * @var string
     */
    private $fileName;
    /**
     * @var boolean
     */
    private $memory = false;
    /**
     * @var array
     */
    private $supportedFormat = array( 'csv', 'xls', 'html', 'xml', 'json' );
    /**
     * @var array
     */
    private $hooks = array();

    /**
     * @param       $format
     * @param array $options
     *
     * @throws \RuntimeException
     */
    public function setOptions($format, $options = array())
    {
        if (!in_array(strtolower($format), $this->getSupportedFormat())) {
            throw new \RuntimeException( sprintf('The format %s is not supported', $format) );
        }

        $this->setFormat(strtolower($format));

        if ('csv' === $format) {
            //options for csv
            array_key_exists('separator', $options) ? $this->setSeparator($options['separator']) : $this->setSeparator(
                ','
            );
            array_key_exists('escape', $options) ? $this->setEscape($options['escape']) : $this->setEscape('\\');
            $this->data = array();
        } elseif ('xls' === $format) {
            //options for xls
            $this->openXLS();
        } elseif ('html' === $format) {
            //options for html
            $this->openHTML();
        } elseif ('xml' === $format) {
            //options for xml
            $this->openXML();
        }

        //convert key and values to lowercase
        $options = array_change_key_case($options, CASE_LOWER);
        $options = array_map('strtolower', $options);

        //fileName
        if (array_key_exists('filename', $options)) {
            $this->setFileName($options['filename'] . '.' . $this->getFormat());
            unset( $options['filename'] );
        } else {
            $this->setFileName('Data export' . '.' . $this->getFormat());
        }
        //memory option
        in_array(
            'memory',
            $options
        ) ? $this->setMemory(true) : false;
    }

    public function openXML()
    {
        $this->addToData('<?xml version="1.0" encoding="UTF-8"?><table>');
    }

    public function closeXML()
    {
        $this->addToData("</table>");
    }

    public function openXLS()
    {
        $this->addToData("<!DOCTYPE ><html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><meta name=\"ProgId\" content=\"Excel.Sheet\"><meta name=\"Generator\" content=\"https://github.com/EE/DataExporter\"></head><body><table>");
    }

    public function closeXLS()
    {
        $this->addToData("</table></body></html>");
    }

    public function openHTML()
    {
        $this->addToData("<!DOCTYPE ><html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=utf-8\" /><meta name=\"Generator\" content=\"https://github.com/EE/DataExporter\"></head><body><table>");
    }

    public function closeHTML()
    {
        $this->addToData("</table></body></html>");
    }

    /**
     * @param $data
     * @param $separator
     * @param $escape
     * @param $column
     * @param $hooks
     *
     * @return string
     */
    public static function escape($data, $separator, $escape, $column, $hooks)
    {
        $data = mb_ereg_replace(
            sprintf('(%s)', $separator),
            sprintf('%s\1', $escape),
            $data
        );

        //check for hook
        if (array_key_exists($column, $hooks)) {
            $obj  = new $hooks[$column][0];
            $data = $obj->$hooks[$column][1]($data);
        }

        return $data;
    }

    /**
     * @param array $function
     * @param       $column
     *
     * @throws \BadFunctionCallException
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     * @throws \LengthException
     */
    public function addHook(Array $function, $column)
    {
        if (2 !== count($function)) {
            throw new \LengthException( 'Exactly two parameters required!' );
        }

        if (!in_array($column, $this->getColumns())) {
            throw new \InvalidArgumentException( sprintf(
                "Parameter column must be someone defined in setColumns function!\nRecived: %s\n Expected one of: %s",
                $function[1],
                implode(', ', $this->getColumns())
            ) );
        }

        if (!is_callable($function)) {
            throw new \BadFunctionCallException( sprintf(
                'Function %s in class %s non exist!',
                $function[1],
                $function[0]
            ) );
        }

        $object = new $function[0];
        $result = $object->$function[1]('test');

        if (!is_string($result)) {
            throw new \UnexpectedValueException( sprintf(
                "Function %s in class %s not return a string! \nReturn: " . gettype($result),
                $function[1],
                $function[0]
            ) );
        }

        $this->hooks[$column] = array( $function[0], $function[1] );
    }

    /**
     * @param $rows
     */
    public function setData($rows)
    {
        if (empty( $this->format )) {
            throw new \RuntimeException( 'First use setOptions!' );
        }
        if (empty( $this->columns )) {
            throw new \RuntimeException( 'First use setColumns to set columns to export!' );
        }

        $accessor  = PropertyAccess::getPropertyAccessor();
        $separator = $this->getSeparator();
        $escape    = $this->getEscape();
        $hooks     = $this->getHooks();

        foreach ($rows as $row) {
            switch ($this->getFormat()) {
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

            $tempRow = array_map(
                function ($column) use ($row, $accessor, $separator, $escape, $hooks) {
                    return DataExporter::escape(
                        $accessor->getValue($row, $column),
                        $separator,
                        $escape,
                        $column,
                        $hooks
                    );
                },
                $this->getColumns()
            );

            switch ($this->getFormat()) {
                case 'csv':
                    $this->addData(implode($this->getSeparator(), $tempRow));
                    break;
                case 'json':
                    $this->addData(array_combine($this->data[0], $tempRow));
                    break;
                case 'xls':
                case 'html':
                    $this->addToData('<tr>');
                    foreach ($tempRow as $val) {
                        $this->addToData('<td>' . $val . '</td>');
                    }
                    $this->addToData('</tr>');
                    break;
                case 'xml':
                    $this->addToData('<row>');
                    $i = 0;
                    foreach ($tempRow as $val) {
                        $this->addToData('<column name="' . $this->columns[$i] . '">' . $val . '</column>');
                        $i++;
                    }
                    $this->addToData('</row>');
                    break;
            }

        }

    }

    /**
     * @param array $columns
     */
    public function setColumns(Array $columns)
    {

        if (empty( $this->format )) {
            throw new \RuntimeException( sprintf('First use setOptions!') );
        }

        foreach ($columns as $key => $column) {
            if (is_integer($key)) {
                $this->addColumn($column);
            } else {
                $this->addColumn($key);
            }

            if ('csv' === $this->getFormat()) {

                //last item
                if (isset( $this->data[0] )) {
                    //last item
                    end($columns);
                    if ($key != key($columns)) {
                        $this->data[0] = $this->data[0] . $column . $this->getSeparator();
                    } else {
                        $this->data[0] = $this->data[0] . $column;
                    }
                } else {
                    $this->addData($column . $this->getSeparator());
                }
            } elseif ('xls' === $this->getFormat() || 'html' === $this->getFormat()) {
                //first item
                reset($columns);
                if ($key === key($columns)) {
                    $this->addToData('<tr>');
                }

                $this->addToData(sprintf('<td>%s</td>', $column));
                //last item
                end($columns);
                if ($key === key($columns)) {
                    $this->addToData('</tr>');
                }
            } elseif ('json' === $this->getFormat()) {
                $this->data[0] = array_values($columns);
            }
        }

    }

    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @return string
     */
    public function prepareCSV()
    {
        return implode("\n", $this->getData());
    }

    /**
     * @param array $option
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function render()
    {

        $response = new Response;

        switch ($this->getFormat()) {
            case 'csv':
                $response->headers->set('Content-Type', 'text/csv');
                $response->setContent($this->prepareCSV());
                break;
            case 'json':
                $response->headers->set('Content-Type', 'application/json');
                //remove first row from data
                unset( $this->data[0] );
                $response->setContent(json_encode($this->getData()));
                break;
            case 'xls':
                //close tags
                $this->closeXLS();
                $response->headers->set('Content-Type', 'application/vnd.ms-excel');
                $response->setContent($this->getData());
                break;
            case 'html':
                //close tags
                $this->closeHTML();
                $response->headers->set('Content-Type', 'text/html');
                $response->setContent($this->getData());
                break;
            case 'xml':
                //close tags
                $this->closeXML();
                $response->headers->set('Content-Type', 'text/xml');
                $response->setContent($this->getData());
                break;
        }

        if ($this->getMemory()) {
            return $response->getContent();
        }

        $response->headers->set('Cache-Control', 'public');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $this->getFileName() . '"');

        return $response;

    }

    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string $escape
     */
    public function setEscape($escape)
    {
        $this->escape = $escape;
    }

    /**
     * @return string
     */
    public function getEscape()
    {
        return $this->escape;
    }

    /**
     * @param string $fileName
     */
    public function setFileName($fileName)
    {
        $this->fileName = $fileName;
    }

    /**
     * @return string
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    /**
     * @param string $format
     */
    public function setFormat($format)
    {
        $this->format = $format;
    }

    /**
     * @return string
     */
    public function getFormat()
    {
        return $this->format;
    }

    /**
     * @return array
     */
    public function getHooks()
    {
        return $this->hooks;
    }

    /**
     * @param boolean $memory
     */
    public function setMemory($memory)
    {
        $this->memory = $memory;
    }

    /**
     * @return boolean
     */
    public function getMemory()
    {
        return $this->memory;
    }

    /**
     * @param string $separator
     */
    public function setSeparator($separator)
    {
        $this->separator = $separator;
    }

    /**
     * @return string
     */
    public function getSeparator()
    {
        return $this->separator;
    }

    /**
     * @return array
     */
    public function getSupportedFormat()
    {
        return $this->supportedFormat;
    }

    private function addColumn($column)
    {
        $this->columns[] = $column;
    }

    private function addData($data)
    {
        $this->data[] = $data;
    }

    private function addToData($data)
    {
        $this->data .= $data;
    }
}
