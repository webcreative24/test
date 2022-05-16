<?php
declare(strict_types=1);

namespace OracDecor\DownloadCenter\Service\Validator;

use Magento\Customer\Model\Session as CustomerSession;
use OracDecor\Assets\Api\Data\GroupInterface;

class GroupValidator
{
    /**
     * @var CustomerSession
     */
    private $customerSession;

    public function __construct(
        CustomerSession $customerSession
    ) {
        $this->customerSession = $customerSession;
    }

    public function isAssetGroupAllowedForCustomer(GroupInterface $group): bool
    {
        $allowedCustomerGroups = $group->getCustomerGroups();
        if (empty($allowedCustomerGroups)) {
            return true;
        }

        return in_array($this->customerSession->getCustomerGroupId(), $allowedCustomerGroups);
    }
}
