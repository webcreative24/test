<?php
declare(strict_types=1);

namespace OracDecor\DownloadCenter\Test\Unit\Service\Validator;

use OracDecor\Assets\Api\Data\GroupInterface;
use OracDecor\DownloadCenter\Service\Validator\GroupValidator;
use PHPUnit\Framework\TestCase;

class GroupValidatorTest extends TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    private $customerSession;

    /**
     * @var GroupValidator
     */
    private $groupValidator;

    public function setUp()
    {
        $this->customerSession = $this->createMock(\Magento\Customer\Model\Session::class);

        $this->groupValidator = new GroupValidator(
            $this->customerSession
        );
    }

    public function testIsAssetGroupAllowedForCustomerGroupWithoutCustomerGroups()
    {
        $group = $this->createMock(GroupInterface::class);
        $group->expects($this->once())->method('getCustomerGroups')->willReturn([]);

        $this->assertTrue($this->groupValidator->isAssetGroupAllowedForCustomer($group));
    }

    public function testIsAssetGroupAllowedForCustomerGroupWithCustomerGroupsPass()
    {
        $group = $this->createMock(GroupInterface::class);
        $group->expects($this->once())->method('getCustomerGroups')->willReturn([1,3]);
        $this->customerSession->expects($this->once())->method('getCustomerGroupId')->willReturn(1);

        $this->assertTrue($this->groupValidator->isAssetGroupAllowedForCustomer($group));
    }

    public function testIsAssetGroupAllowedForCustomerGroupWithCustomerGroupsFail()
    {
        $group = $this->createMock(GroupInterface::class);
        $group->expects($this->once())->method('getCustomerGroups')->willReturn([1,3]);
        $this->customerSession->expects($this->once())->method('getCustomerGroupId')->willReturn(2);

        $this->assertFalse($this->groupValidator->isAssetGroupAllowedForCustomer($group));
    }
}
