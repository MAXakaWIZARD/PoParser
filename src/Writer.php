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

        // fwrite( $handle, "\xEF\xBB\xBF" );	//UTF-8 BOM header

        $entriesCount = count($entries);
        $counter = 0;
        foreach ($entries as $entry) {
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
    protected function getEntryWriteStr($entry)
    {
        $result = '';

        if (isset($entry['tcomment'])) {
            $result .= "# " . $entry['tcomment'] . "\n";
        }

        if (isset($entry['ccomment'])) {
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

        if (isset($entry['msgctxt'])) {
            $result .= 'msgctxt ' . $this->cleanExport($entry['msgctxt']) . "\n";
        }

        $result .= $this->getMsgIdStr($entry);
        $result .= $this->getMsgIdPluralStr($entry);
        $result .= $this->getMsgStrStr($entry);

        return $result;
    }

    /**
     * @param $entry
     *
     * @return string
     */
    protected function getMsgIdStr($entry)
    {
        $result = '';

        if (!isset($entry['msgid'])) {
            return $result;
        }

        if (is_array($entry['msgid'])) {
            $entry['msgid'] = implode('', $entry['msgid']);
        }

        // Special clean for msgid
        $msgid = explode("\n", $entry['msgid']);

        if ($entry['obsolete']) {
            $result .= "#~ ";
        }

        $result .= 'msgid ';
        foreach ($msgid as $i => $id) {
            $result .= $this->cleanExport($id) . "\n";
        }

        return $result;
    }

    /**
     * @param $entry
     *
     * @return string
     */
    protected function getMsgIdPluralStr($entry)
    {
        $result = '';

        if (isset($entry['msgid_plural'])) {
            if (is_array($entry['msgid_plural'])) {
                $entry['msgid_plural'] = implode('', $entry['msgid_plural']);
            }
            $result .= 'msgid_plural ' . $this->cleanExport($entry['msgid_plural']) . "\n";
        }

        return $result;
    }

    /**
     * @param $entry
     *
     * @return string
     */
    protected function getMsgStrStr($entry)
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
