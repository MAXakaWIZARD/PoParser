<?php
namespace PoParser\Tests;

use PoParser\Parser;

/**
 *
 */
class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     *
     */
    public function setUp()
    {
        $this->parser = new Parser();
    }

    /**
     *
     */
    public function testGeneral()
    {
        $this->parser->read(TEST_DATA_PATH . '/general.po');

        $entries = $this->parser->getEntries();
        //var_dump($this->parser->getEntriesAsArrays());
        //exit;

        $correctData = $this->getGeneralCorrectData();

        $idx = 0;
        foreach ($entries as $entry) {
            $item = $correctData[$idx];
            $this->assertEquals($item['msgid'], $entry->getMsgId());
            $this->assertEquals($item['msgstr'], $entry->getTranslation(0));
            $this->assertEquals($item['fuzzy'], $entry->isFuzzy(), $entry->getMsgId() . ' should be fuzzy');
            $this->assertEquals($item['obsolete'], $entry->isObsolete(), $entry->getMsgId() . ' should be obsolete');

            foreach ($item['flags'] as $flag) {
                $this->assertTrue($entry->hasFlag($flag), $entry->getMsgId() . ' should have flag: ' . $flag);
            }

            $idx++;
        }
    }

    /**
     * @return array
     */
    protected function getGeneralCorrectData()
    {
        return array(
            array(
                'msgid' => '',
                'msgstr' => '',
                'fuzzy' => false,
                'obsolete' => false,
                'flags' => array()
            ),
            array(
                'msgid' => 'cat',
                'msgstr' => 'gato',
                'fuzzy' => false,
                'obsolete' => false,
                'flags' => array()
            ),
            array(
                'msgid' => 'dog',
                'msgstr' => 'perro',
                'fuzzy' => false,
                'obsolete' => false,
                'flags' => array('php-format', 'another-flag')
            ),
            array(
                'msgid' => 'racoon',
                'msgstr' => 'mapache',
                'fuzzy' => true,
                'obsolete' => false,
                'flags' => array('fuzzy')
            ),
            array(
                'msgid' => 'hare',
                'msgstr' => 'liebre',
                'fuzzy' => false,
                'obsolete' => true,
                'flags' => array()
            ),
        );
    }

    /**
     *
     */
    public function testHeaders()
    {
        $this->parser->read(TEST_DATA_PATH . '/general.po');

        $correctData = array(
            'Project-Id-Version' => 'test',
            'Report-Msgid-Bugs-To' => '',
            'POT-Creation-Date' => '2014-12-11 15:31+0200',
            'PO-Revision-Date' => '2014-12-11 15:31+0200',
            'Last-Translator' => 'maxakawizard <mail@server.com>',
            'Language-Team' => '',
            'Language' => 'es_ES',
            'MIME-Version' => '1.0',
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Transfer-Encoding' => '8bit',
            'X-Poedit-KeywordsList' => '_;gettext;gettext_noop',
            'X-Poedit-Basepath' => '.',
            'X-Poedit-SourceCharset' => 'UTF-8',
            'Plural-Forms' => 'nplurals=2; plural=n != 1;',
            'X-Generator' => 'Poedit 1.7.1',
            'X-Poedit-SearchPath-0' => '/path/to/project'
        );

        $headers = $this->parser->getHeaders();

        $this->assertEquals(count($correctData), count($headers));

        foreach ($headers as $headerName => $headerValue) {
            $this->assertEquals($correctData[$headerName], $headerValue);
        }

        $entries = $this->parser->getEntries();
        $idx = 0;
        foreach ($entries as $entry) {
            $this->assertEquals($idx === 0, $entry->isHeader());
            $idx++;
        }
    }
}
