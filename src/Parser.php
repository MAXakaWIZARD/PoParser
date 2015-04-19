<?php

namespace PoParser;

class Parser
{
    /**
     * @var array
     */
    protected $headers = array();

    /**
     * @var Entry[]
     */
    protected $entries = array();

    /**
     * @var array
     */
    protected $entriesAsArrays = array();

    /**
     * @var string
     */
    protected $state;

    /**
     * @var array
     */
    protected $rawEntries;

    /**
     * @var array
     */
    protected $currentEntry;

    /**
     * @var boolean
     */
    protected $justNewEntry;

    /**
     * @return Entry[]
     */
    public function getEntries()
    {
        return $this->entries;
    }

    /**
     * @return array
     */
    public function getEntriesAsArrays()
    {
        return $this->entriesAsArrays;
    }

    /**
     * Reads and parses strings in a .po file.
     *
     *  return An array of entries located in the file:
     *  Format: array(
     *      'msgid'     => <string> ID of the message.
     *      'msgctxt'   => <string> Message context.
     *      'msgstr'    => <string> Message translation.
     *      'tcomment'  => <string> Comment from translator.
     *      'ccomment'  => <string> Extracted comments from code.
     *      'reference' => <string> Location of string in code.
     *      'obsolete'  => <bool> Is the message obsolete?
     *      'fuzzy'     => <bool> Is the message "fuzzy"?
     *      'flags'     => <string> Flags of the entry. Internal usage.
     *  )
     *
     *   #~ (old entry)
     *   # @ default
     *   #, fuzzy
     *   #~ msgid "Editar datos"
     *   #~ msgstr "editar dades"
     *
     * @param string $filePath
     * @throws \Exception
     * @return array|bool
     */
    public function read($filePath)
    {
        $this->rawEntries = array();
        $this->currentEntry = $this->createNewEntryAsArray();
        $this->state = null;
        $this->justNewEntry = false;

        $handle = $this->openFileForRead($filePath);
        while (!feof($handle)) {
            $line = trim(fgets($handle));
            $this->processLine($line);
        }
        fclose($handle);

        $this->addFinalEntry();
        $this->prepareResults();

        return $this->entriesAsArrays;
    }

    /**
     * @param string $line
     *
     * @throws \Exception
     */
    protected function processLine($line)
    {
        if ($line === '') {
            if ($this->justNewEntry) {
                // Two consecutive blank lines
                return;
            }

            // A new entry is found
            $this->rawEntries[] = $this->currentEntry;
            $this->currentEntry = $this->createNewEntryAsArray();
            $this->state = null;
            $this->justNewEntry = true;
            return;
        }

        $this->justNewEntry = false;

        $split = preg_split('/\s/ ', $line, 2);
        $key = $split[0];
        $data = isset($split[1]) ? $split[1] : null;

        switch ($key) {
            case '#,':
                //flag
                $this->currentEntry['flags'] = preg_split('/,\s*/', $data);
                $this->currentEntry['fuzzy'] = in_array('fuzzy', $this->currentEntry['flags'], true);
                break;
            case '#':
                $this->currentEntry['tcomment'] = $data;
                break;
            case '#.':
                $this->currentEntry['ccomment'] = $data;
                break;
            case '#:':
                $this->currentEntry['reference'][] = addslashes($data);
                break;
            case '#|':
                //msgid previous-untranslated-string
                // start a new entry
                break;
            case '#@':
                // ignore #@ default
                $this->currentEntry['@'] = $data;
                break;
            case '#~':
                $this->processObsoleteEntry($data);
                break;
            case 'msgctxt':
            case 'msgid':
            case 'msgid_plural':
                $this->state = $key;
                $this->currentEntry[$this->state] = $data;
                break;
            case 'msgstr':
                $this->state = 'msgstr';
                $this->currentEntry[$this->state][] = $data;
                break;
            default:
                if (strpos($key, 'msgstr[') !== false) {
                    // translated plurals
                    $this->state = 'msgstr';
                    $this->currentEntry[$this->state][] = $data;
                } else {
                    $this->processContinuedLineInSameState($line);
                }
                break;
        }
    }

    /**
     * @param string $line
     *
     * @throws \Exception
     */
    protected function processContinuedLineInSameState($line)
    {
        switch ($this->state) {
            case 'msgctxt':
            case 'msgid':
            case 'msgid_plural':
                if (is_string($this->currentEntry[$this->state])) {
                    // Convert it to array
                    $this->currentEntry[$this->state] = array($this->currentEntry[$this->state]);
                }
                $this->currentEntry[$this->state][] = $line;
                break;
            case 'msgstr':
                $this->currentEntry['msgstr'][] = trim($line, '"');
                break;
            default:
                throw new \Exception('Parse error!');
        }
    }

    /**
     * @param $data
     */
    protected function processObsoleteEntry($data)
    {
        $this->currentEntry['obsolete'] = true;

        $tmpParts = explode(' ', $data);
        $tmpKey = $tmpParts[0];
        $str = implode(' ', array_slice($tmpParts, 1));

        switch ($tmpKey) {
            case 'msgid':
                $this->currentEntry['msgid'] = trim($str, '"');
                break;
            case 'msgstr':
                $this->currentEntry['msgstr'][] = trim($str, '"');
                break;
            default:
                break;
        }
    }

    /**
     *
     */
    protected function addFinalEntry()
    {
        if ($this->state == 'msgstr' || $this->currentEntry['obsolete']) {
            $this->rawEntries[] = $this->currentEntry;
        }
    }

    /**
     * Cleanup data, merge multiline entries, reindex hash for ksort
     *
     * @return bool
     */
    protected function prepareResults()
    {
        $this->entriesAsArrays = array();
        $this->entries = array();
        $this->headers = array();

        $counter = 0;
        foreach ($this->rawEntries as $entry) {
            foreach ($entry as &$field) {
                $field = $this->clean($field);
            }

            $id = is_array($entry['msgid']) ? implode('', $entry['msgid']) : $entry['msgid'];

            if ($counter === 0 && $id === '') {
                //header entry
                $entry['header'] = true;
                $this->setHeaders($this->parseHeaders($entry));
            }

            $this->entriesAsArrays[$id] = $entry;
            $this->entries[$id] = new Entry($entry);

            $counter++;
        }

        return true;
    }

    /**
     * @return array
     */
    protected function createNewEntryAsArray()
    {
        return array(
            'msgctxt' => '',
            'obsolete' => false,
            'fuzzy' => false,
            'flags' => array(),
            'ccomment' => '',
            'tcomment' => '',
        );
    }

    /**
     * @param string $filePath
     *
     * @throws \Exception
     * @return resource
     */
    protected function openFileForRead($filePath)
    {
        if (empty($filePath)) {
            throw new \Exception('Input file not defined.');
        } elseif (!file_exists($filePath)) {
            throw new \Exception("File does not exist: {$filePath}");
        }

        $handle = @fopen($filePath, 'r');
        if (false === $handle) {
            throw new \Exception("Unable to open file for reading: {$filePath}");
        }

        return $handle;
    }

    /**
     * @param array $entry
     *
     * @return array
     */
    protected function parseHeaders(array $entry)
    {
        $headers = array();

        if (!is_array($entry['msgstr'])) {
            return $headers;
        }

        foreach ($entry['msgstr'] as $headerRaw) {
            $parts = explode(':', $headerRaw);
            if (count($parts) < 2) {
                continue;
            }

            $parts[1] = ltrim($parts[1]);
            $values = array_slice($parts, 1);
            $headerValue = rtrim(implode(':', $values));

            $headers[$parts[0]] = $headerValue;
        }

        return $headers;
    }

    /**
     * set all entries at once
     *
     * @param array $entries
     */
    public function setEntries(array $entries)
    {
        $this->entriesAsArrays = $entries;
    }

    /**
     * Allows modification a msgid.
     * By default disabled fuzzy flag if defined.
     *
     * @param $original
     * @param $translation
     */
    public function updateEntry($original, $translation)
    {
        $this->entriesAsArrays[$original]['fuzzy'] = false;
        $this->entriesAsArrays[$original]['msgstr'] = array($translation);

        $flags = $this->entriesAsArrays[$original]['flags'];
        unset($flags[array_search('fuzzy', $flags, true)]);
        $this->entriesAsArrays[$original]['flags'] = $flags;
    }


    /**
     * Write entries into the po file.
     *
     * @param string $filePath
     * @throws \Exception
     */
    public function write($filePath)
    {
        $writer = new Writer;
        $writer->write($filePath, $this->entriesAsArrays);
    }

    /**
     *
     */
    public function clearFuzzy()
    {
        foreach ($this->entriesAsArrays as &$entry) {
            if ($entry['fuzzy'] === true) {
                $flags = $entry['flags'];
                $entry['flags'] = str_replace('fuzzy', '', $flags);
                $entry['fuzzy'] = false;
                $entry['msgstr'] = array('');
            }
        }
    }

    /**
     * @param $value
     *
     * @return array|string
     */
    public function clean($value)
    {
        if ($value === true || $value === false) {
            return $value;
        } elseif (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = $this->clean($v);
            }
        } else {
            // Remove " from start and end
            if ($value == '') {
                return '';
            }

            if ($value[0] == '"') {
                $value = substr($value, 1, -1);
            }

            $value = stripcslashes($value);
        }

        return $value;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @param array $headers
     */
    public function setHeaders($headers)
    {
        $this->headers = $headers;
    }
}
