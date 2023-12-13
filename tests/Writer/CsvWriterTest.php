<?php

declare(strict_types=1);

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Exporter\Tests\Writer;

use PHPUnit\Framework\TestCase;
use Sonata\Exporter\Exception\InvalidDataFormatException;
use Sonata\Exporter\Formatter\BoolFormatter;
use Sonata\Exporter\Formatter\DateTimeFormatter;
use Sonata\Exporter\Formatter\EnumFormatter;
use Sonata\Exporter\Formatter\IterableFormatter;
use Sonata\Exporter\Tests\Source\Fixtures\Suit;
use Sonata\Exporter\Writer\CsvWriter;

final class CsvWriterTest extends TestCase
{
    private string $filename;

    protected function setUp(): void
    {
        parent::setUp();
        $this->filename = 'foobar.csv';

        if (is_file($this->filename)) {
            unlink($this->filename);
        }
    }

    protected function tearDown(): void
    {
        if (is_file($this->filename)) {
            unlink($this->filename);
        }
    }

    public function testInvalidDataFormat(): void
    {
        $this->expectException(InvalidDataFormatException::class);

        $writer = new CsvWriter($this->filename, ',', '', '\\', false);
        $writer->open();

        $writer->write(['john "2', 'doe', '1']);
    }

    public function testEnclosureFormating(): void
    {
        $writer = new CsvWriter($this->filename, ',', '"', '\\', false);
        $writer->open();

        $writer->write([' john , ""2"', 'doe', '1']);

        $writer->close();

        $expected = '" john , """"2""",doe,1';

        $content = file_get_contents($this->filename);
        static::assertIsString($content);
        static::assertSame($expected, trim($content));
    }

    public function testEscapeFormating(): void
    {
        $writer = new CsvWriter($this->filename, ',', '"', '/', false);

        $writer->open();

        $writer->write(['john', 'doe', '\\', '/']);

        $writer->close();

        $expected = 'john,doe,\,"/"';

        $content = file_get_contents($this->filename);
        static::assertIsString($content);
        static::assertSame($expected, trim($content));
    }

    public function testWithTerminate(): void
    {
        $writer = new CsvWriter($this->filename, ',', '"', '\\', false, false, "\r\n");
        $writer->open();

        $writer->write(['john', 'doe', '1']);
        $writer->write(['john', 'doe', '2']);

        $writer->close();

        $expected = "john,doe,1\r\njohn,doe,2";

        $content = file_get_contents($this->filename);
        static::assertIsString($content);
        static::assertSame($expected, trim($content));
    }

    public function testEnclosureFormatingWithExcel(): void
    {
        $writer = new CsvWriter($this->filename, ',', '"', '\\', false);
        $writer->open();

        $writer->write(['john , ""2"', 'doe ', '1']);

        $writer->close();

        $expected = '"john , """"2""","doe ",1';

        $content = file_get_contents($this->filename);
        static::assertIsString($content);
        static::assertSame($expected, trim($content));
    }

    public function testWithHeaders(): void
    {
        $writer = new CsvWriter($this->filename, ',', '"', '\\', true);
        $writer->open();

        $writer->write(['name' => 'john , ""2"', 'surname' => 'doe ', 'year' => '2001']);

        $writer->close();

        $expected = 'name,surname,year'."\n".'"john , """"2""","doe ",2001';

        $content = file_get_contents($this->filename);
        static::assertIsString($content);
        static::assertSame($expected, trim($content));
    }

    public function testWithBom(): void
    {
        $writer = new CsvWriter($this->filename, ',', '"', '\\', true, true);
        $writer->open();

        $writer->write(['name' => 'Rémi , ""2"', 'surname' => 'doe ', 'year' => '2001']);

        $writer->close();

        $expected = \chr(0xEF).\chr(0xBB).\chr(0xBF).'name,surname,year'."\n".'"Rémi , """"2""","doe ",2001';

        $content = file_get_contents($this->filename);
        static::assertIsString($content);
        static::assertSame($expected, trim($content));
    }

    /**
     * @requires PHP >= 8.1
     */
    public function testValueFormatting(): void
    {
        $writer = new CsvWriter($this->filename, ',', '"', '\\', false);
        $writer->addFormatter(new BoolFormatter());
        $writer->addFormatter(new DateTimeFormatter());
        $writer->addFormatter(new EnumFormatter());
        $writer->addFormatter(new IterableFormatter());
        $writer->open();

        $writer->write([
            ' john , ""2"',
            'doe',
            '1',
            true,
            new \DateTimeImmutable('1986-03-22 19:45:54', new \DateTimeZone('America/Argentina/Buenos_Aires')),
            Suit::Hearts,
            [
                'foo' => ['bool', 'float'],
                'bar' => ['string', 'int'],
            ],
        ]);

        $writer->close();

        $expected = '" john , """"2""",doe,1,yes,"Sat, 22 Mar 1986 19:45:54 -0300",H,"[Array, Array]"';

        $content = file_get_contents($this->filename);
        static::assertIsString($content);
        static::assertSame($expected, trim($content));
    }
}
