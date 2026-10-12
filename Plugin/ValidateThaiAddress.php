<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Plugin;

use Magento\Checkout\Api\Data\ShippingInformationInterface;
use Magento\Checkout\Api\GuestShippingInformationManagementInterface;
use Magento\Checkout\Api\ShippingInformationManagementInterface;
use Magento\Framework\Exception\InputException;
use Magento\Quote\Api\Data\AddressInterface;
use Peerapatfc\ThaiAddress\Model\AddressValidator;
use Peerapatfc\ThaiAddress\Model\Config;
use Peerapatfc\ThaiAddress\Model\Dataset;

/**
 * Rejects a Thai shipping or billing address with an unknown postal code or a province that does not
 * match it, before the checkout saves it. Works for logged-in customers and guests.
 */
class ValidateThaiAddress
{
    /**
     * @param AddressValidator $validator
     * @param Config $config
     */
    public function __construct(
        private readonly AddressValidator $validator,
        private readonly Config $config
    ) {
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
        if ($address === null || $address->getCountryId() !== Dataset::COUNTRY_ID) {
            return;
        }
        if (!$this->config->isValidationEnabled()) {
            return;
        }

        $error = $this->validator->validate(
            $address->getPostcode(),
            $address->getRegionId(),
            $address->getRegion(),
            $address->getCity(),
            // The sub-district lives in the second street line, which only exists with 2 or more street lines;
            // a store with one line can still receive a leftover second line from a saved address.
            $this->config->getStreetLines() >= 2 ? ($address->getStreet()[1] ?? null) : null
        );
        if ($error !== null) {
            throw new InputException($error);
        }
    }
}
