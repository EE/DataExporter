<?php

namespace EE\DataExporterBundle\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * DataExporter class
 *
 * @author  Piotr Antosik <mail@piotrantosik.com>
 * @version Release: 0.4.3
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
    protected $memory = false;

    /**
     * @var boolean
     */
    protected $skipHeader = false;

    /**
     * @var boolean
     */
    protected $allowNull = false;

    /**
     * @var string
     */
    protected $nullReplace = '-';

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
     * @param string $format
     * @param array  $options
     *
     * @return $this
     * @throws \RuntimeException
     */
    public function setOptions($format, $options = array())
    {
        if (false === in_array(strtolower($format), $this->supportedFormat)) {
            throw new \RuntimeException(sprintf('The format %s is not supported', $format));
        }

        $this->format = strtolower($format);
        $this->charset = isset($options['charset']) ? $options['charset'] : 'utf-8';

        switch ($this->format) {
            case 'csv':
                //options for csv
                array_key_exists('separator', $options) ? $this->separator = $options['separator'] : $this->separator = ',';
                array_key_exists('escape', $options) ? $this->escape = $options['escape'] : '\\';
                $this->data = array();
                break;
            case 'xls':
                $this->openXLS();
                break;
            case 'html':
                $this->openHTML();
                break;
            case 'xml':
                $this->openXML();
                break;
        }

        //convert key and values to lowercase
        $options = array_map('strtolower', array_change_key_case($options, CASE_LOWER));

        //fileName
        if (true === array_key_exists('filename', $options)) {
            $this->fileName = $options['filename'] . '.' . $this->format;
            unset($options['filename']);
        } else {
            $this->fileName = 'Data export' . '.' . $this->format;
        }

        //memory option
        if (true === in_array('memory', $options)) {
            $this->memory = true;
        }

        //skip header
        if (true === in_array('skip_header', $options)) {
            $this->skipHeader = true;
        }

        if (true === $this->skipHeader && $this->format !== 'csv') {
            throw new \RuntimeException('Only CSV support skip_header option!');
        }

        //allow null data
        if (true === in_array('allow_null', $options) && true === $options['allow_null']) {
            $this->allowNull = true;

            if (true === in_array('null_replace', $options)) {
                $this->nullReplace = $options['null_replace'];
            }
        }

        return $this;
    }

    /**
     * @return $this
     */
    private function openXML()
    {
        $this->data = '<?xml version="1.0" encoding="' . $this->charset . '"?><table>';

        return $this;
    }

    /**
     * @return $this
     */
    private function closeXML()
    {
        $this->data .= "</table>";

        return $this;
    }

    /**
     * @return $this
     */
    private function openXLS()
    {
        $this->data = "<!DOCTYPE ><html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=" . $this->charset . "\" /><meta name=\"ProgId\" content=\"Excel.Sheet\"><meta name=\"Generator\" content=\"https://github.com/EE/DataExporter\"></head><body><table>";

        return $this;
    }

    /**
     * @return $this
     */
    private function closeXLS()
    {
        $this->data .= "</table></body></html>";

        return $this;
    }

    /**
     * @return $this
     */
    private function openHTML()
    {
        $this->data = "<!DOCTYPE ><html><head><meta http-equiv=\"Content-Type\" content=\"text/html; charset=" . $this->charset . "\" /><meta name=\"Generator\" content=\"https://github.com/EE/DataExporter\"></head><body><table>";

        return $this;
    }

    /**
     * @return $this
     */
    private function closeHTML()
    {
        $this->data .= "</table></body></html>";

        return $this;
    }

    /**
     * @param string $data
     * @param string $separator
     * @param string $escape
     * @param string $column
     * @param array  $hooks
     * @param string $format
     *
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
     * @param array|\Closure  $function
     * @param string          $column
     *
     * @return $this|bool
     * @throws \BadFunctionCallException
     * @throws \InvalidArgumentException
     * @throws \LengthException
     */
    public function addHook($function, $column)
    {
        //check for closure
        if (false === is_array($function)) {
            $functionReflected = new \ReflectionFunction($function);
            if ($functionReflected->isClosure()) {
                $this->hooks[$column] = $function;

                return true;
            }
        } else {
            if (2 !== count($function)) {
                throw new \LengthException('Exactly two parameters required!');
            }

            if (false === in_array($column, $this->columns)) {
                throw new \InvalidArgumentException(sprintf(
                    "Parameter column must be defined in setColumns function!\nRecived: %s\n Expected one of: %s",
                    $function[1],
                    implode(', ', $this->columns)
                ));
            }

            if (false === is_callable($function)) {
                throw new \BadFunctionCallException(sprintf(
                    'Function %s in class %s is non callable!',
                    $function[1],
                    $function[0]
                ));
            }

            $this->hooks[$column] = array($function[0], $function[1]);
        }

        return $this;
    }

    /**
     * @param string $row
     *
     * @return bool
     */
    private function addRow($row)
    {
        $separator = $this->separator;
        $escape = $this->escape;
        $hooks = $this->hooks;
        $format = $this->format;
        $allowNull = $this->allowNull;
        $nullReplace = $this->nullReplace;

        $tempRow = array_map(
            function ($column) use ($row, $separator, $escape, $hooks, $format, $allowNull, $nullReplace) {
                try {
                    $value = PropertyAccess::createPropertyAccessor()->getValue($row, $column);
                } catch (UnexpectedTypeException $exception) {
                    if (true === $allowNull) {
                        $value = $nullReplace;
                    } else {
                        throw $exception;
                    }
                }

                return DataExporter::escape(
                    $value,
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
                $index = 0;
                foreach ($tempRow as $val) {
                    $this->data .= '<column name="' . $this->columns[$index] . '">' . $val . '</column>';
                    $index++;
                }
                $this->data .= '</row>';
                break;
        }

        return true;
    }

    /**
     * @param array $rows
     *
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

        foreach ($rows as $row) {
            $this->addRow($row);
        }

        //close tags
        $this->closeData();

        return $this;
    }

    /**
     * @return $this
     */
    private function closeData()
    {
        switch ($this->format) {
            case 'json':
                //remove first row from data
                unset($this->data[0]);
                break;
            case 'xls':
                $this->closeXLS();
                break;
            case 'html':
                $this->closeHTML();
                break;
            case 'xml':
                $this->closeXML();
                break;
        }

        return $this;
    }

    /**
     * @param array $haystack
     *
     * @return mixed
     */
    private function getLastKeyFromArray(Array $haystack)
    {
        end($haystack);

        return key($haystack);
    }

    /**
     * @param array $haystack
     *
     * @return mixed
     */
    private function getFirstKeyFromArray(Array $haystack)
    {
        reset($haystack);

        return key($haystack);
    }

    /**
     * @param string  $column
     * @param integer $key
     * @param array   $columns
     *
     * @return $this
     */
    private function setColumn($column, $key, $columns)
    {
        if (true === is_integer($key)) {
            $this->columns[] = $column;
        } else {
            $this->columns[] = $key;
        }

        if ('csv' === $this->format && false === $this->skipHeader) {
            //last item
            if (isset($this->data[0])) {
                //last item
                if ($key != $this->getLastKeyFromArray($columns)) {
                    $this->data[0] = $this->data[0] . $column . $this->separator;
                } else {
                    $this->data[0] = $this->data[0] . $column;
                }
            } else {
                $this->data[] = $column . $this->separator;
            }
        } elseif (true === in_array($this->format, array('xls', 'html'))) {
            //first item
            if ($key === $this->getFirstKeyFromArray($columns)) {
                $this->data .= '<tr>';
            }

            $this->data .= sprintf('<td>%s</td>', $column);
            //last item
            if ($key === $this->getLastKeyFromArray($columns)) {
                $this->data .= '</tr>';
            }
        } elseif ('json' === $this->format) {
            $this->data[0] = array_values($columns);
        }

        return $this;
    }

    /**
     * @param array $columns
     *
     * @return $this
     * @throws \RuntimeException
     */
    public function setColumns(Array $columns)
    {

        if (empty($this->format)) {
            throw new \RuntimeException(sprintf('First use setOptions!'));
        }

        foreach ($columns as $key => $column) {
            $this->setColumn($column, $key, $columns);
        }

        return $this;
    }


    /**
     * @return string
     */
    private function prepareCSV()
    {
        return implode("\n", $this->data);
    }

    /**
     * @return string|Response
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
                $response->headers->set('Content-Type', 'application/vnd.ms-excel');
                $response->setContent($this->data);
                break;
            case 'html':
                $response->headers->set('Content-Type', 'text/html');
                $response->setContent($this->data);
                break;
            case 'xml':
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
