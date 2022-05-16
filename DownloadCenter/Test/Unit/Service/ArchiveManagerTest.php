<?php
declare(strict_types=1);

namespace OracDecor\DownloadCenter\Test\Unit\Service;

use Magento\Framework\Filesystem\Driver\File;
use OracDecor\Akeneo\Configuration\AkeneoConfiguration;
use OracDecor\Assets\Model\Asset;
use OracDecor\DownloadCenter\Configuration\General;
use OracDecor\DownloadCenter\Service\ArchiveManager;
use OracDecor\DownloadCenter\Service\Factory\ZippyFactory;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ArchiveManagerTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $zippyFactory;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $filesystem;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $akeneoConfig;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $generalConfig;
    /**
     * @var ArchiveManager
     */
    private $archiveManager;

    public function setUp()
    {
        $this->zippyFactory = $this->createMock(ZippyFactory::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->filesystem = $this->createMock(File::class);
        $this->akeneoConfig = $this->createMock(AkeneoConfiguration::class);
        $this->generalConfig = $this->createMock(General::class);

        $this->archiveManager = new ArchiveManager(
            $this->zippyFactory,
            $this->logger,
            $this->filesystem,
            $this->akeneoConfig,
            $this->generalConfig
        );
    }

    public function testCreateArchiveCaching()
    {
        $filename = 'archive.zip';
        $basePath = '/var/archives';
        $archivePath = $basePath . '/' . $filename;

        $this->generalConfig->expects($this->any())->method('getArchivePath')->willReturn($basePath);

        $this->filesystem->expects($this->any())->method('isExists')->with($archivePath)->willReturn(true);
        $this->generalConfig->expects($this->any())->method('isForceRecreate')->willReturn(false);

        $this->zippyFactory->expects($this->never())->method('create');

        $result = $this->archiveManager->createArchive($filename, []);
        $this->assertEquals($archivePath, $result);
    }

    public function testCreateArchive()
    {
        $filename = 'archive.zip';
        $basePath = '/var/archives';
        $archivePath = $basePath . '/' . $filename;

        $this->generalConfig->expects($this->any())->method('getArchivePath')->willReturn($basePath);

        $this->akeneoConfig->expects($this->any())->method('getPrivatePath')->willReturn('/base');

        $this->filesystem->expects($this->any())->method('isExists')->will($this->returnValueMap([
            [$archivePath, false],
            ['/base/asset1-file1.jpg', true],
            ['/base/asset1-file2.jpg', true],
            ['/base/asset2-file1.jpg', true],
            ['/base/asset2-file3.jpg', false],
        ]));

        $asset1 = $this->createMock(Asset::class);
        $asset1->expects($this->any())->method('getData')->will($this->returnValueMap([
            ['requested_files', null, null],
            ['file_1', null, 'asset1-file1.jpg'],
            ['file_2', null, 'asset1-file2.jpg'],
        ]));
        $asset1->expects($this->once())->method('getAvailableFileFields')->willReturn(['file_1', 'file_2']);
        $asset1->expects($this->once())->method('isDownloadable')->willReturn(true);

        $asset2 = $this->createMock(Asset::class);
        $asset2->expects($this->any())->method('getData')->will($this->returnValueMap([
            ['requested_files', null, ['file_1', 'file_3']],
            ['file_1', null, 'asset2-file1.jpg'],
            ['file_2', null, 'asset2-file2.jpg'],
            ['file_3', null, 'asset2-file3.jpg'],
        ]));
        $asset2->expects($this->once())->method('isDownloadable')->willReturn(true);

        $zippy = $this->createMock(\Alchemy\Zippy\Zippy::class);
        $this->zippyFactory->expects($this->once())->method('create')->willReturn($zippy);
        $this->filesystem->expects($this->once())->method('createDirectory')->with($basePath);

        $zippy->expects($this->once())
            ->method('create')
            ->with(
                $archivePath,
                [
                    'asset1-file1.jpg' => '/base/asset1-file1.jpg',
                    'asset1-file2.jpg' => '/base/asset1-file2.jpg',
                    'asset2-file1.jpg' => '/base/asset2-file1.jpg',
                ]
            );

        $result = $this->archiveManager->createArchive($filename, [$asset1, $asset2]);
        $this->assertEquals($archivePath, $result);
    }
}
