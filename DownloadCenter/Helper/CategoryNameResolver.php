<?php
declare(strict_types=1);

namespace OracDecor\DownloadCenter\Helper;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use OracDecor\Assets\Api\Data\GroupInterface;
use Pimgento\Entities\Model\ResourceModel\Entities\Collection;
use Pimgento\Entities\Model\ResourceModel\Entities\CollectionFactory as PimgentoCollectionFactory;
use Psr\Log\LoggerInterface;

class CategoryNameResolver
{
    /**
     * @var CategoryRepositoryInterface
     */
    private $categoryRepository;
    /**
     * @var AttributeRepositoryInterface
     */
    private $attributeRepository;
    /**
     * @var PimgentoCollectionFactory
     */
    private $pimgentoEntitiesFactory;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        CategoryRepositoryInterface $categoryRepository,
        AttributeRepositoryInterface $attributeRepository,
        PimgentoCollectionFactory $pimgentoEntitiesFactory,
        LoggerInterface $logger
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->attributeRepository = $attributeRepository;
        $this->pimgentoEntitiesFactory = $pimgentoEntitiesFactory;
        $this->logger = $logger;
    }

    public function resolveCategory1(string $groupReference, string $category)
    {
        try {
            if ($groupReference === GroupInterface::GROUP_INSPIRATION_IMAGES) {
                return $this->getAttributeOptionLabelByKey(
                    'inspirational_image_style',
                    $category
                );
            }

            if ($groupReference === GroupInterface::GROUP_TECHNICAL_DRAWINGS) {
                $categoryModel = $this->categoryRepository->get((int)$category);

                return $categoryModel->getName();
            }
        } catch (\Exception $e) {
            $this->logger->error('Resolving category (1) "'. $category .'" for group "'. $groupReference .'" failed.', ['exception' => $e]);
        }

        return __($category);
    }

    public function resolveCategory2(string $groupReference, string $category)
    {
        try {
            if ($groupReference === GroupInterface::GROUP_TECHNICAL_DRAWINGS) {
                return $this->getAttributeOptionLabelById('application_specific', (int)$category);
            }
        } catch (\Exception $e) {
            $this->logger->error('Resolving category (2) "'. $category .'" for group "'. $groupReference .'" failed.', ['exception' => $e]);
        }

        return __($category);
    }

    /**
     * @param string $attributeKey
     * @param string $optionKey
     * @return string
     * @throws \Exception
     */
    private function getAttributeOptionLabelByKey(string $attributeKey, string $optionKey): string
    {
        /** @var Collection $pimgentoEntitiesCollection */
        $pimgentoEntitiesCollection = $this->pimgentoEntitiesFactory->create();
        $pimgentoEntitiesCollection->addFieldToFilter('import', ['eq' => 'option']);
        $pimgentoEntitiesCollection->addFieldToFilter('code', ['eq' => $optionKey]);
        $optionEntity = $pimgentoEntitiesCollection->getFirstItem();

        if (!$optionEntity->getData('id')) {
            throw new \Exception('No matching option found.');
        }

        return $this->getAttributeOptionLabelById($attributeKey, (int)$optionEntity->getData('entity_id'));
    }

    private function getAttributeOptionLabelById(string $attributeKey, int $optionId): string
    {
        $attribute = $this->attributeRepository->get(Product::ENTITY, $attributeKey);

        if (!$attribute || !$attribute->getSource()) {
            throw new \Exception('Attribute not found.');
        }

        $optionValue = $attribute->getSource()->getOptionText($optionId);
        if ($optionValue === false) {
            throw new \Exception('Option value of attribute not found.');
        }

        return (string)$optionValue;
    }
}
