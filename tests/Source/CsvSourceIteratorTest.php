<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Exporter\Test\Source;

use Sonata\Exporter\Source\CsvSourceIterator;

class CsvSourceIteratorTest extends \PHPUnit_Framework_TestCase
{
    protected $filename;

    public function setUp(): void
    {
        $this->filename = 'foobar.csv';

        if (is_file($this->filename)) {
            unlink($this->filename);
        }

        $csv = <<<'EOF'
firstname,name
John 1,Doe
John 2,Doe
"John, 3", Doe
EOF;
        file_put_contents($this->filename, $csv);
    }

    public function tearDown(): void
    {
        unlink($this->filename);
    }

    public function testHandler(): void
    {
        $iterator = new CsvSourceIterator($this->filename);

        $i = 0;
        foreach ($iterator as $value) {
            $this->assertTrue(is_array($value));
            $this->assertEquals(2, count($value));
            $this->assertEquals($i, $iterator->key());
            $keys = array_keys($value);
            $this->assertEquals('firstname', $keys[0]);
            $this->assertEquals('name', $keys[1]);
            ++$i;
        }
        $this->assertEquals(3, $i);
    }

    public function testNoHeaders(): void
    {
        $iterator = new CsvSourceIterator($this->filename, ',', '"', '\\', false);

        $i = 0;
        foreach ($iterator as $value) {
            $this->assertTrue(is_array($value));
            $this->assertEquals(2, count($value));
            $this->assertEquals($i, $iterator->key());
            ++$i;
        }
        $this->assertEquals(4, $i);
    }

    public function testRewind(): void
    {
        $iterator = new CsvSourceIterator($this->filename);

        $i = 0;
        foreach ($iterator as $value) {
            $this->assertTrue(is_array($value));
            $this->assertEquals(2, count($value));
            ++$i;
        }
        $this->assertEquals(3, $i);

        $i = 0;
        foreach ($iterator as $value) {
            $this->assertTrue(is_array($value));
            $this->assertEquals(2, count($value));
            ++$i;
        }
        $this->assertEquals(3, $i);
    }
}
