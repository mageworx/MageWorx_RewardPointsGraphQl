<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MageWorx\RewardPointsGraphQl\Model\Resolver;

use Magento\Customer\Api\Data\GroupInterface;
use Magento\Customer\Model\ResourceModel\CustomerRepository;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use MageWorx\RewardPoints\Helper\Data as HelperData;
use MageWorx\RewardPoints\Helper\Price as HelperPrice;
use MageWorx\RewardPoints\Model\PointCurrencyConverter;
use MageWorx\RewardPoints\Model\RuleActionValidator;

class ProductRewardMessages extends RewardMessages
{
    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var RuleActionValidator
     */
    protected $ruleActionValidator;

    /**
     * @param HelperData $helperData
     * @param HelperPrice $helperPrice
     * @param PointCurrencyConverter $pointConverter
     * @param PriceCurrencyInterface $priceCurrency
     * @param StoreManagerInterface $storeManager
     * @param CustomerRepository $customerRepository
     * @param RuleActionValidator $ruleActionValidator
     */
    public function __construct(
        HelperData $helperData,
        HelperPrice $helperPrice,
        PointCurrencyConverter $pointConverter,
        PriceCurrencyInterface $priceCurrency,
        StoreManagerInterface $storeManager,
        CustomerRepository $customerRepository,
        RuleActionValidator $ruleActionValidator
    ) {
        parent::__construct($helperData, $helperPrice, $pointConverter, $priceCurrency, $storeManager);
        $this->customerRepository  = $customerRepository;
        $this->ruleActionValidator = $ruleActionValidator;
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

        $customerGroupId = GroupInterface::NOT_LOGGED_IN_ID;

        if ($context->getUserId()) {
            $customer        = $this->customerRepository->getById((int)$context->getUserId());
            $customerGroupId = (int)$customer->getGroupId();
        }

        /** @var StoreInterface $store */
        $store   = $context->getExtensionAttributes()->getStore();
        $storeId = (int)$store->getId();

        /** @var \Magento\Catalog\Model\Product $product */
        $product = $value['model'];
        $product->setCustomerGroupId($customerGroupId);

        $points = $this->ruleActionValidator->getPointsByProduct($product);

        if ($points) {
            return [
                'product_message'  => $this->getProductMessage($points, $storeId),
                'category_message' => $this->getCategoryMessage($points, $storeId)
            ];
        }

        return null;
    }

    /**
     * @param float $points
     * @param int $storeId
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getProductMessage(float $points, int $storeId): string
    {
        if ($this->helperData->isDisplayPromiseMessageOnProduct($storeId)) {
            return $this->getPointBalanceMessage(
                $this->helperData->getPromiseMessageForProduct($storeId),
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
    protected function getCategoryMessage(float $points, int $storeId): string
    {
        if ($this->helperData->isDisplayPromiseMessageOnCategory($storeId)) {
            return $this->getPointBalanceMessage(
                $this->helperData->getPromiseMessageForCategory($storeId),
                $points,
                $storeId
            );
        }

        return '';
    }
}
