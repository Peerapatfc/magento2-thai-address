<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Model\Resolver;

use Magento\Framework\Exception\InputException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Peerapatfc\ThaiAddress\Api\AddressLookupInterface;

class ThaiAddress implements ResolverInterface
{
    /**
     * @param AddressLookupInterface $lookup
     */
    public function __construct(private readonly AddressLookupInterface $lookup)
    {
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, ?array $value = null, ?array $args = null)
    {
        try {
            $matches = $this->lookup->getByPostcode((string)($args['postcode'] ?? ''));
        } catch (InputException $e) {
            throw new GraphQlInputException(__($e->getMessage()), $e);
        }

        return array_map(
            static fn ($match): array => [
                'postcode' => $match->getPostcode(),
                'province_code' => $match->getProvinceCode(),
                'province_name_th' => $match->getProvinceNameTh(),
                'province_name_en' => $match->getProvinceNameEn(),
                'district' => $match->getDistrict(),
                'subdistrict' => $match->getSubdistrict(),
                'region_id' => $match->getRegionId(),
            ],
            $matches
        );
    }
}
