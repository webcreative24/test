<?php
declare(strict_types=1);

namespace OracDecor\DownloadCenter\Service;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Controller\Result\JsonFactory;
use OracDecor\Assets\Api\AssetRepositoryInterface;
use OracDecor\Assets\Api\Data\AssetInterface;
use OracDecor\Assets\Api\Data\GroupInterface;
use OracDecor\Assets\Api\GroupRepositoryInterface;
use OracDecor\DownloadCenter\Api\DownloadManagerInterface;
use OracDecor\DownloadCenter\Exception\InvalidRequestException;
use OracDecor\DownloadCenter\Model\DownloadRequest;
use OracDecor\DownloadCenter\Service\Validator\GroupValidator;

class FilesDownloadManager implements DownloadManagerInterface
{
    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;
    /**
     * @var AssetRepositoryInterface
     */
    private $assetRepository;
    /**
     * @var GroupRepositoryInterface
     */
    private $groupRepository;
    /**
     * @var GroupValidator
     */
    private $groupValidator;
    /**
     * @var ArchiveManager
     */
    private $archiveManager;
    /**
     * @var JsonFactory
     */
    private $jsonFactory;

    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AssetRepositoryInterface $assetRepository,
        GroupRepositoryInterface $groupRepository,
        GroupValidator $groupValidator,
        ArchiveManager $archiveManager,
        JsonFactory $jsonFactory
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->assetRepository = $assetRepository;
        $this->groupRepository = $groupRepository;
        $this->groupValidator = $groupValidator;
        $this->archiveManager = $archiveManager;
        $this->jsonFactory = $jsonFactory;
    }

    /**
     * @param DownloadRequest $request
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws \Exception
     */
    public function createDownload(DownloadRequest $request)
    {
        $this->searchCriteriaBuilder->addFilter('main_table.' . AssetInterface::FIELD_REFERENCE, array_keys($request->getData()), 'in');
        $this->searchCriteriaBuilder->addFilter('main_table.' . AssetInterface::FIELD_ACTIVE, 1);
        $assetResult = $this->assetRepository->getList($this->searchCriteriaBuilder->create());
        if ($assetResult->getTotalCount() <= 0) {
            throw new InvalidRequestException('Invalid download request: no assets found');
        }

        $assetGroupIds = [];
        foreach ($assetResult->getItems() as $asset) {
            $fileFields = $request->getData($asset->getReference());
            if (empty($fileFields) || !is_array($fileFields)) {
                continue;
            }
            $assetGroupIds[$asset->getGroupId()] = $asset->getGroupId();
            $asset->setData('requested_files', $fileFields);
        }

        $this->searchCriteriaBuilder->addFilter(GroupInterface::FIELD_GROUP_ID, $assetGroupIds, 'in');
        $groupResult = $this->groupRepository->getList($this->searchCriteriaBuilder->create());
        foreach ($groupResult->getItems() as $group) {
            if (!$this->groupValidator->isAssetGroupAllowedForCustomer($group)) {
                throw new InvalidRequestException('Not allowed to download assets and files');
            }
        }

        $archiveName = md5(json_encode($request->getData())) . '.' . self::ARCHIVE_EXTENSION;
        $zipArchivePath = $this->archiveManager->createArchive($archiveName, $assetResult->getItems());

        $jsonResult = $this->jsonFactory->create();
        $jsonResult->setData([
            'token' => pathinfo($zipArchivePath, PATHINFO_BASENAME)
        ]);

        return $jsonResult;
    }
}
