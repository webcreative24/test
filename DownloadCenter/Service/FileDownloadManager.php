<?php
declare(strict_types=1);

namespace OracDecor\DownloadCenter\Service;

use Magento\Framework\App\ResponseInterface;
use OracDecor\Akeneo\Configuration\AkeneoConfiguration;
use OracDecor\Assets\Api\AssetRepositoryInterface;
use OracDecor\Assets\Api\GroupRepositoryInterface;
use OracDecor\Assets\Model\Asset;
use OracDecor\Base\App\Response\StreamResponseFactory;
use OracDecor\DownloadCenter\Api\DownloadManagerInterface;
use OracDecor\DownloadCenter\Exception\InvalidRequestException;
use OracDecor\DownloadCenter\Model\DownloadRequest;
use OracDecor\DownloadCenter\Service\Validator\GroupValidator;

class FileDownloadManager implements DownloadManagerInterface
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
     * @var AkeneoConfiguration
     */
    private $akeneoConfiguration;
    /**
     * @var StreamResponseFactory
     */
    private $streamResponseFactory;

    public function __construct(
        AssetRepositoryInterface $assetRepository,
        GroupRepositoryInterface $groupRepository,
        GroupValidator $groupValidator,
        AkeneoConfiguration $configuration,
        StreamResponseFactory $streamResponseFactory
    ) {
        $this->assetRepository = $assetRepository;
        $this->groupRepository = $groupRepository;
        $this->groupValidator = $groupValidator;
        $this->akeneoConfiguration = $configuration;
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
        $fileField = $request->getData('file_field');

        $asset = $this->assetRepository->getByReference($assetReference);
        if (!$asset->isActive()) {
            throw new InvalidRequestException(sprintf('Asset %s is inactive', $assetReference));
        }
        if (!$asset->getData($fileField)) {
            throw new InvalidRequestException(sprintf('The requested file %s of asset %s does not exist', $fileField, $asset));
        }

        $group = $this->groupRepository->getById($asset->getGroupId());
        if (!$this->groupValidator->isAssetGroupAllowedForCustomer($group)) {
            throw new InvalidRequestException('Not allowed to download asset ' . $assetReference);
        }

        $basePath = ($asset->isDownloadable()) ? $this->akeneoConfiguration->getPrivatePath() : $this->akeneoConfiguration->getPublicPath();

        return $this->streamResponseFactory->create(
            $basePath . DIRECTORY_SEPARATOR . $asset->getData($fileField),
            pathinfo($asset->getData($fileField), PATHINFO_BASENAME)
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

        if (!$request->hasData('file_field')) {
            throw new InvalidRequestException('The file_field is required');
        }

        if (!in_array($request->getData('file_field'), Asset::$filesFields)) {
            throw new InvalidRequestException('Invalid file_field provided');
        }
    }
}
