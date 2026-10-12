<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Test\Unit\Plugin;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Api\ShippingInformationManagementInterface;
use Magento\Framework\Exception\InputException;
use Magento\Quote\Api\Data\AddressInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Peerapatfc\ThaiAddress\Model\AddressChecker;
use Peerapatfc\ThaiAddress\Plugin\ValidateThaiAddress;

class ValidateThaiAddressTest extends TestCase
{
    private AddressChecker&MockObject $checker;
    private ValidateThaiAddress $plugin;

    protected function setUp(): void
    {
        $this->checker = $this->createMock(AddressChecker::class);
        $this->plugin = new ValidateThaiAddress($this->checker);
    }

    private function address(string $country, string $postcode = '10110'): AddressInterface&MockObject
    {
        $address = $this->createMock(AddressInterface::class);
        $address->method('getCountryId')->willReturn($country);
        $address->method('getPostcode')->willReturn($postcode);
        $address->method('getRegionId')->willReturn(700);
        $address->method('getRegion')->willReturn('Bangkok');
        $address->method('getCity')->willReturn('คลองเตย');
        $address->method('getStreet')->willReturn(['1 Rama IV Rd', 'คลองเตย']);

        return $address;
    }

    private function information(AddressInterface $shipping, ?AddressInterface $billing = null): ShippingInformationInterface
    {
        $info = $this->createMock(ShippingInformationInterface::class);
        $info->method('getShippingAddress')->willReturn($shipping);
        $info->method('getBillingAddress')->willReturn($billing);

        return $info;
    }

    public function testTheAddressIsHandedToTheChecker(): void
    {
        $this->checker->expects($this->once())->method('check')
            ->with('TH', '10110', 700, 'Bangkok', 'คลองเตย', ['1 Rama IV Rd', 'คลองเตย'])->willReturn(null);

        $result = $this->plugin->beforeSaveAddressInformation(
            $this->createMock(ShippingInformationManagementInterface::class),
            '1',
            $this->information($this->address('TH'))
        );

        $this->assertNull($result);
    }

    public function testARejectedAddressStopsTheCheckoutWithTheReason(): void
    {
        $this->checker->method('check')->willReturn(__('Postal code %1 does not exist in Thailand.', '00000'));

        $this->expectException(InputException::class);
        $this->expectExceptionMessage('Postal code 00000 does not exist in Thailand.');

        $this->plugin->beforeSaveAddressInformation(
            $this->createMock(ShippingInformationManagementInterface::class),
            '1',
            $this->information($this->address('TH', '00000'))
        );
    }

    public function testTheBillingAddressIsCheckedToo(): void
    {
        $this->checker->expects($this->exactly(2))->method('check')->willReturn(null);

        $this->plugin->beforeSaveAddressInformation(
            $this->createMock(ShippingInformationManagementInterface::class),
            '1',
            $this->information($this->address('TH'), $this->address('TH', '50200'))
        );
    }

    public function testAMissingBillingAddressIsSkipped(): void
    {
        $this->checker->expects($this->once())->method('check')->willReturn(null);

        $this->plugin->beforeSaveAddressInformation(
            $this->createMock(ShippingInformationManagementInterface::class),
            '1',
            $this->information($this->address('TH'), null)
        );
    }
}
