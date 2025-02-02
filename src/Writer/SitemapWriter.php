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
 * Generates a sitemap site from.
 */
final class SitemapWriter implements WriterInterface
{
    public const LIMIT_SIZE = 10_485_760;
    public const LIMIT_URL = 50000;

    private string $pattern;

    /**
     * @var resource|null
     *
     * @phpstan-var resource|null
     * @psalm-var resource|closed-resource|null
     */
    private $buffer;

    private int $bufferSize = 0;

    private int $bufferUrlCount = 0;

    private int $bufferPart = 0;

    /**
     * @param string   $folder    The folder to store the sitemap.xml file
     * @param string   $groupName Name of sub-sitemap (optional)
     * @param string[] $headers   Indicate the need for namespace in the header sitemap
     * @param bool     $autoIndex If you want to generate index of sitemap (optional)
     *
     * @phpstan-param non-empty-string $folder
     */
    public function __construct(
        private string $folder,
        private string $groupName = '',
        private array $headers = [],
        private bool $autoIndex = true,
    ) {
        $this->pattern = 'sitemap_'.('' !== $this->groupName ? $this->groupName.'_' : '').'%05d.xml';
    }

    /**
     * Returns the status of auto generation of index site map.
     */
    public function isAutoIndex(): bool
    {
        return $this->autoIndex;
    }

    /**
     * Returns folder to store the sitemap.xml file.
     */
    public function getFolder(): string
    {
        return $this->folder;
    }

    public function open(): void
    {
        $this->bufferPart = 0;
        $this->generateNewPart();
    }

    public function write(array $data): void
    {
        $data = $this->buildData($data);

        $line = match ($data['type']) {
            'video' => $this->generateVideoLine($data),
            'image' => $this->generateImageLine($data),
            default => $this->generateDefaultLine($data),
        };

        $this->addSitemapLine($line);
    }

    public function close(): void
    {
        if (null !== $this->buffer) {
            $this->closeSitemap();
        }

        if ($this->autoIndex) {
            self::generateSitemapIndex(
                $this->folder,
                'sitemap_'.('' !== $this->groupName ? $this->groupName.'_' : '').'*.xml',
                'sitemap'.('' !== $this->groupName ? '_'.$this->groupName : '').'.xml'
            );
        }
    }

    /**
     * Generates the sitemap index from the sitemap part avaible in the folder.
     *
     * @param string $folder   A folder to write sitemap index
     * @param string $baseUrl  A base URL
     * @param string $pattern  A sitemap pattern, optional
     * @param string $filename A sitemap file name, optional
     *
     * @throws \RuntimeException
     *
     * @phpstan-param non-empty-string $folder
     * @phpstan-param non-empty-string $pattern
     */
    public static function generateSitemapIndex(string $folder, string $baseUrl, string $pattern = 'sitemap*.xml', string $filename = 'sitemap.xml'): void
    {
        $content = "<?xml version='1.0' encoding='UTF-8'?>\n<sitemapindex xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' xsi:schemaLocation='http://www.sitemaps.org/schemas/sitemap/1.0 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd' xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>\n";
        $files = glob(\sprintf('%s/%s', $folder, $pattern));
        if (false === $files) {
            throw new \RuntimeException('Cannot find sitemap files.');
        }

        foreach ($files as $file) {
            $stat = stat($file);
            if (false === $stat) {
                throw new \RuntimeException(\sprintf('Cannot access to stats of the file %s.', $file));
            }

            $content .= \sprintf(
                "\t".'<sitemap><loc>%s/%s</loc><lastmod>%s</lastmod></sitemap>'."\n",
                $baseUrl,
                basename($file),
                date('Y-m-d', $stat['mtime'])
            );
        }

        $content .= '</sitemapindex>';

        file_put_contents(\sprintf('%s/%s', $folder, $filename), $content);
    }

    /**
     * Generate a new sitemap part.
     *
     * @throws \RuntimeException
     */
    private function generateNewPart(): void
    {
        if (null !== $this->buffer) {
            $this->closeSitemap();
        }

        $this->bufferUrlCount = 0;
        $this->bufferSize = 0;
        ++$this->bufferPart;

        if (!is_writable($this->folder)) {
            throw new \RuntimeException(\sprintf('Unable to write to folder: %s', $this->folder));
        }

        $filename = \sprintf($this->pattern, $this->bufferPart);

        $buffer = fopen($this->folder.'/'.$filename, 'w');
        if (false === $buffer) {
            throw new \Exception(\sprintf('Cannot open file %s.', $this->folder.'/'.$filename));
        }
        $this->buffer = $buffer;

        $written = fwrite($this->buffer, '<?xml version="1.0" encoding="UTF-8"?>'."\n".'<urlset xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd" xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'.$this->getHeaderByFlag().'>'."\n");
        if (false === $written) {
            throw new \Exception(\sprintf('Cannot write file %s.', $this->folder.'/'.$filename));
        }

        $this->bufferSize += $written;
    }

    /**
     * Add a new line into the sitemap part.
     */
    private function addSitemapLine(string $line): void
    {
        if ($this->bufferUrlCount >= self::LIMIT_URL) {
            $this->generateNewPart();
        }

        if (($this->bufferSize + \strlen($line) + 9) > self::LIMIT_SIZE) {
            $this->generateNewPart();
        }

        ++$this->bufferUrlCount;

        $written = fwrite($this->getBuffer(), $line);
        if (false === $written) {
            throw new \Exception(\sprintf('Cannot write line %s in the buffer.', $line));
        }

        $this->bufferSize += $written;
    }

    /**
     * Build data with default parameters.
     *
     * @param array<string, mixed> $data List of parameters
     *
     * @return array<string, mixed>
     */
    private function buildData(array $data): array
    {
        $default = [
            'url' => null,
            'lastmod' => 'now',
            'changefreq' => 'weekly',
            'priority' => 0.5,
            'type' => 'default',
        ];

        $data = array_merge($default, $data);

        $this->fixDataType($data);

        return $data;
    }

    /**
     * Fix type of data, if data type is specific,
     * he must to be defined in data and he must to be a array.
     *
     * @param array<string, mixed> $data List of parameters
     */
    private function fixDataType(array &$data): void
    {
        if ('default' === $data['type']) {
            return;
        }

        $valid_var_name = [
            'image' => 'images',
            'video' => 'video',
        ];

        if (
            !isset($valid_var_name[$data['type']], $data[$valid_var_name[$data['type']]])
            || !\is_array($data[$valid_var_name[$data['type']]])
        ) {
            $data['type'] = 'default';
        }
    }

    /**
     * Generate standard line of sitemap.
     *
     * @param array<string, mixed> $data List of parameters
     */
    private function generateDefaultLine(array $data): string
    {
        return \sprintf(
            '    <url><loc>%s</loc><lastmod>%s</lastmod><changefreq>%s</changefreq><priority>%s</priority></url>'."\n",
            $data['url'],
            (new \DateTime($data['lastmod']))->format('Y-m-d'),
            $data['changefreq'],
            $data['priority']
        );
    }

    /**
     * Generate image line of sitemap.
     *
     * @param array<string, mixed> $data List of parameters
     */
    private function generateImageLine(array $data): string
    {
        $images = '';

        if (\count($data['images']) > 1000) {
            $data['images'] = array_splice($data['images'], 1000);
        }

        $builder = [
            'url' => 'loc',
            'location' => 'geo_location',
        ];

        foreach ($data['images'] as $image) {
            $images .= '<image:image>';

            foreach ($image as $key => $element) {
                $images .= \sprintf('<image:%1$s>%2$s</image:%1$s>', $builder[$key] ?? $key, $element);
            }

            $images .= '</image:image>';
        }

        return \sprintf('    <url><loc>%s</loc>%s</url>'."\n", $data['url'], $images);
    }

    /**
     * Generate video line of sitemap.
     *
     * @param array<string, mixed> $data List of parameters
     */
    private function generateVideoLine(array $data): string
    {
        $videos = '';
        $builder = [
            'thumbnail' => 'thumbnail_loc',
        ];

        foreach ($data['video'] as $key => $video) {
            $videos .= \sprintf('<video:%1$s>%2$s</video:%1$s>', $builder[$key] ?? $key, $video);
        }

        return \sprintf('    <url><loc>%s</loc><video:video>%s</video:video></url>'."\n", $data['url'], $videos);
    }

    /**
     * Generate additional header with namespace adapted to the content.
     */
    private function getHeaderByFlag(): string
    {
        $namespaces = [
            'video' => 'xmlns:video="http://www.google.com/schemas/sitemap-video/1.1"',
            'image' => 'xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"',
        ];

        $result = '';
        foreach ($this->headers as $flag) {
            $result .= ' '.$namespaces[$flag];
        }

        return $result;
    }

    /**
     * @psalm-suppress InvalidPassByReference
     *
     * @see https://github.com/vimeo/psalm/issues/7505
     */
    private function closeSitemap(): void
    {
        fwrite($this->getBuffer(), '</urlset>');
        fclose($this->getBuffer());
    }

    /**
     * @return resource
     */
    private function getBuffer()
    {
        if (!\is_resource($this->buffer)) {
            throw new \LogicException('You MUST open the file first');
        }

        return $this->buffer;
    }
}
