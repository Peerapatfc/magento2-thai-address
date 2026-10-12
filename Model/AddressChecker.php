<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Model;

use Magento\Customer\Api\Data\AddressInterface as CustomerAddressInterface;
use Magento\Framework\Phrase;

/**
 * Checks an address of any kind (quote, customer address book) the way the checkout does: only Thai addresses, only
 * when the validation is switched on.
 */
class AddressChecker
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
     * Returns why the address is rejected, or null when it is acceptable or not for this module to judge.
     *
     * @param string|null $countryId
     * @param string|null $postcode
     * @param int|string|null $regionId
     * @param string|null $region
     * @param string|null $city The district
     * @param string[] $street The second line holds the sub-district when the store has two street lines or more
     * @return Phrase|null
     */
    public function check(
        ?string $countryId,
        ?string $postcode,
        int|string|null $regionId,
        ?string $region,
        ?string $city,
        array $street = []
    ): ?Phrase {
        if ($countryId !== Dataset::COUNTRY_ID || !$this->config->isValidationEnabled()) {
            return null;
        }

        return $this->validator->validate(
            $postcode,
            $regionId,
            $region,
            $city,
            // A store with one street line can still receive a leftover second line from a saved address.
            $this->config->getStreetLines() >= 2 ? ($street[1] ?? null) : null
        );
    }

    /**
     * The same check for an address of the customer address book.
     *
     * @param CustomerAddressInterface $address
     * @return Phrase|null
     */
    public function checkCustomerAddress(CustomerAddressInterface $address): ?Phrase
    {
        $region = $address->getRegion();

        return $this->check(
            $address->getCountryId(),
            $address->getPostcode(),
            $region?->getRegionId(),
            $region?->getRegion(),
            $address->getCity(),
            $address->getStreet() ?? []
        );
    }
}
