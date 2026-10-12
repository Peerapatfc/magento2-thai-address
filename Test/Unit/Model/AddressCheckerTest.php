<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Test\Unit\Model;

use Magento\Customer\Api\Data\AddressInterface as CustomerAddressInterface;
use Magento\Customer\Api\Data\RegionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Peerapatfc\ThaiAddress\Model\AddressChecker;
use Peerapatfc\ThaiAddress\Model\AddressValidator;
use Peerapatfc\ThaiAddress\Model\Config;

class AddressCheckerTest extends TestCase
{
    private AddressValidator&MockObject $validator;
    private Config&MockObject $config;

    private function checker(bool $enabled = true, int $streetLines = 2): AddressChecker
    {
        $this->validator = $this->createMock(AddressValidator::class);
        $this->config = $this->createMock(Config::class);
        $this->config->method('isValidationEnabled')->willReturn($enabled);
        $this->config->method('getStreetLines')->willReturn($streetLines);

        return new AddressChecker($this->validator, $this->config);
    }

    public function testAThaiAddressGoesToTheValidatorWithTheSubdistrictFromTheSecondLine(): void
    {
        $checker = $this->checker();
        $reason = __('Postal code %1 does not exist in Thailand.', '00000');
        $this->validator->expects($this->once())->method('validate')
            ->with('00000', 700, 'Bangkok', 'บางรัก', 'สีลม')->willReturn($reason);

        $this->assertSame($reason, $checker->check('TH', '00000', 700, 'Bangkok', 'บางรัก', ['1 Rd', 'สีลม']));
    }

    public function testAddressesOutsideThailandAreNotChecked(): void
    {
        $checker = $this->checker();
        $this->validator->expects($this->never())->method('validate');

        $this->assertNull($checker->check('US', '90210', null, 'California', 'Beverly Hills', []));
    }

    public function testNothingIsCheckedWhenValidationIsSwitchedOff(): void
    {
        $checker = $this->checker(false);
        $this->validator->expects($this->never())->method('validate');

        $this->assertNull($checker->check('TH', '00000', 700, 'Bangkok', 'บางรัก', []));
    }

    public function testWithOneStreetLineALeftoverSecondLineIsNotChecked(): void
    {
        $checker = $this->checker(true, 1);
        $this->validator->expects($this->once())->method('validate')
            ->with('10500', 700, 'Bangkok', 'บางรัก', null)->willReturn(null);

        $this->assertNull($checker->check('TH', '10500', 700, 'Bangkok', 'บางรัก', ['1 Rd', 'leftover']));
    }

    public function testACustomerAddressIsCheckedLikeAQuoteAddress(): void
    {
        $checker = $this->checker();
        $region = $this->createMock(RegionInterface::class);
        $region->method('getRegionId')->willReturn(700);
        $region->method('getRegion')->willReturn('Bangkok');
        $address = $this->createMock(CustomerAddressInterface::class);
        $address->method('getCountryId')->willReturn('TH');
        $address->method('getPostcode')->willReturn('10500');
        $address->method('getRegion')->willReturn($region);
        $address->method('getCity')->willReturn('บางรัก');
        $address->method('getStreet')->willReturn(['1 Rd', 'สีลม']);
        $this->validator->expects($this->once())->method('validate')
            ->with('10500', 700, 'Bangkok', 'บางรัก', 'สีลม')->willReturn(null);

        $this->assertNull($checker->checkCustomerAddress($address));
    }

    public function testACustomerAddressWithoutARegionIsStillChecked(): void
    {
        $checker = $this->checker();
        $address = $this->createMock(CustomerAddressInterface::class);
        $address->method('getCountryId')->willReturn('TH');
        $address->method('getPostcode')->willReturn('10500');
        $address->method('getRegion')->willReturn(null);
        $this->validator->expects($this->once())->method('validate')
            ->with('10500', null, null, null, null)->willReturn(null);

        $this->assertNull($checker->checkCustomerAddress($address));
    }
}
