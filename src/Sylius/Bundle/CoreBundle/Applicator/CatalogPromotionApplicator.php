<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sylius\Bundle\CoreBundle\Applicator;

use Sylius\Bundle\CoreBundle\Formatter\AppliedPromotionInformationFormatterInterface;
use Sylius\Component\Core\Model\CatalogPromotionInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Promotion\Model\CatalogPromotionActionInterface;

final class CatalogPromotionApplicator implements CatalogPromotionApplicatorInterface
{
    private AppliedPromotionInformationFormatterInterface $appliedPromotionInformationFormatter;

    public function __construct(AppliedPromotionInformationFormatterInterface $appliedPromotionInformationFormatter)
    {
        $this->appliedPromotionInformationFormatter = $appliedPromotionInformationFormatter;
    }

    public function applyCatalogPromotion(
        ProductVariantInterface $variant,
        CatalogPromotionInterface $catalogPromotion
    ): void {
        foreach ($catalogPromotion->getActions() as $action) {
            $this->applyDiscountFromAction($catalogPromotion, $action, $variant);
        }
    }

    private function applyDiscountFromAction(
        CatalogPromotionInterface $catalogPromotion,
        CatalogPromotionActionInterface $action,
        ProductVariantInterface $variant
    ): void {
        $discount = $action->getConfiguration()['amount'];

        foreach ($catalogPromotion->getChannels() as $channel) {
            $channelPricing = $variant->getChannelPricingForChannel($channel);
            if ($channelPricing === null) {
                continue;
            }

            if ($channelPricing->getOriginalPrice() === null) {
                $channelPricing->setOriginalPrice($channelPricing->getPrice());
            }

            $channelPricing->setPrice((int) ($channelPricing->getPrice() - ($channelPricing->getPrice() * $discount)));
            $channelPricing->addAppliedPromotion($this->appliedPromotionInformationFormatter->format($catalogPromotion));
        }
    }
}
