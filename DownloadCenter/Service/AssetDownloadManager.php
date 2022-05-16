<?php
declare(strict_types=1);

namespace OracDecor\DownloadCenter\Service;

use Magento\Framework\App\ResponseInterface;
use OracDecor\Assets\Api\AssetRepositoryInterface;
use OracDecor\Assets\Api\GroupRepositoryInterface;
use OracDecor\Base\App\Response\StreamResponseFactory;
use OracDecor\DownloadCenter\Api\DownloadManagerInterface;
use OracDecor\DownloadCenter\Exception\InvalidRequestException;
use OracDecor\DownloadCenter\Model\DownloadRequest;
use OracDecor\DownloadCenter\Service\Validator\GroupValidator;

class AssetDownloadManager implements DownloadManagerInterface
{
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
        AssetRepositoryInterface $assetRepository,
        GroupRepositoryInterface $groupRepository,
        GroupValidator $groupValidator,
        ArchiveManager $archiveManager,
        StreamResponseFactory $streamResponseFactory
    ) {
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
        $assetReference = $request->getData('asset_reference');

        $asset = $this->assetRepository->getByReference($assetReference);
        if (!$asset->isActive()) {
            throw new InvalidRequestException(sprintf('Asset %s is inactive', $assetReference));
        }

        $group = $this->groupRepository->getById($asset->getGroupId());
        if (!$this->groupValidator->isAssetGroupAllowedForCustomer($group)) {
            throw new InvalidRequestException('Not allowed to download asset ' . $assetReference);
        }

        $archiveName = md5($asset->getReference() . $asset->getAssetId()) . '.' . self::ARCHIVE_EXTENSION;
        $zipArchivePath = $this->archiveManager->createArchive($archiveName, [$asset]);

        return $this->streamResponseFactory->create(
            $zipArchivePath,
            sprintf('%s.%s', $asset->getReference(), self::ARCHIVE_EXTENSION)
        );
    }

    /**
     * @param DownloadRequest $request
     * @throws InvalidRequestException
     */
    private function guardValidRequest(DownloadRequest $request)
    {
        if (!$request->hasData('asset_reference')) {
            throw new InvalidRequestException('The asset_reference is required');
        }
    }
}
