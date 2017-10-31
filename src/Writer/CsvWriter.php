<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\Exporter\Writer;

use Sonata\Exporter\Exception\InvalidDataFormatException;

/**
 * @author Thomas Rabaix <thomas.rabaix@sonata-project.org>
 */
class CsvWriter implements TypedWriterInterface
{
    /**
     * @var string
     */
    protected $filename;

    /**
     * @var string
     */
    protected $delimiter;

    /**
     * @var string
     */
    protected $enclosure;

    /**
     * @var string
     */
    protected $escape;

    /**
     * @var resource
     */
    protected $file;

    /**
     * @var bool
     */
    protected $showHeaders;

    /**
     * @var int
     */
    protected $position;

    /**
     * @var bool
     */
    protected $withBom;

    /**
     * @param string $filename
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape
     * @param bool   $showHeaders
     * @param bool   $withBom
     */
    public function __construct(string $filename, string $delimiter = ',', string $enclosure = '"', string $escape = '\\', bool $showHeaders = true, bool $withBom = false)
    {
        $this->filename = $filename;
        $this->delimiter = $delimiter;
        $this->enclosure = $enclosure;
        $this->escape = $escape;
        $this->showHeaders = $showHeaders;
        $this->position = 0;
        $this->withBom = $withBom;

        if (is_file($filename)) {
            throw new \RuntimeException(sprintf('The file %s already exist', $filename));
        }
    }

    /**
     * {@inheritdoc}
     */
    final public function getDefaultMimeType(): string
    {
        return 'text/csv';
    }

    /**
     * {@inheritdoc}
     */
    final public function getFormat(): string
    {
        return 'csv';
    }

    /**
     * {@inheritdoc}
     */
    public function open(): void
    {
        $this->file = fopen($this->filename, 'w', false);
        if (true === $this->withBom) {
            fprintf($this->file, chr(0xEF).chr(0xBB).chr(0xBF));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close(): void
    {
        fclose($this->file);
    }

    /**
     * {@inheritdoc}
     */
    public function write(array $data): void
    {
        if (0 == $this->position && $this->showHeaders) {
            $this->addHeaders($data);

            ++$this->position;
        }

        $result = @fputcsv($this->file, $data, $this->delimiter, $this->enclosure);

        if (!$result) {
            throw new InvalidDataFormatException();
        }

        ++$this->position;
    }

    /**
     * @param array $data
     */
    protected function addHeaders(array $data): void
    {
        $headers = [];
        foreach ($data as $header => $value) {
            $headers[] = $header;
        }

        fputcsv($this->file, $headers, $this->delimiter, $this->enclosure);
    }
}
