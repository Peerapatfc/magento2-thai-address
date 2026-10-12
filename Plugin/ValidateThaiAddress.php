<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Plugin;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Api\GuestShippingInformationManagementInterface;
use Magento\Checkout\Api\ShippingInformationManagementInterface;
use Magento\Framework\Exception\InputException;
use Magento\Quote\Api\Data\AddressInterface;
use Peerapatfc\ThaiAddress\Model\AddressChecker;

/**
 * Rejects a Thai shipping or billing address with an unknown postal code or a province that does not
 * match it, before the checkout saves it. Works for logged-in customers and guests.
 */
class ValidateThaiAddress
{
    /**
     * @param AddressChecker $checker
     */
    public function __construct(private readonly AddressChecker $checker)
    {
    }

    /**
     * Checks the shipping and billing address before the checkout saves them.
     *
     * @param ShippingInformationManagementInterface|GuestShippingInformationManagementInterface $subject
     * @param int|string $cartId
     * @param ShippingInformationInterface $addressInformation
     * @return null
     * @throws InputException
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSaveAddressInformation($subject, $cartId, ShippingInformationInterface $addressInformation)
    {
        foreach ([$addressInformation->getShippingAddress(), $addressInformation->getBillingAddress()] as $address) {
            $this->assertValid($address);
        }

        return null;
    }

    /**
     * Throws when a Thai address is rejected.
     *
     * @param AddressInterface|null $address
     * @return void
     * @throws InputException
     */
    private function assertValid(?AddressInterface $address): void
    {
        if ($address === null) {
            return;
        }

        $error = $this->checker->check(
            $address->getCountryId(),
            $address->getPostcode(),
            $address->getRegionId(),
            $address->getRegion(),
            $address->getCity(),
            $address->getStreet() ?? []
        );
        if ($error !== null) {
            throw new InputException($error);
        }
    }
}
