<?php
namespace PoParser\Tests;

use PoParser\Parser;

/**
 *
 */
class PoParserTest extends \PHPUnit_Framework_TestCase
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
    public function tearDown()
    {

    }

    /**
     *
     */
    public function testGeneral()
    {
        $this->parser->read(TEST_DATA_PATH . '/general.po');

        $entries = $this->parser->getEntries();

        $correctData = array(
            array('', ''),
            array('cat', 'gato'),
            array('dog', 'perro')
        );

        $idx = 0;
        foreach ($entries as $entry) {
            $this->assertEquals($correctData[$idx][0], $entry->getMsgId());
            $this->assertEquals($correctData[$idx][1], $entry->getTranslation(0));
            $idx++;
        }
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

    /**
     *
     */
    public function testWrite()
    {
        $sourcePath = TEST_DATA_PATH . '/general.po';
        $this->parser->read($sourcePath);

        $writePath = TEST_DATA_PATH . '/general1.po';
        $this->parser->write($writePath);

        $this->assertFileExists($writePath);
        $this->assertFileEquals($sourcePath, $writePath);

        unlink($writePath);
    }
}
