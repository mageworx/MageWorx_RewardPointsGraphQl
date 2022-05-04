<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MageWorx\RewardPointsGraphQl\Model\Resolver\Customer;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Exception\GraphQlInputException;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Store\Api\Data\StoreInterface;
use MageWorx\RewardPoints\Helper\Data as HelperData;
use MageWorx\RewardPoints\Api\CustomerBalanceRepositoryInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use MageWorx\RewardPoints\Api\Data\CustomerBalanceInterface;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use MageWorx\RewardPoints\Model\PointTransaction;
use MageWorx\RewardPoints\Model\ResourceModel\PointTransaction\CollectionFactory as PointTransactionCollectionFactory;
use MageWorx\RewardPoints\Model\ResourceModel\PointTransaction\Collection as PointTransactionCollection;
use MageWorx\RewardPoints\Model\PointTransactionMessageMaker;

class RewardPoints implements ResolverInterface
{
    /**
     * @var CustomerRegistry
     */
    protected $customerRegistry;

    /**
     * @var HelperData
     */
    protected $helperData;

    /**
     * @var CustomerBalanceRepositoryInterface
     */
    protected $customerBalanceRepository;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var TimezoneInterface
     */
    protected $timezone;

    /**
     * @var PointTransactionCollectionFactory
     */
    protected $pointTransactionCollectionFactory;

    /**
     * @var PointTransactionMessageMaker
     */
    protected $pointTransactionMessageMaker;

    /**
     * @param CustomerRegistry $customerRegistry
     * @param HelperData $helperData
     * @param CustomerBalanceRepositoryInterface $customerBalanceRepository
     * @param PriceCurrencyInterface $priceCurrency
     * @param TimezoneInterface $timezone
     * @param PointTransactionCollectionFactory $pointTransactionCollectionFactory
     * @param PointTransactionMessageMaker $pointTransactionMessageMaker
     */
    public function __construct(
        CustomerRegistry $customerRegistry,
        HelperData $helperData,
        CustomerBalanceRepositoryInterface $customerBalanceRepository,
        PriceCurrencyInterface $priceCurrency,
        TimezoneInterface $timezone,
        PointTransactionCollectionFactory $pointTransactionCollectionFactory,
        PointTransactionMessageMaker $pointTransactionMessageMaker
    ) {
        $this->customerRegistry                  = $customerRegistry;
        $this->helperData                        = $helperData;
        $this->customerBalanceRepository         = $customerBalanceRepository;
        $this->priceCurrency                     = $priceCurrency;
        $this->timezone                          = $timezone;
        $this->pointTransactionCollectionFactory = $pointTransactionCollectionFactory;
        $this->pointTransactionMessageMaker      = $pointTransactionMessageMaker;
    }

    /**
     * @param Field $field
     * @param \Magento\Framework\GraphQl\Query\Resolver\ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|null
     * @throws GraphQlInputException
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        /** @var Customer $customer */
        $customer = $this->customerRegistry->retrieve($context->getUserId());
        if (!$customer || !$customer->getId()) {
            throw new GraphQlInputException(
                __('Something went wrong while loading the customer.')
            );
        }

        /** @var StoreInterface $store */
        $store = $context->getExtensionAttributes()->getStore();

        if (!$this->helperData->isEnableForCustomer($customer, (int)$store->getId())) {
            return null;
        }

        $customerBalance = $this->customerBalanceRepository->getByCustomer(
            (int)$customer->getId(),
            (int)$store->getWebsiteId()
        );

        return [
            'balance'         => [
                'points' => $customerBalance->getPoints(),
                'money'  => [
                    'value'    => $this->priceCurrency->convertAndRound($customerBalance->getCurrencyAmount(), $store),
                    'currency' => $store->getCurrentCurrency()->getCode()
                ]
            ],
            'expiration_date' => $this->getExpirationDate($customerBalance),
            'transactions'    => $this->getTransactionsData((int)$customer->getId(), (int)$store->getWebsiteId())
        ];
    }

    /**
     * @param CustomerBalanceInterface $customerBalance
     * @return string|null
     * @throws \Exception
     */
    protected function getExpirationDate(CustomerBalanceInterface $customerBalance): ?string
    {
        if ($customerBalance->getExpirationDate() && $this->helperData->isEnableExpirationDate(
                $customerBalance->getWebsiteId()
            )) {
            return $this->timezone->formatDate(new \DateTime($customerBalance->getExpirationDate()));
        }

        return null;
    }

    /**
     * @param int $customerId
     * @param int $websiteId
     * @return array|null
     * @throws \Exception
     */
    protected function getTransactionsData(int $customerId, int $websiteId): ?array
    {
        $data = [];

        /** @var PointTransactionCollection $collection */
        $collection = $this->pointTransactionCollectionFactory->create();
        $collection
            ->addCustomerFilter($customerId)
            ->addWebsiteFilter($websiteId)
            ->setDefaultOrder();

        /** @var PointTransaction $item */
        foreach ($collection->getItems() as $item) {
            $data[] = [
                'balance' => $item->getPointsBalance(),
                'delta'   => $this->getPointsDelta($item),
                'message' => $this->getPointTransactionMessage($item),
                'date'    => $this->timezone->formatDate(
                    new \DateTime($item->getCreatedAt()),
                    \IntlDateFormatter::SHORT,
                    true
                )
            ];
        }

        return $data ?: null;
    }

    /**
     * @param PointTransaction $item
     * @return string
     */
    protected function getPointsDelta(PointTransaction $item): string
    {
        $prefix = ($item->getPointsDelta() > 0) ? '+' : '';

        return $prefix . $item->getPointsDelta();
    }

    /**
     * @param PointTransaction $item
     * @return string
     */
    protected function getPointTransactionMessage(PointTransaction $item): string
    {
        return (string)$this->pointTransactionMessageMaker->getTransactionMessage(
            $item->getEventCode(),
            $item->getEventData(),
            $item->getComment()
        );
    }
}
