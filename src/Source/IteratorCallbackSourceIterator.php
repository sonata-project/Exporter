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

namespace Sonata\Exporter\Source;

/**
 * IteratorCallbackSource is IteratorSource with callback executed each row.
 *
 * @author Florent Denis <fdenis@ekino.com>
 */
final class IteratorCallbackSourceIterator extends IteratorSourceIterator
{
    /**
     * @param \Iterator $iterator    Iterator with string array elements
     * @param \Closure  $transformer Altering a data row
     */
    public function __construct(
        \Iterator $iterator,
        private \Closure $transformer,
    ) {
        parent::__construct($iterator);
    }

    /**
     * @return array<mixed>
     */
    public function current(): array
    {
        return \call_user_func($this->transformer, $this->iterator->current());
    }
}
