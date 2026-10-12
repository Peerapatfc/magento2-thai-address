<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Plugin\Customer;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Exception\InputException;
use Peerapatfc\ThaiAddress\Model\AddressChecker;

/**
 * Applies the checkout validation to the storefront address book (My Account), so a customer cannot save a Thai
 * address the checkout would then refuse. Registered for the storefront only: the admin and the API are not affected.
 */
class ValidateAddressBook
{
    /**
     * @param AddressChecker $checker
     */
    public function __construct(private readonly AddressChecker $checker)
    {
    }

    /**
     * Rejects a Thai address that does not pass the checkout validation.
     *
     * @param AddressRepositoryInterface $subject
     * @param AddressInterface $address
     * @return null
     * @throws InputException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(AddressRepositoryInterface $subject, AddressInterface $address)
    {
        $error = $this->checker->checkCustomerAddress($address);
        if ($error !== null) {
            throw new InputException($error);
        }

        return null;
    }
}
