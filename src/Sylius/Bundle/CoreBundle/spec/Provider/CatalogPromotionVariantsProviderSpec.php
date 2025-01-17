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

namespace spec\Sylius\Bundle\CoreBundle\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use PhpSpec\ObjectBehavior;
use Sylius\Component\Core\Model\CatalogPromotionInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Sylius\Component\Core\Model\TaxonInterface;
use Sylius\Component\Core\Provider\CatalogPromotionVariantsProviderInterface;
use Sylius\Component\Core\Repository\ProductRepositoryInterface;
use Sylius\Component\Core\Repository\ProductVariantRepositoryInterface;
use Sylius\Component\Promotion\Model\CatalogPromotionRuleInterface;
use Sylius\Component\Taxonomy\Repository\TaxonRepositoryInterface;

final class CatalogPromotionVariantsProviderSpec extends ObjectBehavior
{
    function let(
        ProductVariantRepositoryInterface $productVariantRepository
    ): void {
        $this->beConstructedWith($productVariantRepository);
    }

    function it_implements_catalog_promotion_products_provider_interface(): void
    {
        $this->shouldImplement(CatalogPromotionVariantsProviderInterface::class);
    }

    function it_provides_products_with_configured_variants(
        ProductVariantRepositoryInterface $productVariantRepository,
        CatalogPromotionInterface $catalogPromotion,
        CatalogPromotionRuleInterface $rule,
        ProductVariantInterface $firstVariant,
        ProductVariantInterface $secondVariant
    ): void {
        $catalogPromotion->getRules()->willReturn(new ArrayCollection([$rule->getWrappedObject()]));
        $rule->getConfiguration()->willReturn(['variants' => ['PHP_T_SHIRT_XS_WHITE', 'PHP_T_SHIRT_XS_BLACK', 'PHP_MUG']]);

        $productVariantRepository->findOneBy(['code' => 'PHP_T_SHIRT_XS_WHITE'])->willReturn($firstVariant);
        $productVariantRepository->findOneBy(['code' => 'PHP_T_SHIRT_XS_BLACK'])->willReturn($secondVariant);
        $productVariantRepository->findOneBy(['code' => 'PHP_MUG'])->willReturn(null);

        $firstVariant->getCode()->willReturn('PHP_T_SHIRT_XS_WHITE');
        $secondVariant->getCode()->willReturn('PHP_T_SHIRT_XS_BLACK');

        $this
            ->provideEligibleVariants($catalogPromotion)
            ->shouldReturn([$firstVariant, $secondVariant])
        ;
    }
}
