<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MageWorx\RewardPointsGraphQl\Model\Resolver;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\CustomerRegistry;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageWorx\RewardPoints\Api\CustomerBalanceRepositoryInterface;
use MageWorx\RewardPoints\Helper\Data as HelperData;
use MageWorx\RewardPoints\Helper\Price as HelperPrice;
use MageWorx\RewardPoints\Model\PointCurrencyConverter;
use Magento\Framework\Serialize\Serializer\Json;

class CartRewardMessages extends RewardMessages
{
    /**
     * @var CustomerRegistry
     */
    protected $customerRegistry;

    /**
     * @var CustomerBalanceRepositoryInterface
     */
    protected $customerBalanceRepository;

    /**
     * @var Json
     */
    protected $serializer;

    /**
     * @param HelperData $helperData
     * @param HelperPrice $helperPrice
     * @param PointCurrencyConverter $pointConverter
     * @param PriceCurrencyInterface $priceCurrency
     * @param StoreManagerInterface $storeManager
     * @param CustomerRegistry $customerRegistry
     * @param CustomerBalanceRepositoryInterface $customerBalanceRepository
     * @param Json $serializer
     */
    public function __construct(
        HelperData $helperData,
        HelperPrice $helperPrice,
        PointCurrencyConverter $pointConverter,
        PriceCurrencyInterface $priceCurrency,
        StoreManagerInterface $storeManager,
        CustomerRegistry $customerRegistry,
        CustomerBalanceRepositoryInterface $customerBalanceRepository,
        Json $serializer
    ) {
        parent::__construct($helperData, $helperPrice, $pointConverter, $priceCurrency, $storeManager);
        $this->customerRegistry          = $customerRegistry;
        $this->customerBalanceRepository = $customerBalanceRepository;
        $this->serializer                = $serializer;
    }

    /**
     * @param Field $field
     * @param ContextInterface $context
     * @param ResolveInfo $info
     * @param array|null $value
     * @param array|null $args
     * @return array|null
     * @throws LocalizedException
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!isset($value['model'])) {
            throw new LocalizedException(__('"model" value should be specified'));
        }

        if ($context->getExtensionAttributes()->getIsCustomer() === false) {
            return null;
        }

        /** @var StoreInterface $store */
        $store      = $context->getExtensionAttributes()->getStore();
        $storeId    = (int)$store->getId();
        $customerId = (int)$context->getUserId();
        $quote      = $value['model'];
        $points     = $this->getPointsByAppliedRewardRules((string)$quote->getMwEarnPointsData());

        return [
            'header_message'   => $this->getHeaderMessage($points, $storeId),
            'minicart_message' => $customerId ? $this->getMinicartMessage($customerId, $storeId) : '',
            'cart_message'     => $this->getCartMessage($points, $storeId),
            'checkout_message' => $this->getCheckoutMessage($points, $storeId),
        ];
    }

    /**
     * @param string $appliedRewardRulesAsString
     * @return float
     */
    protected function getPointsByAppliedRewardRules(string $appliedRewardRulesAsString): float
    {
        if ($appliedRewardRulesAsString) {
            $appliedRewardRuleData = $this->serializer->unserialize($appliedRewardRulesAsString);
            $pointsAmount          = \array_sum($appliedRewardRuleData);

            return $pointsAmount ? (float)$pointsAmount : 0;
        }

        return 0;
    }

    /**
     * @param float $points
     * @param int $storeId
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getHeaderMessage(float $points, int $storeId): string
    {
        if ($this->helperData->isDisplayUpcomingPoints($storeId)) {
            return $this->getPointBalanceMessage(
                $this->helperData->getUpcomingPointsMessage($storeId),
                $points,
                $storeId
            );
        }

        return '';
    }

    /**
     * @param int $customerId
     * @param int $storeId
     * @return string
     * @throws LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getMinicartMessage(int $customerId, int $storeId): string
    {
        /** @var Customer $customer */
        $customer = $this->customerRegistry->retrieve($customerId);

        if (!$customer || !$customer->getId()) {
            return '';
        }

        if ($this->helperData->isEnableForCustomer($customer, $storeId)
            && $this->helperData->isDisplayMinicartPointBalanceMessage($storeId)
        ) {
            $websiteId = (int)$this->storeManager->getStore($storeId)->getWebsiteId();
            $points    = $this->customerBalanceRepository->getByCustomer($customerId, $websiteId)->getPoints();

            if ($points) {
                return $this->getPointBalanceMessage(
                    $this->helperData->getMinicartPointBalanceMessage($storeId),
                    $points,
                    $storeId
                );
            } else {
                return $this->helperData->getMinicartEmptyPointBalanceMessage($storeId);
            }
        }

        return '';
    }

    /**
     * @param float $points
     * @param int $storeId
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getCartMessage(float $points, int $storeId): string
    {
        if ($this->helperData->isDisplayCartUpcomingPoints($storeId)) {
            return $this->getPointBalanceMessage(
                $this->helperData->getCartUpcomingPointsMessage($storeId),
                $points,
                $storeId
            );
        }

        return '';
    }

    /**
     * @param float $points
     * @param int $storeId
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getCheckoutMessage(float $points, int $storeId): string
    {
        if ($this->helperData->isDisplayCheckoutUpcomingPoints($storeId)) {
            return $this->getPointBalanceMessage(
                $this->helperData->getCheckoutUpcomingPointsMessage($storeId),
                $points,
                $storeId
            );
        }

        return '';
    }
}
