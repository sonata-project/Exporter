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

namespace Sonata\Exporter\Writer;

/**
 * Format boolean before use another writer.
 *
 * @author Robin Chalas <robin.chalas@gmail.com>
 */
final class FormattedBoolWriter implements WriterInterface
{
    public function __construct(
        private WriterInterface $writer,
        private string $trueLabel = 'yes',
        private string $falseLabel = 'no',
    ) {
    }

    public function open(): void
    {
        $this->writer->open();
    }

    public function close(): void
    {
        $this->writer->close();
    }

    public function write(array $data): void
    {
        foreach ($data as $key => $value) {
            if (\is_bool($value)) {
                $data[$key] = true === $value ? $this->trueLabel : $this->falseLabel;
            }
        }
        $this->writer->write($data);
    }
}
