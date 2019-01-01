<?php

/*
 * This file is part of the Sonata Project package.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Exporter\Source;

class_alias(
    '\Sonata\\'.__NAMESPACE__.'\IteratorCallbackSourceIterator',
    __NAMESPACE__.'\IteratorCallbackSourceIterator'
);

if (false) {
    class IteratorCallbackSourceIterator extends \Sonata\Exporter\Source\IteratorCallbackSourceIterator
    {
    }
}
