<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Api;

use Peerapatfc\ThaiAddress\Api\Data\AddressMatchInterface;

/**
 * Looks up Thai addresses by postal code.
 *
 * @api
 */
interface AddressLookupInterface
{
    /**
     * Returns every sub-district that uses the postal code. Empty when the code does not exist.
     *
     * @param string $postcode Five-digit Thai postal code
     * @return \Peerapatfc\ThaiAddress\Api\Data\AddressMatchInterface[]
     * @throws \Magento\Framework\Exception\InputException When the postal code is not five digits
     */
    public function getByPostcode(string $postcode): array;
}
