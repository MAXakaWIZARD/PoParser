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
    public function tearDown()
    {
        $writePath = TEST_DATA_PATH . '/non_readable.po';
        if (file_exists($writePath)) {
            @chmod($writePath, 0664);
            @unlink($writePath);
        }
    }

    /**
     *
     */
    public function testInvalidFile()
    {
        $this->setExpectedException('\Exception', 'Input file not defined.');
        $this->parser->read('');
    }

    /**
     *
     */
    public function testNonExistentFile()
    {
        $path = '/path/to/unknown/file.po';
        $this->setExpectedException('\Exception', 'File does not exist: ' . $path);
        $this->parser->read($path);
    }

    /**
     *
     */
    public function testNonReadableFile()
    {
        $path = TEST_DATA_PATH . '/non_readable.po';
        file_put_contents($path, '');
        $this->assertFileExists($path);
        chmod($path, 0000);

        $this->setExpectedException('\Exception', 'Unable to open file for reading: ' . $path);
        $this->parser->read($path);
    }

        /**
     *
     */
    public function testGeneral()
    {
        $this->parser->read(TEST_DATA_PATH . '/general.po');

        $entries = $this->parser->getEntries();
        $entriesAsArrays = $this->parser->getEntriesAsArrays();

        $correctData = $this->getGeneralCorrectData();
        $correctDataCount = count($correctData);

        $this->assertEquals(
            $correctDataCount,
            count($entries),
            "There should be {$correctDataCount} entries objects"
        );
        $this->assertEquals(
            count($correctData),
            count($entriesAsArrays),
            "There should be {$correctDataCount} entries arrays"
        );

        $idx = 0;
        foreach ($entries as $entry) {
            $item = $correctData[$idx];
            $this->assertEquals($item['msgid'], $entry->getMsgId());
            $this->assertEquals($item['fuzzy'], $entry->isFuzzy(), $entry->getMsgId() . ' should be fuzzy');
            $this->assertEquals($item['obsolete'], $entry->isObsolete(), $entry->getMsgId() . ' should be obsolete');

            foreach ($item['flags'] as $flag) {
                $this->assertTrue($entry->hasFlag($flag), $entry->getMsgId() . ' should have flag: ' . $flag);
            }
            $this->assertSame($item['flags'], $entry->getFlags());

            if (isset($item['context']) && $item['context'] !== '') {
                $this->assertEquals(
                    $item['context'],
                    $entry->getContext(),
                    $entry->getMsgId() . ' should have context: ' . $item['context']
                );
            }

            if (isset($item['comment']) && $item['comment'] !== '') {
                $this->assertEquals(
                    $item['comment'],
                    $entry->getExtractedComment(),
                    $entry->getMsgId() . ' should have comment: ' . $item['comment']
                );
            }

            if (isset($item['tcomment']) && $item['tcomment'] !== '') {
                $this->assertEquals(
                    $item['tcomment'],
                    $entry->getTranslatorComment(),
                    $entry->getMsgId() . ' should have translator comment: ' . $item['tcomment']
                );
            }

            if (isset($item['plural'])) {
                $this->assertEquals(
                    $item['plural'],
                    $entry->isPlural(),
                    $entry->getMsgId() . ' should be plural: ' . $item['plural']
                );

                if ($item['plural']) {
                    $this->assertEquals(
                        $item['msgid_plural'],
                        $entry->getMsgIdPlural(),
                        $entry->getMsgId() . ' should have msgid_plural: ' . $item['msgid_plural']
                    );
                    $this->assertSame($item['msgstr'], $entry->getTranslations());
                }
            }

            if (!isset($item['plural']) || $item['plural'] === false) {
                $this->assertEquals($item['msgstr'], $entry->getTranslation(0));
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
                'plural' => false,
                'flags' => array()
            ),
            array(
                'msgid' => 'cat',
                'msgstr' => 'gato',
                'fuzzy' => false,
                'obsolete' => false,
                'plural' => false,
                'flags' => array(),
                'comment' => 'some comment',
                'tcomment' => 'translator\'s comment'
            ),
            array(
                'context' => 'some context',
                'msgid' => '%s, the dog',
                'msgstr' => '%s, perro',
                'fuzzy' => false,
                'obsolete' => false,
                'plural' => false,
                'flags' => array('php-format', 'another-flag')
            ),
            array(
                'msgid' => 'racoon',
                'msgstr' => 'mapache',
                'fuzzy' => true,
                'obsolete' => false,
                'plural' => false,
                'flags' => array('fuzzy')
            ),
            array(
                'msgid' => 'country',
                'msgid_plural' => 'countries',
                'msgstr' => array('país', 'países'),
                'fuzzy' => false,
                'obsolete' => false,
                'plural' => true,
                'flags' => array()
            ),
            array(
                'msgid' => 'very-very long string',
                'msgid_plural' => 'very-very long plural string',
                'msgstr' => array('very long translation', 'very long translation2'),
                'fuzzy' => false,
                'obsolete' => false,
                'plural' => true,
                'flags' => array()
            ),
            array(
                'msgid' => 'hare',
                'msgstr' => 'liebre',
                'fuzzy' => false,
                'obsolete' => true,
                'plural' => false,
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

    /**
     *
     */
    public function testClearFuzzy()
    {
        $this->parser->read(TEST_DATA_PATH . '/general.po');

        $entries = $this->parser->getEntriesAsArrays();
        $fuzzyCount = 0;
        foreach ($entries as $entry) {
            if ($entry['fuzzy']) {
                $fuzzyCount++;
            }
        }
        $this->assertTrue($fuzzyCount > 0, 'There should be at least one fuzzy entry');

        $this->parser->clearFuzzy();

        $entries = $this->parser->getEntriesAsArrays();
        $fuzzyCount = 0;
        foreach ($entries as $entry) {
            if ($entry['fuzzy']) {
                $fuzzyCount++;
            }
        }
        $this->assertTrue($fuzzyCount === 0, 'There should not be any fuzzy entry');
    }

    /**
     *
     */
    public function testSetEntries()
    {
        $this->parser->read(TEST_DATA_PATH . '/general.po');

        $entries = $this->parser->getEntriesAsArrays();
        $entries['cat']['fuzzy'] = true;

        $this->parser->setEntries($entries);
        $this->assertSame($entries, $this->parser->getEntriesAsArrays());
    }

    /**
     *
     */
    public function testUpdateEntry()
    {
        $this->parser->read(TEST_DATA_PATH . '/general.po');

        $entries = $this->parser->getEntriesAsArrays();
        $this->assertEquals('mapache', $entries['racoon']['msgstr'][0]);
        $this->assertTrue($entries['racoon']['fuzzy']);

        $this->parser->updateEntry('racoon', 'mapache2');
        $entries = $this->parser->getEntriesAsArrays();
        $this->assertEquals('mapache2', $entries['racoon']['msgstr'][0]);
        $this->assertFalse($entries['racoon']['fuzzy']);
    }
}
