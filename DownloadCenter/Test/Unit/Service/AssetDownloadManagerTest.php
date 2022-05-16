<?php
declare(strict_types=1);

namespace OracDecor\DownloadCenter\Test\Unit\Service;

use Magento\Framework\App\ResponseInterface;
use OracDecor\Assets\Api\AssetRepositoryInterface;
use OracDecor\Assets\Api\Data\AssetInterface;
use OracDecor\Assets\Api\Data\GroupInterface;
use OracDecor\Assets\Api\GroupRepositoryInterface;
use OracDecor\Base\App\Response\StreamResponseFactory;
use OracDecor\DownloadCenter\Model\DownloadRequest;
use OracDecor\DownloadCenter\Service\ArchiveManager;
use OracDecor\DownloadCenter\Service\AssetDownloadManager;
use OracDecor\DownloadCenter\Service\Validator\GroupValidator;
use PHPUnit\Framework\TestCase;

class AssetDownloadManagerTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $assetRepository;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $groupRepository;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $groupValidator;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $archiveManager;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $streamResponseFactory;
    /**
     * @var AssetDownloadManager
     */
    private $assetDownloadManager;

    public function setUp()
    {
        $this->assetRepository = $this->createMock(AssetRepositoryInterface::class);
        $this->groupRepository = $this->createMock(GroupRepositoryInterface::class);
        $this->groupValidator = $this->createMock( GroupValidator::class);
        $this->archiveManager = $this->createMock( ArchiveManager::class);
        $this->streamResponseFactory = $this->createMock( StreamResponseFactory::class);

        $this->assetDownloadManager = new AssetDownloadManager(
            $this->assetRepository,
            $this->groupRepository,
            $this->groupValidator,
            $this->archiveManager,
            $this->streamResponseFactory
        );
    }

    public function testCreateDownload()
    {
        $assetReference = 'ASSET-1';
        $assetId = 3;
        $archiveName = md5($assetReference.$assetId) . '.zip';

        $downloadRequest = new DownloadRequest(['asset_reference' => $assetReference]);
        $asset = $this->createMock(AssetInterface::class);
        $group = $this->createMock(GroupInterface::class);
        $this->assetRepository->expects($this->once())->method('getByReference')->with($assetReference)->willReturn($asset);
        $asset->expects($this->any())->method('getReference')->willReturn($assetReference);
        $asset->expects($this->once())->method('getAssetId')->willReturn($assetId);
        $asset->expects($this->once())->method('isActive')->willReturn(true);
        $asset->expects($this->once())->method('getGroupId')->willReturn(1);
        $this->groupRepository->expects($this->once())->method('getById')->with(1)->willReturn($group);
        $this->groupValidator->expects($this->once())->method('isAssetGroupAllowedForCustomer')->with($group)->willReturn(true);

        $streamResponse = $this->createMock(ResponseInterface::class);

        $this->archiveManager->expects($this->once())
            ->method('createArchive')
            ->with($archiveName, [$asset])
            ->willReturn('/full/path/to/archive/' . $archiveName);

        $this->streamResponseFactory->expects($this->once())
            ->method('create')
            ->with(
                '/full/path/to/archive/' . $archiveName,
                $assetReference . '.zip'
            )
            ->willReturn($streamResponse);

        $this->assertEquals($streamResponse, $this->assetDownloadManager->createDownload($downloadRequest));
    }
}
