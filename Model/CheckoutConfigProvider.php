<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Model;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteriaBuilder;

/**
 * Tells the checkout front end whether to show the drop-downs, in which language, and which of the logged-in
 * customer's saved addresses the checkout would refuse.
 */
class CheckoutConfigProvider implements ConfigProviderInterface
{
    /**
     * @param Config $config
     * @param AddressChecker $checker
     * @param Session $customerSession
     * @param AddressRepositoryInterface $addressRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */
    public function __construct(
        private readonly Config $config,
        private readonly AddressChecker $checker,
        private readonly Session $customerSession,
        private readonly AddressRepositoryInterface $addressRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder
    ) {
    }

    /**
     * @inheritdoc
     */
    public function getConfig(): array
    {
        return [
            'thaiAddress' => [
                'autocomplete' => $this->config->isAutocompleteEnabled(),
                'language' => $this->config->getLanguage(),
                'invalidAddresses' => (object)$this->getInvalidAddresses(),
            ],
        ];
    }

    /**
     * Why each saved Thai address is refused, keyed by address id. Addresses that pass are left out.
     *
     * @return array<string, string>
     */
    private function getInvalidAddresses(): array
    {
        $customerId = (int)$this->customerSession->getCustomerId();
        if ($customerId === 0 || !$this->config->isValidationEnabled()) {
            return [];
        }

        $criteria = $this->searchCriteriaBuilder->addFilter('parent_id', $customerId)->create();
        $invalid = [];
        foreach ($this->addressRepository->getList($criteria)->getItems() as $address) {
            $error = $this->checker->checkCustomerAddress($address);
            if ($error !== null) {
                $invalid[(string)$address->getId()] = (string)$error;
            }
        }

        return $invalid;
    }
}
