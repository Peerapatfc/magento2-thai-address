<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Api\Data;

/**
 * A province, district or sub-district, for filling a drop-down.
 *
 * @api
 */
interface AreaInterface
{
    public const CODE = 'code';
    public const NAME_TH = 'name_th';
    public const NAME_EN = 'name_en';
    public const POSTCODE = 'postcode';
    public const REGION_ID = 'region_id';

    /**
     * Official code: 2 digits for a province, 4 for a district, 6 for a sub-district.
     *
     * @return int
     */
    public function getCode(): int;

    /**
     * Sets the official code.
     *
     * @param int $code
     * @return $this
     */
    public function setCode(int $code): self;

    /**
     * Name in Thai.
     *
     * @return string
     */
    public function getNameTh(): string;

    /**
     * Sets the Thai name.
     *
     * @param string $name
     * @return $this
     */
    public function setNameTh(string $name): self;

    /**
     * Name in English.
     *
     * @return string
     */
    public function getNameEn(): string;

    /**
     * Sets the English name.
     *
     * @param string $name
     * @return $this
     */
    public function setNameEn(string $name): self;

    /**
     * Postal code. Only set for a sub-district.
     *
     * @return string|null
     */
    public function getPostcode(): ?string;

    /**
     * Sets the postal code.
     *
     * @param string|null $postcode
     * @return $this
     */
    public function setPostcode(?string $postcode): self;

    /**
     * Magento region id. Only set for a province, once the regions are installed.
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
