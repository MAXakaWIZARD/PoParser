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
     * File handle
     * @var null
     */
    protected $handle = null;

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
     * @param $filePath
     *
     * @return array|bool
     * @throws \Exception
     */
    public function read($filePath)
    {
        $this->handle = $this->openFileForRead($filePath);

        $rawEntries = array();
        $entry = array();
        $state = null;
        $justNewEntry = false;

        while (!feof($this->handle)) {
            $line = trim(fgets($this->handle));

            if ($line === '') {
                if ($justNewEntry) {
                    // Two consecutive blank lines
                    continue;
                }

                // A new entry is found!
                $rawEntries[] = $entry;
                $entry = array();
                $state = null;
                $justNewEntry = true;
                continue;
            }

            $justNewEntry = false;

            $split = preg_split('/\s/ ', $line, 2);
            $key = $split[0];
            $data = isset($split[1]) ? $split[1] : null;

            switch ($key) {
                case '#,':
                    //flag
                    $entry['fuzzy'] = in_array('fuzzy', preg_split('/,\s*/', $data));
                    $entry['flags'] = $data;
                    break;
                case '#':
                    //translation-comments
                    $entry['tcomment'] = $data;
                    break;
                case '#.':
                    //extracted-comments
                    $entry['ccomment'] = $data;
                    break;
                case '#:':
                    //reference
                    $entry['reference'][] = addslashes($data);
                    break;
                case '#|':
                    //msgid previous-untranslated-string
                    // start a new entry
                    break;
                case '#@':
                    // ignore #@ default
                    $entry['@'] = $data;
                    break;
                // old entry
                case '#~':
                    $tmpParts = explode(' ', $data);
                    $tmpKey = $tmpParts[0];
                    $str = implode(' ', array_slice($tmpParts, 1));
                    $entry['obsolete'] = true;
                    switch ($tmpKey) {
                        case 'msgid':
                            $entry['msgid'] = trim($str, '"');
                            break;
                        case 'msgstr':
                            $entry['msgstr'][] = trim($str, '"');
                            break;
                        default:
                            break;
                    }

                    continue;
                    break;
                case 'msgctxt':
                    // context
                case 'msgid':
                    // untranslated-string
                case 'msgid_plural':
                    // untranslated-string-plural
                    $state = $key;
                    $entry[$state] = $data;
                    break;
                case 'msgstr':
                    // translated-string
                    $state = 'msgstr';
                    $entry[$state][] = $data;
                    break;
                default:
                    if (strpos($key, 'msgstr[') !== false) {
                        // translated-string-case-n
                        $state = 'msgstr';
                        $entry[$state][] = $data;
                    } else {
                        // continued lines
                        switch ($state) {
                            case 'msgctxt':
                            case 'msgid':
                            case 'msgid_plural':
                                if (is_string($entry[$state])) {
                                    // Convert it to array
                                    $entry[$state] = array($entry[$state]);
                                }
                                $entry[$state][] = $line;
                                break;
                            case 'msgstr':
                                $entry['msgstr'][] = trim($line, '"');
                                break;
                            default:
                                throw new \Exception('Parse error!');
                                break;
                        }
                    }
                    break;
            }
        }
        fclose($this->handle);

        // add final entry
        if ($state == 'msgstr') {
            $rawEntries[] = $entry;
        }

        $this->prepareResults($rawEntries);

        return $this->entriesAsArrays;
    }

    /**
     * Cleanup data, merge multiline entries, reindex hash for ksort
     *
     * @param array $data
     *
     * @return bool
     */
    protected function prepareResults(array $data)
    {
        $this->entriesAsArrays = array();
        $this->entries = array();
        $this->headers = array();

        $counter = 0;
        foreach ($data as $entry) {
            foreach ($entry as &$v) {
                $v = $this->clean($v);
                if ($v === false) {
                    // parse error
                    return false;
                }
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
     * @param $filePath
     *
     * @return resource
     * @throws \Exception
     */
    protected function openFileForRead($filePath)
    {
        if (empty($filePath)) {
            throw new \Exception('Input file not defined.');
        } elseif (!file_exists($filePath) || !is_readable($filePath)) {
            throw new \Exception("File does not exist or is not readable: {$filePath}");
        }

        $handle = fopen($filePath, 'r');
        if (false === $handle) {
            throw new \Exception("Unable to open file for reading: {$filePath}");
        }

        return $handle;
    }

    /**
     * @param $filePath
     *
     * @return resource
     * @throws \Exception
     */
    protected function openFileForWrite($filePath)
    {
        if (empty($filePath)) {
            throw new \Exception('Output file not defined.');
        }

        $handle = fopen($filePath, 'wb');
        if (false === $handle) {
            throw new \Exception("Unable to open file for writing: {$filePath}");
        }

        return $handle;
    }

    /**
     * @param $entry
     *
     * @return array
     */
    protected function parseHeaders($entry)
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
     * @param $entries
     */
    public function setEntries($entries)
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

        if (isset($this->entriesAsArrays[$original]['flags'])) {
            $flags = $this->entriesAsArrays[$original]['flags'];
            $this->entriesAsArrays[$original]['flags'] = str_replace('fuzzy', '', $flags);
        }
    }


    /**
     * Write entries into the po file.
     *
     * @param $filePath
     * @throws \Exception
     */
    public function write($filePath)
    {
        $handle = $this->openFileForWrite($filePath);

        // fwrite( $handle, "\xEF\xBB\xBF" );	//UTF-8 BOM header

        $entriesCount = count($this->entriesAsArrays);
        $counter = 0;
        foreach ($this->entriesAsArrays as $entry) {
            $counter++;

            if ($counter > 1) {
                fwrite($handle, "\n");
            }

            $entryStr = $this->getEntryWriteStr($entry);
            if ($counter == $entriesCount) {
                $entryStr = rtrim($entryStr);
            }

            fwrite($handle, $entryStr);
        }

        fclose($handle);
    }

    /**
     * @param $entry
     *
     * @return string
     */
    protected function getEntryWriteStr($entry)
    {
        $entryStr = '';

        $isObsolete = isset($entry['obsolete']) && $entry['obsolete'];
        $isPlural = isset($entry['msgid_plural']);

        if (isset($entry['tcomment'])) {
            $entryStr .= "# " . $entry['tcomment'] . "\n";
        }

        if (isset($entry['ccomment'])) {
            $entryStr .= '#. ' . $entry['ccomment'] . "\n";
        }

        if (isset($entry['reference'])) {
            foreach ($entry['reference'] as $ref) {
                $entryStr .= '#: ' . $ref . "\n";
            }
        }

        if (isset($entry['flags']) && !empty($entry['flags'])) {
            $entryStr .= "#, " . $entry['flags'] . "\n";
        }

        if (isset($entry['@'])) {
            $entryStr .= "#@ " . $entry['@'] . "\n";
        }

        if (isset($entry['msgctxt'])) {
            $entryStr .= 'msgctxt ' . $entry['msgctxt'] . "\n";
        }

        if ($isObsolete) {
            $entryStr .= "#~ ";
        }

        if (isset($entry['msgid'])) {
            if (is_array($entry['msgid'])) {
                $entry['msgid'] = implode('', $entry['msgid']);
            }

            // Special clean for msgid
            $msgid = explode("\n", $entry['msgid']);

            $entryStr .= 'msgid ';
            foreach ($msgid as $i => $id) {
                $entryStr .= $this->cleanExport($id) . "\n";
            }
        }

        if (isset($entry['msgid_plural'])) {
            if (is_array($entry['msgid_plural'])) {
                $entry['msgid_plural'] = implode('', $entry['msgid_plural']);
            }
            $entryStr .= 'msgid_plural ' . $this->cleanExport($entry['msgid_plural']) . "\n";
        }

        if (!isset($entry['msgstr'])) {
            return $entryStr;
        }

        foreach ($entry['msgstr'] as $i => $t) {
            if ($isPlural) {
                if ($isObsolete) {
                    $entryStr .= "#~ ";
                }
                $entryStr .= "msgstr[$i] " . $this->cleanExport($t) . "\n";
            } else {
                if ($i == 0) {
                    if ($isObsolete) {
                        $entryStr .= "#~ ";
                    }
                    $entryStr .= 'msgstr ' . $this->cleanExport($t) . "\n";
                } else {
                    $entryStr .= $this->cleanExport($t) . "\n";
                }
            }
        }

        return $entryStr;
    }

    /**
     *
     */
    public function clearFuzzy()
    {
        foreach ($this->entriesAsArrays as &$str) {
            if (isset($str['fuzzy']) && $str['fuzzy'] === true) {
                $flags = $str['flags'];
                $str['flags'] = str_replace('fuzzy', '', $flags);
                $str['fuzzy'] = false;
                $str['msgstr'] = array('');
            }
        }
    }

    /**
     * @param $string
     *
     * @return mixed
     */
    protected function cleanExport($string)
    {
        $quote = '"';
        $slash = '\\';
        $newline = "\n";

        $replaces = array(
            "$slash" => "$slash$slash",
            "$quote" => "$slash$quote",
            "\t"     => '\t',
        );

        $string = str_replace(array_keys($replaces), array_values($replaces), $string);

        $po = $quote . implode("${slash}n$quote$newline$quote", explode($newline, $string)) . $quote;

        // remove empty strings
        return str_replace("$newline$quote$quote", '', $po);
    }

    /**
     * @param $x
     *
     * @return array|string
     */
    public function clean($x)
    {
        if (is_array($x)) {
            foreach ($x as $k => $v) {
                $x[$k] = $this->clean($v);
            }
        } else {
            // Remove " from start and end
            if ($x == '') {
                return '';
            }

            if ($x[0] == '"') {
                $x = substr($x, 1, -1);
            }

            $x = stripcslashes($x);
        }

        return $x;
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
