<?php

declare(strict_types=1);

namespace Peerapatfc\ThaiAddress\Test\Unit\Model;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Customer\Api\Data\AddressSearchResultsInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\Api\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use PHPUnit\Framework\TestCase;
use Peerapatfc\ThaiAddress\Model\AddressChecker;
use Peerapatfc\ThaiAddress\Model\CheckoutConfigProvider;
use Peerapatfc\ThaiAddress\Model\Config;

class CheckoutConfigProviderTest extends TestCase
{
    private function provider(int $customerId, array $addresses, bool $validation = true): CheckoutConfigProvider
    {
        $config = $this->createMock(Config::class);
        $config->method('isAutocompleteEnabled')->willReturn(true);
        $config->method('getLanguage')->willReturn('th');
        $config->method('isValidationEnabled')->willReturn($validation);

        $checker = $this->createMock(AddressChecker::class);
        $checker->method('checkCustomerAddress')->willReturnCallback(
            fn (AddressInterface $a) => $a->getCity() === 'bad' ? __('District "%1" is not in postal code %2.', 'bad', '10500') : null
        );

        $session = $this->createMock(Session::class);
        $session->method('getCustomerId')->willReturn($customerId);

        $results = $this->createMock(AddressSearchResultsInterface::class);
        $results->method('getItems')->willReturn($addresses);
        $repository = $this->createMock(AddressRepositoryInterface::class);
        $repository->method('getList')->willReturn($results);

        $builder = $this->createMock(SearchCriteriaBuilder::class);
        $builder->method('addFilter')->willReturnSelf();
        $builder->method('create')->willReturn($this->createMock(SearchCriteria::class));

        return new CheckoutConfigProvider($config, $checker, $session, $repository, $builder);
    }

    private function address(int $id, string $city): AddressInterface
    {
        $address = $this->createMock(AddressInterface::class);
        $address->method('getId')->willReturn($id);
        $address->method('getCity')->willReturn($city);

        return $address;
    }

    public function testItReportsSettingsAndTheSavedAddressesTheCheckoutWouldRefuse(): void
    {
        $config = $this->provider(5, [$this->address(11, 'ok'), $this->address(12, 'bad')])->getConfig()['thaiAddress'];

        $this->assertTrue($config['autocomplete']);
        $this->assertSame('th', $config['language']);
        $this->assertSame(['12' => 'District "bad" is not in postal code 10500.'], (array)$config['invalidAddresses']);
    }

    public function testAGuestHasNoSavedAddresses(): void
    {
        $this->assertSame([], (array)$this->provider(0, [$this->address(12, 'bad')])->getConfig()['thaiAddress']['invalidAddresses']);
    }

    public function testNothingIsFlaggedWhenValidationIsSwitchedOff(): void
    {
        $config = $this->provider(5, [$this->address(12, 'bad')], false)->getConfig()['thaiAddress'];

        $this->assertSame([], (array)$config['invalidAddresses']);
    }

    public function testTheListIsAnObjectSoItBecomesAMapInJson(): void
    {
        $config = $this->provider(0, [])->getConfig()['thaiAddress'];

        $this->assertSame('{}', json_encode($config['invalidAddresses']));
    }
}
