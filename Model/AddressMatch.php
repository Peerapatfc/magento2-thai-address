<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Model;

use Magento\Framework\DataObject;
use Peerapatfc\ThaiAddress\Api\Data\AddressMatchInterface;

class AddressMatch extends DataObject implements AddressMatchInterface
{
    /**
     * @inheritdoc
     */
    public function getPostcode(): string
    {
        return (string)$this->getData(self::POSTCODE);
    }

    /**
     * @inheritdoc
     */
    public function setPostcode(string $postcode): AddressMatchInterface
    {
        return $this->setData(self::POSTCODE, $postcode);
    }

    /**
     * @inheritdoc
     */
    public function getProvinceCode(): int
    {
        return (int)$this->getData(self::PROVINCE_CODE);
    }

    /**
     * @inheritdoc
     */
    public function setProvinceCode(int $provinceCode): AddressMatchInterface
    {
        return $this->setData(self::PROVINCE_CODE, $provinceCode);
    }

    /**
     * @inheritdoc
     */
    public function getProvinceNameTh(): string
    {
        return (string)$this->getData(self::PROVINCE_NAME_TH);
    }

    /**
     * @inheritdoc
     */
    public function setProvinceNameTh(string $name): AddressMatchInterface
    {
        return $this->setData(self::PROVINCE_NAME_TH, $name);
    }

    /**
     * @inheritdoc
     */
    public function getProvinceNameEn(): string
    {
        return (string)$this->getData(self::PROVINCE_NAME_EN);
    }

    /**
     * @inheritdoc
     */
    public function setProvinceNameEn(string $name): AddressMatchInterface
    {
        return $this->setData(self::PROVINCE_NAME_EN, $name);
    }

    /**
     * @inheritdoc
     */
    public function getDistrict(): string
    {
        return (string)$this->getData(self::DISTRICT);
    }

    /**
     * @inheritdoc
     */
    public function setDistrict(string $district): AddressMatchInterface
    {
        return $this->setData(self::DISTRICT, $district);
    }

    /**
     * @inheritdoc
     */
    public function getSubdistrict(): string
    {
        return (string)$this->getData(self::SUBDISTRICT);
    }

    /**
     * @inheritdoc
     */
    public function setSubdistrict(string $subdistrict): AddressMatchInterface
    {
        return $this->setData(self::SUBDISTRICT, $subdistrict);
    }

    /**
     * @inheritdoc
     */
    public function getRegionId(): ?int
    {
        $id = $this->getData(self::REGION_ID);

        return $id === null ? null : (int)$id;
    }

    /**
     * @inheritdoc
     */
    public function setRegionId(?int $regionId): AddressMatchInterface
    {
        return $this->setData(self::REGION_ID, $regionId);
    }
}
