<?php
declare(strict_types=1);

namespace OracDecor\DownloadCenter\Test\Unit\Service;

use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use OracDecor\Assets\Api\AssetRepositoryInterface;
use OracDecor\Assets\Api\Data\GroupInterface;
use OracDecor\Assets\Api\GroupRepositoryInterface;
use OracDecor\Assets\Model\Asset;
use OracDecor\DownloadCenter\Model\DownloadRequest;
use OracDecor\DownloadCenter\Service\ArchiveManager;
use OracDecor\DownloadCenter\Service\FilesDownloadManager;
use OracDecor\DownloadCenter\Service\Validator\GroupValidator;
use PHPUnit\Framework\TestCase;

class FilesDownloadManagerTest extends TestCase
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
    private $jsonFactory;
    /**
     * @var FilesDownloadManager
     */
    private $filesDownloadManager;

    public function setUp()
    {
        $this->searchCriteriaBuilder = $this->createMock(SearchCriteriaBuilder::class);
        $this->searchCriteria = $this->createMock(SearchCriteria::class);
        $this->assetRepository = $this->createMock(AssetRepositoryInterface::class);
        $this->groupRepository = $this->createMock(GroupRepositoryInterface::class);
        $this->groupValidator = $this->createMock( GroupValidator::class);
        $this->archiveManager = $this->createMock( ArchiveManager::class);
        $this->jsonFactory = $this->createMock( JsonFactory::class);

        $this->filesDownloadManager = new FilesDownloadManager(
            $this->searchCriteriaBuilder,
            $this->assetRepository,
            $this->groupRepository,
            $this->groupValidator,
            $this->archiveManager,
            $this->jsonFactory
        );
    }

    public function testCreateDownload()
    {
        $assetReference1 = 'ASSET-1';
        $assetReference2 = 'ASSET-2';
        $data = [$assetReference1 => ['file_1', 'file_2'], $assetReference2 => ['file_3']];
        $downloadRequest = new DownloadRequest($data);


        $asset1 = $this->createMock(Asset::class);
        $asset1->expects($this->any())->method('getReference')->willReturn($assetReference1);
        $asset1->expects($this->any())->method('getGroupId')->willReturn(1);
        $asset1->expects($this->once())->method('setData')->with('requested_files', ['file_1', 'file_2']);
        $asset2 = $this->createMock(Asset::class);
        $asset2->expects($this->any())->method('getReference')->willReturn($assetReference2);
        $asset2->expects($this->any())->method('getGroupId')->willReturn(1);
        $asset2->expects($this->once())->method('setData')->with('requested_files', ['file_3']);

        $this->searchCriteriaBuilder->expects($this->exactly(3))->method('addFilter');
        $this->searchCriteriaBuilder->expects($this->exactly(2))->method('create')->willReturn($this->searchCriteria);

        $assetResult = $this->createMock(SearchResultsInterface::class);
        $assetResult->expects($this->any())->method('getItems')->willReturn([$asset1, $asset2]);
        $assetResult->expects($this->once())->method('getTotalCount')->willReturn(2);
        $this->assetRepository->expects($this->once())->method('getList')->with($this->searchCriteria)->willReturn($assetResult);

        $group = $this->createMock(GroupInterface::class);
        $groupResult = $this->createMock(SearchResultsInterface::class);
        $groupResult->expects($this->once())->method('getItems')->willReturn([$group]);
        $this->groupRepository->expects($this->once())->method('getList')->with($this->searchCriteria)->willReturn($groupResult);
        $this->groupValidator->expects($this->once())->method('isAssetGroupAllowedForCustomer')->with($group)->willReturn(true);

        $archiveName = md5(json_encode($data)). '.zip';
        $this->archiveManager->expects($this->once())->method('createArchive')->with($archiveName, [$asset1, $asset2])->willReturn($archiveName);

        $json = $this->createMock(Json::class);
        $this->jsonFactory->expects($this->once())->method('create')->willReturn($json);
        $json->expects($this->once())->method('setData')->with(['token' => $archiveName]);

        $this->assertEquals($json, $this->filesDownloadManager->createDownload($downloadRequest));
    }
}
