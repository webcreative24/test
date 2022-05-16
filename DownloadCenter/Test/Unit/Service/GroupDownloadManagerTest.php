<?php
declare(strict_types=1);

namespace OracDecor\DownloadCenter\Test\Unit\Service;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\App\ResponseInterface;
use OracDecor\Assets\Api\AssetRepositoryInterface;
use OracDecor\Assets\Api\Data\AssetInterface;
use OracDecor\Assets\Api\Data\GroupInterface;
use OracDecor\Assets\Api\GroupRepositoryInterface;
use OracDecor\Base\App\Response\StreamResponseFactory;
use OracDecor\DownloadCenter\Model\DownloadRequest;
use OracDecor\DownloadCenter\Service\ArchiveManager;
use OracDecor\DownloadCenter\Service\GroupDownloadManager;
use OracDecor\DownloadCenter\Service\Validator\GroupValidator;
use PHPUnit\Framework\TestCase;

class GroupDownloadManagerTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $searchCriteriaBuilder;
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $searchCriteria;
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
     * @var GroupDownloadManager
     */
    private $groupDownloadManager;

    public function setUp()
    {
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->searchCriteria = $this->createMock(SearchCriteria::class);
        $this->assetRepository = $this->createMock(AssetRepositoryInterface::class);
        $this->groupRepository = $this->createMock(GroupRepositoryInterface::class);
        $this->groupValidator = $this->createMock( GroupValidator::class);
        $this->archiveManager = $this->createMock( ArchiveManager::class);
        $this->streamResponseFactory = $this->createMock( StreamResponseFactory::class);

        $this->groupDownloadManager = new GroupDownloadManager(
            $this->searchCriteriaBuilder,
            $this->assetRepository,
            $this->groupRepository,
            $this->groupValidator,
            $this->archiveManager,
            $this->streamResponseFactory
        );
    }

    public function testCreateDownload()
    {
        $groupReference = 'GROUP-1';
        $groupId = 3;
        $archiveName = md5($groupReference.$groupId) . '.zip';

        $downloadRequest = new DownloadRequest(['group_reference' => $groupReference]);
        $asset = $this->createMock(AssetInterface::class);
        $group = $this->createMock(GroupInterface::class);
        $group->expects($this->any())->method('getReference')->willReturn($groupReference);
        $group->expects($this->any())->method('getGroupId')->willReturn($groupId);

        $this->groupRepository->expects($this->once())->method('getByReference')->with($groupReference)->willReturn($group);
        $this->groupValidator->expects($this->once())->method('isAssetGroupAllowedForCustomer')->with($group)->willReturn(true);

        $this->searchCriteriaBuilder->expects($this->exactly(2))->method('addFilter');
        $this->searchCriteriaBuilder->expects($this->once())->method('create')->willReturn($this->searchCriteria);
        $assetResult = $this->createMock(SearchResultsInterface::class);

        $this->assetRepository->expects($this->once())->method('getList')->with($this->searchCriteria)->willReturn($assetResult);
        $assetResult->expects($this->once())->method('getTotalCount')->willReturn(1);
        $assetResult->expects($this->once())->method('getItems')->willReturn([$asset]);

        $streamResponse = $this->createMock(ResponseInterface::class);

        $this->archiveManager->expects($this->once())
            ->method('createArchive')
            ->with($archiveName, [$asset])
            ->willReturn('/full/path/to/archive/' . $archiveName);

        $this->streamResponseFactory->expects($this->once())
            ->method('create')
            ->with(
                '/full/path/to/archive/' . $archiveName,
                $groupReference . '.zip'
            )
            ->willReturn($streamResponse);

        $this->assertEquals($streamResponse, $this->groupDownloadManager->createDownload($downloadRequest));
    }
}
