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

namespace Sylius\Behat\Context\Api\Shop;

use Behat\Behat\Context\Context;
use Sylius\Behat\Client\ApiClientInterface;
use Sylius\Behat\Client\ResponseCheckerInterface;
use Sylius\Component\Core\Model\ProductInterface;
use Sylius\Component\Core\Model\ProductVariantInterface;
use Webmozart\Assert\Assert;

final class ProductVariantContext implements Context
{
    private ApiClientInterface $client;

    private ResponseCheckerInterface $responseChecker;

    public function __construct(
        ApiClientInterface $client,
        ResponseCheckerInterface $responseChecker
    ) {
        $this->client = $client;
        $this->responseChecker = $responseChecker;
    }

    /**
     * @When I select :variant variant
     * @When I view :variant variant
     * @When I view :variant variant of the :product product
     */
    public function iSelectVariant(ProductVariantInterface $variant): void
    {
        $this->client->show($variant->getCode());
    }

    /**
     * @When I view variants
     */
    public function iViewVariants(): void
    {
        $this->client->index();
    }

    /**
     * @Then /^the product variant price should be ("[^"]+")$/
     */
    public function theProductVariantPriceShouldBe(int $price): void
    {
        $response = $this->responseChecker->getResponseContent($this->client->getLastResponse());

        Assert::same($response['price'], $price);
    }

    /**
     * @Then /^the product original price should be ("[^"]+")$/
     */
    public function theProductOriginalPriceShouldBe(int $originalPrice): void
    {
        $response = $this->responseChecker->getResponseContent($this->client->getLastResponse());

        Assert::same($response['originalPrice'], $originalPrice);
    }

    /**
     * @Then /^I should see ("[^"]+" variant) is discounted from ("[^"]+") to ("[^"]+") with "([^"]+)" promotion$/
     * @Then /^I should see (this variant) is discounted from ("[^"]+") to ("[^"]+") with "([^"]+)" promotion$/
     */
    public function iShouldSeeVariantIsDiscountedFromToWithPromotion(
        ProductVariantInterface $variant,
        int $originalPrice,
        int $price,
        string $promotionName
    ): void {
        $content = $this->findVariant($variant);

        Assert::same($content['price'], $price);
        Assert::same($content['originalPrice'], $originalPrice);
        Assert::inArray(['en_US' => ['name' => $promotionName, 'description' => $promotionName . ' description']], $content['appliedPromotions']);
    }

    /**
     * @Then /^I should see ("[^"]+" variant) is not discounted$/
     */
    public function iShouldSeeVariantIsNotDiscounted(ProductVariantInterface $variant): void
    {
        $items = $this->responseChecker->getCollectionItemsWithValue($this->client->getLastResponse(), 'code', $variant->getCode());
        $item = array_pop($items);
        Assert::keyNotExists($item, 'appliedPromotions');
    }

    /**
     * @Then /^I should see this variant is not discounted$/
     */
    public function iShouldSeeThisVariantIsNotDiscounted(): void
    {
        $content = $this->responseChecker->getResponseContent($this->client->getLastResponse());

        Assert::keyNotExists($content, 'appliedPromotions');
    }

    private function findVariant(?ProductVariantInterface $variant): array
    {
        $response = $this->client->getLastResponse();

        if ($variant !== null && $this->responseChecker->hasValue($response, '@type', 'hydra:Collection')) {
            $returnValue = $this->responseChecker->getCollectionItemsWithValue($response, 'code', $variant->getCode());

            return array_shift($returnValue);
        }

        return $this->responseChecker->getResponseContent($response);
    }
}
