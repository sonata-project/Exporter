<?php

namespace Exporter\Test\Source;

use Exporter\Writer\CsvWriter;

class CsvWriterTest extends \PHPUnit_Framework_TestCase
{
    protected $filename;

    public function setUp()
    {
        $this->filename = 'foobar.csv';

        if (is_file($this->filename)) {
            unlink($this->filename);
        }
    }

    public function testEnclosureFormating()
    {

        $writer = new CsvWriter($this->filename, ',', '"');
        $writer->open();

        $writer->write(array(' john , ""2"', 'doe', '1'));

        $writer->close();

        $expected = '" john , """"2""",doe,1';

        $this->assertEquals($expected, trim(file_get_contents($this->filename)));
    }

    public function testEnclosureFormatingWithExcel()
    {
        $writer = new CsvWriter($this->filename, ',', '"', "");
        $writer->open();

        $writer->write(array('john , ""2"', 'doe ', '1'));

        $writer->close();

        $expected = '"john , """"2""","doe ",1';

        $this->assertEquals($expected, trim(file_get_contents($this->filename)));
    }

    public function tearDown()
    {
        unlink($this->filename);
    }
}
