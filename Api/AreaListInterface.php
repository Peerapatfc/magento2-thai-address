<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Api;

/**
 * Lists the provinces, the districts of a province and the sub-districts of a district, sorted in Thai
 * alphabetical order, to fill drop-downs.
 *
 * @api
 */
interface AreaListInterface
{
    /**
     * All 77 provinces, with the Magento region id once the regions are installed.
     *
     * @return \Peerapatfc\ThaiAddress\Api\Data\AreaInterface[]
     */
    public function getProvinces(): array;

    /**
     * The districts of a province. Empty when the province does not exist.
     *
     * @param int $provinceCode Official province code (10 = Bangkok)
     * @return \Peerapatfc\ThaiAddress\Api\Data\AreaInterface[]
     */
    public function getDistricts(int $provinceCode): array;

    /**
     * The sub-districts of a district, each with its postal code. Empty when the district does not exist.
     *
     * @param int $districtCode Official district code (1004 = Bang Rak)
     * @return \Peerapatfc\ThaiAddress\Api\Data\AreaInterface[]
     */
    public function getSubdistricts(int $districtCode): array;
}
