<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Model;

use Magento\Framework\Phrase;

/**
 * Checks a Thai postal code and, depending on the configured level, that the province, district and sub-district
 * agree with it.
 */
class AddressValidator
{
    /**
     * @param Dataset $dataset
     * @param ProvinceMatcher $provinceMatcher
     * @param AreaNameMatcher $areaMatcher
     * @param RegionResolver $regionResolver
     * @param Config $config
     */
    public function __construct(
        private readonly Dataset $dataset,
        private readonly ProvinceMatcher $provinceMatcher,
        private readonly AreaNameMatcher $areaMatcher,
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
     * @param string|null $district Free text district (the City field), checked at the "address" level
     * @param string|null $subdistrict Free text sub-district (the second street line), checked at the "address" level
     * @param int|string|null $storeId
     * @return Phrase|null
     */
    public function validate(
        ?string $postcode,
        int|string|null $regionId = null,
        ?string $regionName = null,
        ?string $district = null,
        ?string $subdistrict = null,
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

        $level = $this->config->getValidationLevel($storeId);
        if ($level === Config::LEVEL_POSTCODE) {
            return null;
        }

        $chosen = $this->chosenProvinceCode($regionId, $regionName);
        if ($chosen === null && trim((string)$regionName) === '') {
            return null; // Nothing to compare against.
        }

        $expected = array_values(array_unique(array_column($rows, 'province_code')));
        if ($chosen === null || !in_array($chosen, $expected, true)) {
            $names = array_map(fn (int $code): string => $this->dataset->getProvinces()[$code]['th'], $expected);

            return __(
                'Postal code %1 belongs to %2, not "%3".',
                $postcode,
                implode(', ', $names),
                trim((string)$regionName) !== '' ? trim((string)$regionName) : (string)$regionId
            );
        }

        if ($level !== Config::LEVEL_ADDRESS) {
            return null;
        }

        $rows = array_values(array_filter($rows, fn (array $row): bool => $row['province_code'] === $chosen));

        return $this->validateAreas($postcode, $rows, trim((string)$district), trim((string)$subdistrict));
    }

    /**
     * Checks district and sub-district against the rows of the postal code. An empty value is skipped.
     *
     * @param string $postcode
     * @param array $rows Dataset rows of the postal code, already limited to the chosen province
     * @param string $district
     * @param string $subdistrict
     * @return Phrase|null
     */
    private function validateAreas(string $postcode, array $rows, string $district, string $subdistrict): ?Phrase
    {
        if ($district !== '') {
            $rows = array_values(array_filter(
                $rows,
                fn (array $row): bool => $this->areaMatcher->matches($district, $row['district'], $row['district_en'])
            ));
            if ($rows === []) {
                return __('District "%1" is not in postal code %2.', $district, $postcode);
            }
        }

        if ($subdistrict !== '') {
            foreach ($rows as $row) {
                if ($this->areaMatcher->matches($subdistrict, $row['subdistrict'], $row['subdistrict_en'])) {
                    return null;
                }
            }

            return __('Sub-district "%1" is not in postal code %2.', $subdistrict, $postcode);
        }

        return null;
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
