<?php declare(strict_types=1);

namespace Shopware\Core\Content\Sitemap\Service;

use League\Flysystem\FilesystemOperator;
use Shopware\Core\Content\ImportExport\Exception\FileNotReadableException;
use Shopware\Core\Content\Sitemap\Event\SitemapFilterOpenTagEvent;
use Shopware\Core\Content\Sitemap\Struct\Url;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class SitemapHandle implements SitemapHandleInterface
{
    private const MAX_URLS = 49999;
    private const SITEMAP_NAME_PATTERN = 'sitemap%s-%d.xml.gz';

    private array $tmpFiles = [];

    private FilesystemOperator $filesystem;

    private EventDispatcherInterface $eventDispatcher;

    private SalesChannelContext $context;

    /**
     * @var resource
     */
    private $handle;

    private int $index = 1;

    private int $urlCount = 0;

    private ?string $domainName = null;

    /**
     * @internal
     */
    public function __construct(
        FilesystemOperator $filesystem,
        SalesChannelContext $context,
        EventDispatcherInterface $eventDispatcher,
        ?string $domain = null
    ) {
        $this->setDomainName($domain);
        $this->filesystem = $filesystem;
        $this->eventDispatcher = $eventDispatcher;
        $this->context = $context;

        $filePath = $this->getTmpFilePath($context);
        $this->openGzip($filePath);
        $this->printHeader();

        $this->tmpFiles[] = $filePath;
    }

    /**
     * @param Url[] $urls
     */
    public function write(array $urls): void
    {
        foreach ($urls as $url) {
            gzwrite($this->handle, (string) $url);
            ++$this->urlCount;

            if ($this->urlCount % self::MAX_URLS === 0) {
                $this->printFooter();
                gzclose($this->handle);
                ++$this->index;
                $path = $this->getTmpFilePath($this->context);
                $this->openGzip($path);
                $this->printHeader();
                $this->tmpFiles[] = $path;
            }
        }
    }

    public function finish(?bool $cleanUp = true): void
    {
        if ($cleanUp) {
            $this->cleanUp();
        }

        if (\is_resource($this->handle)) {
            $this->printFooter();
            gzclose($this->handle);
        }

        foreach ($this->tmpFiles as $i => $tmpFile) {
            $sitemapPath = $this->getFilePath($i + 1, $this->context);
            if ($this->filesystem->fileExists($sitemapPath)) {
                $this->filesystem->delete($sitemapPath);
            }

            $this->filesystem->write($sitemapPath, file_get_contents($tmpFile));
            @unlink($tmpFile);
        }
    }

    private function getFilePath(int $index, SalesChannelContext $salesChannelContext): string
    {
        return $this->getPath($salesChannelContext) . $this->getFileName($salesChannelContext, $index);
    }

    private function getPath(SalesChannelContext $salesChannelContext): string
    {
        return 'sitemap/salesChannel-' . $salesChannelContext->getSalesChannel()->getId() . '-' . $salesChannelContext->getLanguageId() . '/';
    }

    private function getTmpFilePath(SalesChannelContext $salesChannelContext): string
    {
        return rtrim(sys_get_temp_dir(), '/') . '/' . $this->getFileName($salesChannelContext);
    }

    private function getFileName(SalesChannelContext $salesChannelContext, ?int $index = null): string
    {
        if ($this->domainName === null) {
            return sprintf($salesChannelContext->getSalesChannel()->getId() . '-' . self::SITEMAP_NAME_PATTERN, null, $index ?? $this->index);
        }

        return sprintf($salesChannelContext->getSalesChannel()->getId() . '-' . self::SITEMAP_NAME_PATTERN, '-' . $this->domainName, $index ?? $this->index);
    }

    private function printHeader(): void
    {
        /** @var SitemapFilterOpenTagEvent $sitemapOpenTagEvent */
        $sitemapOpenTagEvent = $this->eventDispatcher->dispatch(
            new SitemapFilterOpenTagEvent($this->context)
        );

        gzwrite($this->handle, $sitemapOpenTagEvent->getFullOpenTag());
    }

    private function printFooter(): void
    {
        gzwrite($this->handle, '</urlset>');
    }

    private function cleanUp(): void
    {
        try {
            $files = $this->filesystem->listContents($this->getPath($this->context));
        } catch (\Throwable $e) {
            // Folder does not exists
            return;
        }

        foreach ($files as $file) {
            $this->filesystem->delete($file['path']);
        }
    }

    private function setDomainName(?string $domain = null): void
    {
        if ($domain === null) {
            return;
        }

        $host = parse_url($domain, \PHP_URL_HOST);
        if ($host) {
            $host = str_replace('.', '-', $host);
        }

        $path = parse_url($domain, \PHP_URL_PATH);
        if ($path) {
            $path = str_replace('/', '-', $path);
        }

        $this->domainName = $host . $path;
    }

    private function openGzip(string $filePath): void
    {
        $handle = gzopen($filePath, 'ab');
        if ($handle === false) {
            throw new FileNotReadableException($filePath);
        }

        $this->handle = $handle;
    }
}
