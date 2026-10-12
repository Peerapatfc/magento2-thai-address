<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Test\Unit\Plugin\Customer;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Exception\InputException;
use PHPUnit\Framework\TestCase;
use Peerapatfc\ThaiAddress\Model\AddressChecker;
use Peerapatfc\ThaiAddress\Plugin\Customer\ValidateAddressBook;

class ValidateAddressBookTest extends TestCase
{
    public function testAValidAddressIsSaved(): void
    {
        $checker = $this->createMock(AddressChecker::class);
        $checker->method('checkCustomerAddress')->willReturn(null);

        $result = (new ValidateAddressBook($checker))->beforeSave(
            $this->createMock(AddressRepositoryInterface::class),
            $this->createMock(AddressInterface::class)
        );

        $this->assertNull($result);
    }

    public function testARejectedAddressIsNotSavedAndTheCustomerSeesWhy(): void
    {
        $checker = $this->createMock(AddressChecker::class);
        $checker->method('checkCustomerAddress')->willReturn(__('District "%1" is not in postal code %2.', 'X', '10500'));

        $this->expectException(InputException::class);
        $this->expectExceptionMessage('District "X" is not in postal code 10500.');

        (new ValidateAddressBook($checker))->beforeSave(
            $this->createMock(AddressRepositoryInterface::class),
            $this->createMock(AddressInterface::class)
        );
    }
}
