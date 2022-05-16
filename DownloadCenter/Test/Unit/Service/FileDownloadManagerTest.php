<?php
declare(strict_types=1);

namespace OracDecor\DownloadCenter\Test\Unit\Service;

use Magento\Framework\App\ResponseInterface;
use OracDecor\Akeneo\Configuration\AkeneoConfiguration;
use OracDecor\Assets\Api\AssetRepositoryInterface;
use OracDecor\Assets\Api\Data\GroupInterface;
use OracDecor\Assets\Api\GroupRepositoryInterface;
use OracDecor\Assets\Model\Asset;
use OracDecor\Base\App\Response\StreamResponseFactory;
use OracDecor\DownloadCenter\Model\DownloadRequest;
use OracDecor\DownloadCenter\Service\FileDownloadManager;
use OracDecor\DownloadCenter\Service\Validator\GroupValidator;
use PHPUnit\Framework\TestCase;

class FileDownloadManagerTest extends TestCase
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
    private $akeneoConfig;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $streamResponseFactory;
    /**
     * @var FileDownloadManager
     */
    private $fileDownloadManager;

    public function setUp()
    {
        $this->assetRepository = $this->createMock(AssetRepositoryInterface::class);
        $this->groupRepository = $this->createMock(GroupRepositoryInterface::class);
        $this->groupValidator = $this->createMock( GroupValidator::class);
        $this->akeneoConfig = $this->createMock( AkeneoConfiguration::class);
        $this->streamResponseFactory = $this->createMock( StreamResponseFactory::class);

        $this->fileDownloadManager = new FileDownloadManager(
            $this->assetRepository,
            $this->groupRepository,
            $this->groupValidator,
            $this->akeneoConfig,
            $this->streamResponseFactory
        );
    }

    public function testCreateDownload()
    {
        $fileField = 'file_3';
        $assetReference = 'ASSET-1';

        $downloadRequest = new DownloadRequest(['asset_reference' => $assetReference, 'file_field' => $fileField]);
        $asset = $this->createMock(Asset::class);
        $group = $this->createMock(GroupInterface::class);
        $this->assetRepository->expects($this->once())->method('getByReference')->with($assetReference)->willReturn($asset);
        $asset->expects($this->any())->method('getReference')->willReturn($assetReference);
        $asset->expects($this->once())->method('isActive')->willReturn(true);
        $asset->expects($this->once())->method('isDownloadable')->willReturn(true);
        $asset->expects($this->once())->method('getGroupId')->willReturn(1);
        $asset->expects($this->any())->method('getData')->with($fileField)->willReturn('path/test.jpg');
        $this->groupRepository->expects($this->once())->method('getById')->with(1)->willReturn($group);
        $this->groupValidator->expects($this->once())->method('isAssetGroupAllowedForCustomer')->with($group)->willReturn(true);

        $this->akeneoConfig->expects($this->any())->method('getPrivatePath')->willReturn('/base');

        $streamResponse = $this->createMock(ResponseInterface::class);

        $this->streamResponseFactory->expects($this->once())
            ->method('create')
            ->with(
                '/base/path/test.jpg',
                'test.jpg'
            )
            ->willReturn($streamResponse);

        $this->assertEquals($streamResponse, $this->fileDownloadManager->createDownload($downloadRequest));
    }
}
