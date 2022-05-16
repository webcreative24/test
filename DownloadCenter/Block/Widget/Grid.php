<?php
declare(strict_types=1);

namespace OracDecor\DownloadCenter\Block\Widget;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Widget\Block\BlockInterface;
use OracDecor\Assets\Api\AssetFilesManagementInterface;
use OracDecor\Assets\Api\AssetManagementInterface;
use OracDecor\Assets\Api\Data\AssetInterface;
use OracDecor\Assets\Api\Data\GroupInterface;
use OracDecor\Assets\Api\GroupRepositoryInterface;
use OracDecor\Assets\Config\AssetConfiguration;
use OracDecor\Assets\Model\Asset;
use OracDecor\DownloadCenter\Helper\CategoryNameResolver;
use Psr\Log\LoggerInterface;

class Grid extends Template implements BlockInterface, IdentityInterface
{
    protected $_template = 'widget/grid.phtml';
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var GroupRepositoryInterface
     */
    private $groupRepository;
    /**
     * @var AssetConfiguration
     */
    private $configuration;
    /**
     * @var AssetManagementInterface
     */
    private $assetManagement;
    /**
     * @var AssetFilesManagementInterface
     */
    private $assetFilesManagement;
    /**
     * @var CategoryNameResolver
     */
    private $categoryNameResolver;
    /**
     * @var GroupInterface
     */
    private $group;
    /**
     * @var AssetInterface[];
     */
    private $assets;
    /**
     * @var array
     */
    private $visibleFileFields = [];
    /**
     * @var
     */
    private $hasError = false;

    public function __construct(
        Context $context,
        LoggerInterface $logger,
        GroupRepositoryInterface $groupRepository,
        AssetConfiguration $configuration,
        AssetManagementInterface $assetManagement,
        AssetFilesManagementInterface $assetFilesManagement,
        CategoryNameResolver $categoryNameResolver,
        array $data
    ) {
        $this->logger = $logger;
        $this->groupRepository = $groupRepository;
        $this->configuration = $configuration;
        $this->assetFilesManagement = $assetFilesManagement;
        $this->assetManagement = $assetManagement;
        $this->categoryNameResolver = $categoryNameResolver;
        parent::__construct($context, $data);
    }

    /**
     * Initialize asset group and related assets
     *
     * @return $this
     */
    public function _prepareLayout()
    {
        $groupReference = (string)$this->getData('asset_group_reference');
        $assetReferencesString = $this->getData('asset_references');
        $assetReferences = ($assetReferencesString) ? array_map('trim', explode(',', $assetReferencesString)) : [];

        try {
            $this->initVisibleFileFields();

            // TODO improvement: check if the customer is allowed to see the widget based on customer groups
            $this->group = $this->groupRepository->getByReference($groupReference);
            $this->assets = $this->assetManagement->getAssetsForGroupAndReferences($this->group, $assetReferences, $this->getExcludeCategoryIds());
        } catch (\Exception $e) {
            $this->logger->error(
                'Cannot render download center widget: ' . $e->getMessage(),
                ['exception' => $e, 'group' => $groupReference, 'assetReferences' => $assetReferences]
            );

            $this->hasError = true;
        }

        return $this;
    }

    public function getGroup(): GroupInterface
    {
        return $this->group;
    }
    /**
     * @return AssetInterface[]
     */
    public function getAssets(): array
    {
        return $this->assets;
    }

    public function getThumbnailPath(AssetInterface $asset)
    {
        return $this->configuration->getThumbnailUrl($asset->getThumbnail());
    }

    public function getThumbnailPlaceholderPath()
    {
        return $this->configuration->getPlaceholderUrl();
    }

    public function getDownloadSelection() :string
    {
        return $this->assetFilesManagement->getDownloadSelectionUrl();
    }

    public function getDownloadGroup() :string
    {
        return $this->assetFilesManagement->getDownloadGroupUrl($this->group);
    }

    public function getDownloadAsset(AssetInterface $asset): string
    {
        return $this->assetFilesManagement->getDownloadAssetUrl($asset);
    }

    public function getDownloadFiles(AssetInterface $asset): array
    {
        $filesMetaInfo = $this->assetFilesManagement->getDownloadAssetFileUrls($asset);

        return array_filter($filesMetaInfo, function ($fileField) {
            return in_array($fileField, $this->visibleFileFields);
        }, ARRAY_FILTER_USE_KEY);
    }

    public function getAvailableFiles(): array
    {
        $fileFields = $this->group->getFileMapping();

        return array_filter($fileFields, function ($fileField) {
            return in_array($fileField, $this->visibleFileFields);
        }, ARRAY_FILTER_USE_KEY);
    }

    public function isAssetDownloadIndividuallyEnabled()
    {
        return (bool)$this->getData('enable_asset_download_individually');
    }

    public function normalizeCategory(string $category): string
    {
        return preg_replace('/[^A-Za-z0-9\-]/', '', $category);
    }

    public function resolveCategory1($categoryCode)
    {
        return $this->categoryNameResolver->resolveCategory1(
            (string)$this->getData('asset_group_reference'),
            $categoryCode
        );
    }

    public function resolveCategory2($categoryCode)
    {
        return $this->categoryNameResolver->resolveCategory2(
            (string)$this->getData('asset_group_reference'),
            $categoryCode
        );
    }

    /**
     * @inheritdoc
     */
    public function _toHtml()
    {
        if ($this->hasError) {
            return '';
        }

        return parent::_toHtml();
    }

    private function getExcludeCategoryIds(): array
    {
        $categoryIdsRaw = trim((string)$this->getData('exclude_category_ids'));
        if (empty($categoryIdsRaw)) {
            return [];
        }

        return explode(',', $categoryIdsRaw);
    }

    private function initVisibleFileFields()
    {
        foreach (Asset::$filesFields as $fileField) {
            if (!(bool)$this->getData('show_' . $fileField)) {
                continue;
            }
            $this->visibleFileFields[$fileField] = $fileField;
        }
    }

    /**
     * @inheritdoc
     */
    public function getIdentities()
    {
        $identities = $this->group->getIdentities();
        foreach ($this->assets as $asset) {
            $identities = array_merge($identities, $asset->getIdentities());
        }

        return $identities;
    }
}
