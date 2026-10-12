<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Api\Data;

/**
 * One sub-district that uses a given postal code.
 *
 * @api
 */
interface AddressMatchInterface
{
    public const POSTCODE = 'postcode';
    public const PROVINCE_CODE = 'province_code';
    public const PROVINCE_NAME_TH = 'province_name_th';
    public const PROVINCE_NAME_EN = 'province_name_en';
    public const DISTRICT = 'district';
    public const SUBDISTRICT = 'subdistrict';
    public const REGION_ID = 'region_id';

    /**
     * Postal code.
     *
     * @return string
     */
    public function getPostcode(): string;

    /**
     * Sets the postal code.
     *
     * @param string $postcode
     * @return $this
     */
    public function setPostcode(string $postcode): self;

    /**
     * Official province code (10 = Bangkok).
     *
     * @return int
     */
    public function getProvinceCode(): int;

    /**
     * Sets the official province code.
     *
     * @param int $provinceCode
     * @return $this
     */
    public function setProvinceCode(int $provinceCode): self;

    /**
     * Province name in Thai.
     *
     * @return string
     */
    public function getProvinceNameTh(): string;

    /**
     * Sets the province name in Thai.
     *
     * @param string $name
     * @return $this
     */
    public function setProvinceNameTh(string $name): self;

    /**
     * Province name in English.
     *
     * @return string
     */
    public function getProvinceNameEn(): string;

    /**
     * Sets the province name in English.
     *
     * @param string $name
     * @return $this
     */
    public function setProvinceNameEn(string $name): self;

    /**
     * District (amphoe / khet), in Thai.
     *
     * @return string
     */
    public function getDistrict(): string;

    /**
     * Sets the district.
     *
     * @param string $district
     * @return $this
     */
    public function setDistrict(string $district): self;

    /**
     * Sub-district (tambon / khwaeng), in Thai.
     *
     * @return string
     */
    public function getSubdistrict(): string;

    /**
     * Sets the sub-district.
     *
     * @param string $subdistrict
     * @return $this
     */
    public function setSubdistrict(string $subdistrict): self;

    /**
     * Magento region id of the province, when the regions have been installed.
     *
     * @return int|null
     */
    public function getRegionId(): ?int;

    /**
     * Sets the Magento region id.
     *
     * @param int|null $regionId
     * @return $this
     */
    public function setRegionId(?int $regionId): self;
}
