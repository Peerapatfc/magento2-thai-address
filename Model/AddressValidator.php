<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Model;

use Magento\Framework\Phrase;

/**
 * Checks a Thai postal code and, optionally, that the province agrees with it.
 *
 * District and sub-district are deliberately not enforced: customers spell them in too many ways.
 */
class AddressValidator
{
    /**
     * @param Dataset $dataset
     * @param ProvinceMatcher $provinceMatcher
     * @param RegionResolver $regionResolver
     * @param Config $config
     */
    public function __construct(
        private readonly Dataset $dataset,
        private readonly ProvinceMatcher $provinceMatcher,
        private readonly RegionResolver $regionResolver,
        private readonly Config $config
    ) {
    }

    /**
     * Returns why the address is rejected, or null when it is acceptable.
     *
     * @param string|null $postcode
     * @param int|string|null $regionId Magento region id, if the customer picked one from the list
     * @param string|null $regionName Free text province, used when there is no region id
     * @param int|string|null $storeId
     * @return Phrase|null
     */
    public function validate(
        ?string $postcode,
        int|string|null $regionId = null,
        ?string $regionName = null,
        int|string|null $storeId = null
    ): ?Phrase {
        $postcode = trim((string)$postcode);
        if (!preg_match('/^\d{5}$/', $postcode)) {
            return __('The postal code must be exactly 5 digits.');
        }

        $rows = $this->dataset->findByPostcode($postcode);
        if ($rows === []) {
            return __('Postal code %1 does not exist in Thailand.', $postcode);
        }

        if (!$this->config->enforceProvinceMatch($storeId)) {
            return null;
        }

        $chosen = $this->chosenProvinceCode($regionId, $regionName);
        if ($chosen === null && trim((string)$regionName) === '') {
            return null; // Nothing to compare against.
        }

        $expected = array_values(array_unique(array_column($rows, 'province_code')));
        if ($chosen !== null && in_array($chosen, $expected, true)) {
            return null;
        }

        $names = array_map(fn (int $code): string => $this->dataset->getProvinces()[$code]['th'], $expected);

        return __(
            'Postal code %1 belongs to %2, not "%3".',
            $postcode,
            implode(', ', $names),
            trim((string)$regionName) !== '' ? trim((string)$regionName) : (string)$regionId
        );
    }

    /**
     * The province the customer chose, from the region id or else from the free text.
     *
     * @param int|string|null $regionId
     * @param string|null $regionName
     * @return int|null
     */
    private function chosenProvinceCode(int|string|null $regionId, ?string $regionName): ?int
    {
        if ($regionId !== null && (int)$regionId > 0) {
            $code = $this->regionResolver->getProvinceCode((int)$regionId);
            if ($code !== null) {
                return $code;
            }
        }

        $name = trim((string)$regionName);

        return $name === '' ? null : $this->provinceMatcher->findCode($name);
    }
}
