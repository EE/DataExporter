<?php

namespace EE\DataExporterBundle\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * @author  Piotr Antosik <mail@piotrantosik.com>
 * @version 0.4.2
 */
class DataExporter
{
    /**
     * @var array
     */
    protected $columns;
    /**
     * @var array
     */
    protected $data;
    /**
     * @var string
     */
    protected $format;
    /**
     * @var string
     */
    protected $separator;
    /**
     * @var string
     */
    protected $escape;
    /**
     * @var string
     */
    protected $fileName;
    /**
     * @var boolean
     */
    protected $memory;
    /**
     * @var boolean
     */
    protected $skipHeader;
    /**
     * @var string
     */
    protected $charset;
    /**
     * @var array
     */
    protected $supportedFormat = array('csv', 'xls', 'html', 'xml', 'json');
    /**
     * @var array
     */
    protected $hooks = array();

    /**
     * @param $format
     * @param array $options
     * @return $this
     * @throws \RuntimeException
     */
    public function setOptions($format, $options = array())
    {
        if (!in_array(strtolower($format), $this->supportedFormat)) {
            throw new \RuntimeException(sprintf('The format %s is not supported', $format));
        }

        $this->format = strtolower($format);

        $this->charset = isset($options['charset']) ? $options['charset'] : 'utf-8';

        if ('csv' === $this->format) {
            //options for csv
            array_key_exists('separator', $options) ? $this->separator = $options['separator'] : $this->separator = ',';
            array_key_exists('escape', $options) ? $this->escape = $options['escape'] : '\\';
            $this->data = array();
        } elseif ('xls' === $this->format) {
            //options for xls
            $this->openXLS();
        } elseif ('html' === $this->format) {
            //options for html
            $this->openHTML();
        } elseif ('xml' === $this->format) {
            //options for xml
            $this->openXML();
        }

        //convert key and values to lowercase
        $options = array_change_key_case($options, CASE_LOWER);
        $options = array_map('strtolower', $options);

        //fileName
        if (array_key_exists('filename', $options)) {
            $this->fileName = $options['filename'] . '.' . $this->format;
            unset($options['filename']);
        } else {
            $this->fileName = 'Data export' . '.' . $this->format;
        }
        //memory option
        in_array(
            'memory',
            $options
        ) ? $this->memory = true : false;

        //skip header
        in_array(
            'skip_header',
            $options
        ) ? $this->skipHeader = true : false;

        if ($this->skipHeader && !($this->format === 'csv')) {
            throw new \RuntimeException('Only CSV support skip_header option!');
        }

        return $this;
    }

    /**
     * @return $this
     */
    public function openXML()
    {
        $this->data = '<?xml version="1.0" encoding="' . $this->charset . '"?><table>';

        return $this;
    }

    /**
     * @return $this
     */
    public function closeXML()
    {
        $this->data .= "</table>";

        return $this;
    }

    /**
     * @return $this
     */
    public function openXLS()
    {
        $this->data = "<!DOCTYPE ><html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=" . $this->charset . "\" /><meta name=\"ProgId\" content=\"Excel.Sheet\"><meta name=\"Generator\" content=\"https://github.com/EE/DataExporter\"></head><body><table>";

        return $this;
    }

    /**
     * @return $this
     */
    public function closeXLS()
    {
        $this->data .= "</table></body></html>";

        return $this;
    }

    /**
     * @return $this
     */
    public function openHTML()
    {
        $this->data = "<!DOCTYPE ><html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=" . $this->charset . "\" /><meta name=\"Generator\" content=\"https://github.com/EE/DataExporter\"></head><body><table>";

        return $this;
    }

    /**
     * @return $this
     */
    public function closeHTML()
    {
        $this->data .= "</table></body></html>";

        return $this;
    }

    /**
     * @param $data
     * @param $separator
     * @param $escape
     * @param $column
     * @param $hooks
     * @param $format
     * @return string
     */
    public static function escape($data, $separator, $escape, $column, $hooks, $format)
    {
        //check for hook
        if (array_key_exists($column, $hooks)) {
            //check for closure
            if (false === is_array($hooks[$column])) {
                $data = $hooks[$column]($data);
            } else {
                if (is_object($hooks[$column][0])) {
                    $obj = $hooks[$column][0];
                } else {
                    $obj = new $hooks[$column][0];
                }
                $data = $obj->$hooks[$column][1]($data);
            }
        }

        //replace new line character
        $data = preg_replace("/\r\n|\r|\n/", ' ', $data);

        $data = mb_ereg_replace(
            sprintf('%s', $separator),
            sprintf('%s', $escape),
            $data
        );

        if ('xml' === $format) {
            if (version_compare(phpversion(), '5.4.0', '>=')) {
                $data = htmlspecialchars($data, ENT_XML1);
            } else {
                $data = htmlspecialchars($data);
            }
        }


        return $data;
    }

    /**
     * @param $function
     * @param $column
     * @return $this|bool
     * @throws \BadFunctionCallException
     * @throws \InvalidArgumentException
     * @throws \LengthException
     */
    public function addHook($function, $column)
    {
        //check for closure
        if (false === is_array($function)) {
            $f = new \ReflectionFunction($function);
            if ($f->isClosure()) {
                $this->hooks[$column] = $function;

                return true;
            }
        } else {
            if (2 !== count($function)) {
                throw new \LengthException('Exactly two parameters required!');
            }

            if (!in_array($column, $this->columns)) {
                throw new \InvalidArgumentException(sprintf(
                    "Parameter column must be someone defined in setColumns function!\nRecived: %s\n Expected one of: %s",
                    $function[1],
                    implode(', ', $this->columns)
                ));
            }

            if (!is_callable($function)) {
                throw new \BadFunctionCallException(sprintf(
                    'Function %s in class %s non exist!',
                    $function[1],
                    $function[0]
                ));
            }

            $this->hooks[$column] = array($function[0], $function[1]);
        }

        return $this;
    }

    /**
     * @param $rows
     * @return $this
     * @throws \RuntimeException
     */
    public function setData($rows)
    {
        if (empty($this->format)) {
            throw new \RuntimeException('First use setOptions!');
        }
        if (empty($this->columns)) {
            throw new \RuntimeException('First use setColumns to set columns to export!');
        }

        $accessor = PropertyAccess::getPropertyAccessor();
        $separator = $this->separator;
        $escape = $this->escape;
        $hooks = $this->hooks;
        $format = $this->format;

        foreach ($rows as $row) {
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

            $tempRow = array_map(
                function ($column) use ($row, $accessor, $separator, $escape, $hooks, $format) {
                    return DataExporter::escape(
                        $accessor->getValue($row, $column),
                        $separator,
                        $escape,
                        $column,
                        $hooks,
                        $format
                    );
                },
                $this->columns
            );

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
                        $this->data .= '<td>' . $val . '</td>';
                    }
                    $this->data .= '</tr>';
                    break;
                case 'xml':
                    $this->data .= '<row>';
                    $i = 0;
                    foreach ($tempRow as $val) {
                        $this->data .= '<column name="' . $this->columns[$i] . '">' . $val . '</column>';
                        $i++;
                    }
                    $this->data .= '</row>';
                    break;
            }

        }

        return $this;
    }

    /**
     * @param array $columns
     * @return $this
     * @throws \RuntimeException
     */
    public function setColumns(Array $columns)
    {

        if (empty($this->format)) {
            throw new \RuntimeException(sprintf('First use setOptions!'));
        }

        foreach ($columns as $key => $column) {
            if (is_integer($key)) {
                $this->columns[] = $column;
            } else {
                $this->columns[] = $key;
            }

            if ('csv' === $this->format && !$this->skipHeader) {

                //last item
                if (isset($this->data[0])) {
                    //last item
                    end($columns);
                    if ($key != key($columns)) {
                        $this->data[0] = $this->data[0] . $column . $this->separator;
                    } else {
                        $this->data[0] = $this->data[0] . $column;
                    }
                } else {
                    $this->data[] = $column . $this->separator;
                }
            } elseif ('xls' === $this->format || 'html' === $this->format) {
                //first item
                reset($columns);
                if ($key === key($columns)) {
                    $this->data .= '<tr>';
                }

                $this->data .= sprintf('<td>%s</td>', $column);
                //last item
                end($columns);
                if ($key === key($columns)) {
                    $this->data .= '</tr>';
                }
            } elseif ('json' === $this->format) {
                $this->data[0] = array_values($columns);
            }
        }

        return $this;
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
     *
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
                $response->headers->set('Content-Type', 'application/xml');
                $response->setContent($this->data);
                break;
        }

        if ($this->memory) {
            return $response->getContent();
        }

        $response->headers->set('Cache-Control', 'public');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $this->fileName . '"');

        return $response;

    }
}
