<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Test\Unit\Plugin;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Api\ShippingInformationManagementInterface;
use Magento\Framework\Exception\InputException;
use Magento\Quote\Api\Data\AddressInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Peerapatfc\ThaiAddress\Model\AddressValidator;
use Peerapatfc\ThaiAddress\Model\Config;
use Peerapatfc\ThaiAddress\Plugin\ValidateThaiAddress;

class ValidateThaiAddressTest extends TestCase
{
    private AddressValidator&MockObject $validator;
    private Config&MockObject $config;
    private ValidateThaiAddress $plugin;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(AddressValidator::class);
        $this->config = $this->createMock(Config::class);
        $this->config->method('isValidationEnabled')->willReturn(true);
        $this->plugin = new ValidateThaiAddress($this->validator, $this->config);
    }

    private function address(string $country, string $postcode = '10110'): AddressInterface&MockObject
    {
        $address = $this->createMock(AddressInterface::class);
        $address->method('getCountryId')->willReturn($country);
        $address->method('getPostcode')->willReturn($postcode);
        $address->method('getRegionId')->willReturn(700);
        $address->method('getRegion')->willReturn('Bangkok');

        return $address;
    }

    private function information(AddressInterface $shipping, ?AddressInterface $billing = null): ShippingInformationInterface
    {
        $info = $this->createMock(ShippingInformationInterface::class);
        $info->method('getShippingAddress')->willReturn($shipping);
        $info->method('getBillingAddress')->willReturn($billing);

        return $info;
    }

    public function testAValidThaiAddressPasses(): void
    {
        $this->validator->expects($this->once())->method('validate')->with('10110', 700, 'Bangkok')->willReturn(null);

        $result = $this->plugin->beforeSaveAddressInformation(
            $this->createMock(ShippingInformationManagementInterface::class),
            '1',
            $this->information($this->address('TH'))
        );

        $this->assertNull($result);
    }

    public function testARejectedThaiAddressStopsTheCheckoutWithTheReason(): void
    {
        $this->validator->method('validate')->willReturn(__('Postal code %1 does not exist in Thailand.', '00000'));

        $this->expectException(InputException::class);
        $this->expectExceptionMessage('Postal code 00000 does not exist in Thailand.');

        $this->plugin->beforeSaveAddressInformation(
            $this->createMock(ShippingInformationManagementInterface::class),
            '1',
            $this->information($this->address('TH', '00000'))
        );
    }

    public function testAddressesOutsideThailandAreNotChecked(): void
    {
        $this->validator->expects($this->never())->method('validate');

        $this->plugin->beforeSaveAddressInformation(
            $this->createMock(ShippingInformationManagementInterface::class),
            '1',
            $this->information($this->address('US', '90210'), $this->address('SG', '018956'))
        );
    }

    public function testTheBillingAddressIsCheckedToo(): void
    {
        $this->validator->expects($this->exactly(2))->method('validate')->willReturn(null);

        $this->plugin->beforeSaveAddressInformation(
            $this->createMock(ShippingInformationManagementInterface::class),
            '1',
            $this->information($this->address('TH'), $this->address('TH', '50200'))
        );
    }

    public function testNothingIsCheckedWhenValidationIsSwitchedOff(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('isValidationEnabled')->willReturn(false);
        $this->validator->expects($this->never())->method('validate');
        $plugin = new ValidateThaiAddress($this->validator, $config);

        $plugin->beforeSaveAddressInformation(
            $this->createMock(ShippingInformationManagementInterface::class),
            '1',
            $this->information($this->address('TH', '00000'))
        );
    }
}
