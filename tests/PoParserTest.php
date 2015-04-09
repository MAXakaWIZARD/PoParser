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
        $this->parser->read(BASE_PATH . '/tests/data/general.po');

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
}
