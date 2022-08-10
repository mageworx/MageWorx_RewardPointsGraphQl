<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MageWorx\RewardPointsGraphQl\Model\Resolver;

use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use MageWorx\RewardPoints\Helper\Data as HelperData;
use MageWorx\RewardPoints\Helper\Price as HelperPrice;
use MageWorx\RewardPoints\Model\PointCurrencyConverter;
use Magento\Store\Model\StoreManagerInterface;

abstract class RewardMessages implements ResolverInterface
{
    /**
     * @var \MageWorx\RewardPoints\Helper\Data
     */
    protected $helperData;

    /**
     * @var HelperPrice
     */
    protected $helperPrice;

    /**
     * @var PointCurrencyConverter
     */
    protected $pointConverter;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param HelperData $helperData
     * @param HelperPrice $helperPrice
     * @param PointCurrencyConverter $pointConverter
     * @param PriceCurrencyInterface $priceCurrency
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        HelperData $helperData,
        HelperPrice $helperPrice,
        PointCurrencyConverter $pointConverter,
        PriceCurrencyInterface $priceCurrency,
        StoreManagerInterface $storeManager
    ) {
        $this->helperData     = $helperData;
        $this->helperPrice    = $helperPrice;
        $this->pointConverter = $pointConverter;
        $this->priceCurrency  = $priceCurrency;
        $this->storeManager   = $storeManager;
    }

    /**
     * @param string $message
     * @param float $points
     * @param int $storeId
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    protected function getPointBalanceMessage(string $message, float $points, int $storeId): string
    {
        if ($points && $message) {
            $value = $this->pointConverter->getCurrencyByPoints($points, $storeId);

            // No message if points can't be generated to currency
            if (!$value) {
                return '';
            }

            if (strpos($message, '[p]') !== false) {
                $message = str_replace('[p]', (string)$this->helperPrice->roundPoints($points), $message);
            }

            if (strpos($message, '[c]') !== false) {
                $currency = $this->priceCurrency->convertAndFormat(
                    $value,
                    false,
                    PriceCurrencyInterface::DEFAULT_PRECISION,
                    $this->storeManager->getStore($storeId)
                );

                $message = str_replace('[c]', $currency, $message);
            }
        } else {
            $message = '';
        }

        return $message;
    }
}
