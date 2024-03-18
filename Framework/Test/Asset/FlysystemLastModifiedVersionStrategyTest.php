<?php declare(strict_types=1);

namespace Shopware\Core\Framework\Test\Asset;

use League\Flysystem\Filesystem;
use League\Flysystem\FilesystemOperator;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Framework\Adapter\Asset\FlysystemLastModifiedVersionStrategy;
use Shopware\Core\Framework\Adapter\Filesystem\MemoryFilesystemAdapter;
use Symfony\Component\Asset\UrlPackage;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\TagAwareAdapter;

/**
 * @internal
 */
class FlysystemLastModifiedVersionStrategyTest extends TestCase
{
    private Filesystem $fs;

    private UrlPackage $asset;

    private FlysystemLastModifiedVersionStrategy $strategy;

    protected function setUp(): void
    {
        $this->fs = new Filesystem(new MemoryFilesystemAdapter());
        $this->strategy = new FlysystemLastModifiedVersionStrategy('test', $this->fs, new TagAwareAdapter(new ArrayAdapter(), new ArrayAdapter()));
        $this->asset = new UrlPackage(['http://snapadmin.net'], $this->strategy);
    }

    public function testNonExistentFile(): void
    {
        $url = $this->asset->getUrl('test');
        static::assertSame('http://snapadmin.net/test', $url);
    }

    public function testExistsFile(): void
    {
        $this->fs->write('testFile', 'yea');
        $lastModified = (string) $this->fs->lastModified('testFile');
        $url = $this->asset->getUrl('testFile');
        static::assertSame('http://snapadmin.net/testFile?' . $lastModified, $url);
    }

    public function testApplyDoesSameAsGetVersion(): void
    {
        static::assertSame($this->strategy->getVersion('foo'), $this->strategy->getVersion('foo'));
    }

    public function testFolder(): void
    {
        $this->fs->write('folder/file', 'test');

        static::assertSame('http://snapadmin.net/folder', $this->asset->getUrl('folder'));
        static::assertSame('http://snapadmin.net/not_existing/bla', $this->asset->getUrl('not_existing/bla'));
        static::assertSame('http://snapadmin.net/folder', $this->asset->getUrl('folder'));
    }

    public function testWithEmptyString(): void
    {
        $fs = $this->createMock(FilesystemOperator::class);
        $fs->expects(static::never())->method('lastModified');

        $strategy = new FlysystemLastModifiedVersionStrategy('test', $fs, new TagAwareAdapter(new ArrayAdapter(), new ArrayAdapter()));

        static::assertSame('', $strategy->getVersion(''));
    }
}
