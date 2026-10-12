<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Model;

use Magento\Framework\DataObject;
use Peerapatfc\ThaiAddress\Api\Data\AreaInterface;

class Area extends DataObject implements AreaInterface
{
    /**
     * @inheritdoc
     */
    public function getCode(): int
    {
        return (int)$this->getData(self::CODE);
    }

    /**
     * @inheritdoc
     */
    public function setCode(int $code): AreaInterface
    {
        return $this->setData(self::CODE, $code);
    }

    /**
     * @inheritdoc
     */
    public function getNameTh(): string
    {
        return (string)$this->getData(self::NAME_TH);
    }

    /**
     * @inheritdoc
     */
    public function setNameTh(string $name): AreaInterface
    {
        return $this->setData(self::NAME_TH, $name);
    }

    /**
     * @inheritdoc
     */
    public function getNameEn(): string
    {
        return (string)$this->getData(self::NAME_EN);
    }

    /**
     * @inheritdoc
     */
    public function setNameEn(string $name): AreaInterface
    {
        return $this->setData(self::NAME_EN, $name);
    }

    /**
     * @inheritdoc
     */
    public function getPostcode(): ?string
    {
        $postcode = $this->getData(self::POSTCODE);

        return $postcode === null ? null : (string)$postcode;
    }

    /**
     * @inheritdoc
     */
    public function setPostcode(?string $postcode): AreaInterface
    {
        return $this->setData(self::POSTCODE, $postcode);
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
    public function setRegionId(?int $regionId): AreaInterface
    {
        return $this->setData(self::REGION_ID, $regionId);
    }
}
