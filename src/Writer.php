<?php

namespace PoParser;

class Writer
{
    /**
     * @param $filePath
     * @param $entries
     *
     * @throws \Exception
     */
    public function write($filePath, $entries)
    {
        $handle = $this->openFileForWrite($filePath);

        $entriesCount = count($entries);
        $counter = 0;
        foreach ($entries as $entry) {
            $counter++;

            if ($counter > 1) {
                fwrite($handle, "\n");
            }

            $entryStr = $this->getEntryStr($entry);
            if ($counter == $entriesCount) {
                $entryStr = rtrim($entryStr);
            }

            fwrite($handle, $entryStr);
        }

        fclose($handle);
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

        $handle = @fopen($filePath, 'wb');
        if (false === $handle) {
            throw new \Exception("Unable to open file for writing: {$filePath}");
        }

        return $handle;
    }

    /**
     * @param $entry
     *
     * @return string
     */
    protected function getEntryStr($entry)
    {
        $result = '';

        if (isset($entry['tcomment']) && $entry['tcomment'] !== '') {
            $result .= "# " . $entry['tcomment'] . "\n";
        }

        if (isset($entry['ccomment']) && $entry['ccomment'] !== '') {
            $result .= '#. ' . $entry['ccomment'] . "\n";
        }

        if (isset($entry['reference']) && is_array($entry['reference'])) {
            foreach ($entry['reference'] as $ref) {
                $result .= '#: ' . $ref . "\n";
            }
        }

        if (isset($entry['flags']) && count($entry['flags']) > 0) {
            $result .= "#, " . implode(', ', $entry['flags']) . "\n";
        }

        if (isset($entry['@'])) {
            $result .= "#@ " . $entry['@'] . "\n";
        }

        if (isset($entry['msgctxt']) && $entry['msgctxt'] !== '') {
            $result .= 'msgctxt ' . $this->cleanExport($entry['msgctxt']) . "\n";
        }

        $result .= $this->getMsgId($entry);
        $result .= $this->getMsgIdPlural($entry);
        $result .= $this->getMsgStr($entry);

        return $result;
    }

    /**
     * @param $entry
     *
     * @return string
     */
    protected function getMsgId($entry)
    {
        $result = '';

        if (!isset($entry['msgid'])) {
            return $result;
        }

        if ($entry['obsolete']) {
            $result .= "#~ ";
        }

        $result .= 'msgid ';
        if (is_array($entry['msgid'])) {
            foreach ($entry['msgid'] as $id) {
                $result .= $this->cleanExport($id) . "\n";
            }
        } else {
            $result .= $this->cleanExport($entry['msgid']) . "\n";
        }

        return $result;
    }

    /**
     * @param $entry
     *
     * @return string
     */
    protected function getMsgIdPlural($entry)
    {
        $result = '';

        if (!isset($entry['msgid_plural'])) {
            return $result;
        }

        $result .= 'msgid_plural ';
        if (is_array($entry['msgid_plural'])) {
            foreach ($entry['msgid_plural'] as $id) {
                $result .= $this->cleanExport($id) . "\n";
            }
        } else {
            $result .= $this->cleanExport($entry['msgid_plural']) . "\n";
        }

        return $result;
    }

    /**
     * @param $entry
     *
     * @return string
     */
    protected function getMsgStr($entry)
    {
        $result = '';

        if (!isset($entry['msgstr'])) {
            return $result;
        }

        $isPlural = isset($entry['msgid_plural']);

        foreach ($entry['msgstr'] as $i => $t) {
            if ($isPlural) {
                if ($entry['obsolete']) {
                    $result .= "#~ ";
                }
                $result .= "msgstr[$i] " . $this->cleanExport($t) . "\n";
            } else {
                if ($i == 0) {
                    if ($entry['obsolete']) {
                        $result .= "#~ ";
                    }
                    $result .= 'msgstr ' . $this->cleanExport($t) . "\n";
                } else {
                    $result .= $this->cleanExport($t) . "\n";
                }
            }
        }

        return $result;
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
}
