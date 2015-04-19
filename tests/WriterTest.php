<?php
namespace PoParser\Tests;

use PoParser\Parser;

/**
 *
 */
class WriterTest extends \PHPUnit_Framework_TestCase
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
        $writePath = TEST_DATA_PATH . '/general1.po';
        if (file_exists($writePath)) {
            @chmod($writePath, 0664);
            @unlink($writePath);
        }
    }

    /**
     *
     */
    public function testGeneral()
    {
        $sourcePath = TEST_DATA_PATH . '/general.po';
        $this->parser->read($sourcePath);

        $writePath = TEST_DATA_PATH . '/general1.po';
        $this->parser->write($writePath);

        $this->assertFileExists($writePath);
        $this->assertFileEquals($sourcePath, $writePath);
    }

    /**
     *
     */
    public function testInvalidPath()
    {
        $sourcePath = TEST_DATA_PATH . '/general.po';
        $this->parser->read($sourcePath);

        $this->setExpectedException('\Exception', 'Output file not defined.');
        $this->parser->write('');
    }

    /**
     *
     */
    public function testNonWritable()
    {
        $sourcePath = TEST_DATA_PATH . '/general.po';
        $this->parser->read($sourcePath);

        $writePath = TEST_DATA_PATH . '/general1.po';
        file_put_contents($writePath, '');
        $this->assertFileExists($writePath);
        chmod($writePath, 0000);
        $this->setExpectedException('\Exception', 'Unable to open file for writing: ' . $writePath);
        $this->parser->write($writePath);
    }
}
