<?php
declare(strict_types=1);

namespace OracDecor\DownloadCenter\Service;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\ResponseInterface;
use OracDecor\Assets\Api\AssetRepositoryInterface;
use OracDecor\Assets\Api\Data\AssetInterface;
use OracDecor\Assets\Api\GroupRepositoryInterface;
use OracDecor\Base\App\Response\StreamResponseFactory;
use OracDecor\DownloadCenter\Api\DownloadManagerInterface;
use OracDecor\DownloadCenter\Exception\InvalidRequestException;
use OracDecor\DownloadCenter\Model\DownloadRequest;
use OracDecor\DownloadCenter\Service\Validator\GroupValidator;

class GroupDownloadManager implements DownloadManagerInterface
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
     * @var StreamResponseFactory
     */
    private $streamResponseFactory;

    public function __construct(
        SearchCriteriaBuilder $searchCriteriaBuilder,
        AssetRepositoryInterface $assetRepository,
        GroupRepositoryInterface $groupRepository,
        GroupValidator $groupValidator,
        ArchiveManager $archiveManager,
        StreamResponseFactory $streamResponseFactory
    ) {
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->assetRepository = $assetRepository;
        $this->groupRepository = $groupRepository;
        $this->groupValidator = $groupValidator;
        $this->archiveManager = $archiveManager;
        $this->streamResponseFactory = $streamResponseFactory;
    }

    /**
     * @param DownloadRequest $request
     * @return ResponseInterface
     * @throws \Exception
     */
    public function createDownload(DownloadRequest $request)
    {
        $this->guardValidRequest($request);
        $groupReference = $request->getData('group_reference');

        $group = $this->groupRepository->getByReference($groupReference);
        if (!$this->groupValidator->isAssetGroupAllowedForCustomer($group)) {
            throw new InvalidRequestException('Not allowed to download group ' . $groupReference);
        }

        $this->searchCriteriaBuilder->addFilter(AssetInterface::FIELD_GROUP_ID, $group->getGroupId(), 'eq');
        $this->searchCriteriaBuilder->addFilter('main_table.' . AssetInterface::FIELD_ACTIVE, 1);
        $assetResult = $this->assetRepository->getList($this->searchCriteriaBuilder->create());
        if ($assetResult->getTotalCount() <= 0) {
            throw new InvalidRequestException('Invalid download request: no assets found');
        }

        $archiveName = md5($group->getReference() . $group->getGroupId()) . '.' . self::ARCHIVE_EXTENSION;
        $zipArchivePath = $this->archiveManager->createArchive($archiveName, $assetResult->getItems());

        return $this->streamResponseFactory->create(
            $zipArchivePath,
            sprintf('%s.%s', $group->getReference(), self::ARCHIVE_EXTENSION)
        );
    }

    /**
     * @param DownloadRequest $request
     * @throws InvalidRequestException
     */
    private function guardValidRequest(DownloadRequest $request)
    {
        if (!$request->hasData('group_reference')) {
            throw new InvalidRequestException('The group_reference is required');
        }
    }
}
