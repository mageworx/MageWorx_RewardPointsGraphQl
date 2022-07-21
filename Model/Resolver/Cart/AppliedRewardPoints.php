<?php
/**
 * Copyright © MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace MageWorx\RewardPointsGraphQl\Model\Resolver\Cart;

use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Framework\Exception\LocalizedException;
use MageWorx\RewardPoints\Helper\Price as HelperPrice;
use Magento\Framework\GraphQl\Query\Resolver\ContextInterface;

class AppliedRewardPoints implements ResolverInterface
{
    /**
     * @var HelperPrice
     */
    protected $helperPrice;

    /**
     * @param HelperPrice $helperPrice
     */
    public function __construct(HelperPrice $helperPrice)
    {
        $this->helperPrice = $helperPrice;
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

        $quote = $value['model'];
        if ($quote->getMwRwrdpointsAmnt()) {
            return [
                'points' => $this->helperPrice->roundPoints((float)$quote->getMwRwrdpointsAmnt()),
                'money'  => [
                    'value'    => $this->helperPrice->roundPoints((float)$quote->getMwRwrdpointsCurAmnt()),
                    'currency' => $quote->getQuoteCurrencyCode()
                ]
            ];
        }

        return null;
    }
}
