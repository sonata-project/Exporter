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

namespace Sonata\Exporter\Tests\Bridge\Symfony\DependencyInjection;

use Matthias\SymfonyConfigTest\PhpUnit\ConfigurationTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Sonata\Exporter\Bridge\Symfony\DependencyInjection\Configuration;

final class ConfigurationTest extends TestCase
{
    use ConfigurationTestCaseTrait;

    public function getConfiguration(): Configuration
    {
        return new Configuration();
    }

    public function testDefault(): void
    {
        $this->assertProcessedConfigurationEquals([
            [],
        ], [
            'exporter' => ['default_writers' => [
                'csv', 'json', 'xls', 'xml', 'xlsx',
            ]],
            'writers' => [
                'csv' => [
                    'filename' => 'php://output',
                    'delimiter' => ',',
                    'enclosure' => '"',
                    'escape' => '\\',
                    'show_headers' => true,
                    'with_bom' => false,
                ],
                'json' => [
                    'filename' => 'php://output',
                ],
                'xls' => [
                    'filename' => 'php://output',
                    'show_headers' => true,
                ],
                'xlsx' => [
                    'filename' => 'php://output',
                    'show_headers' => true,
                    'show_filters' => true,
                ],
                'xml' => [
                    'filename' => 'php://output',
                    'show_headers' => true,
                    'main_element' => 'datas',
                    'child_element' => 'data',
                ],
            ],
        ]);
    }
}
