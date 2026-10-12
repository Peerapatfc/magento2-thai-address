<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Tells the checkout front end whether to suggest addresses from the postal code.
 */
class CheckoutConfigProvider implements ConfigProviderInterface
{
    /**
     * @param Config $config
     */
    public function __construct(private readonly Config $config)
    {
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return ['thaiAddress' => ['autocomplete' => $this->config->isAutocompleteEnabled()]];
    }
}
