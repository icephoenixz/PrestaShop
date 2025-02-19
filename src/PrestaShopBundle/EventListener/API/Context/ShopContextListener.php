<?php
/**
 * Copyright since 2007 PrestaShop SA and Contributors
 * PrestaShop is an International Registered Trademark & Property of PrestaShop SA
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/OSL-3.0
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to https://devdocs.prestashop.com/ for more information.
 *
 * @author    PrestaShop SA and Contributors <contact@prestashop.com>
 * @copyright Since 2007 PrestaShop SA and Contributors
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License (OSL 3.0)
 */

declare(strict_types=1);

namespace PrestaShopBundle\EventListener\API\Context;

use PrestaShop\PrestaShop\Adapter\Feature\MultistoreFeature;
use PrestaShop\PrestaShop\Core\Context\ShopContextBuilder;
use PrestaShop\PrestaShop\Core\Domain\Configuration\ShopConfigurationInterface;
use PrestaShop\PrestaShop\Core\Domain\Shop\ValueObject\ShopCollection;
use PrestaShop\PrestaShop\Core\Domain\Shop\ValueObject\ShopConstraint;
use PrestaShopBundle\Controller\Api\OAuth2\AccessTokenController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Listener dedicated to set up Shop context for the Back-Office/Admin application.
 */
class ShopContextListener
{
    public function __construct(
        private readonly ShopContextBuilder $shopContextBuilder,
        private readonly MultistoreFeature $multistoreFeature,
        private readonly ShopConfigurationInterface $configuration,
    ) {
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->multistoreFeature->isUsed()) {
            // When multistore is not enabled shop context is pretty straightforward to setup, simply use the default shop
            $defaultShopId = $this->getConfiguredDefaultShopId();
            $shopConstraint = ShopConstraint::shop($defaultShopId);
            $this->shopContextBuilder->setShopConstraint($shopConstraint);
            $this->shopContextBuilder->setShopId($defaultShopId);
        } else {
            // When multishop is used the context must be specified via request parameters
            $shopConstraint = $this->getShopConstraintFromRequest($event->getRequest());
            if (null === $shopConstraint) {
                $event->setResponse(new JsonResponse('Multi shop is enabled, you must specify a shop context', JsonResponse::HTTP_BAD_REQUEST));

                return;
            }

            $this->shopContextBuilder->setShopConstraint($shopConstraint);
            if ($shopConstraint->getShopId()) {
                $this->shopContextBuilder->setShopId($shopConstraint->getShopId()->getValue());
            } elseif ($shopConstraint instanceof ShopCollection && $shopConstraint->hasShopIds()) {
                $this->shopContextBuilder->setShopId($shopConstraint->getShopIds()[0]->getValue());
            } else {
                $this->shopContextBuilder->setShopId($this->getConfiguredDefaultShopId());
            }
        }

        // Set shop constraint easily accessible via request attribute
        $event->getRequest()->attributes->set('shopConstraint', $shopConstraint);
    }

    private function getConfiguredDefaultShopId(): int
    {
        return (int) $this->configuration->get('PS_SHOP_DEFAULT', null, ShopConstraint::allShops());
    }

    private function getShopConstraintFromRequest(Request $request): ?ShopConstraint
    {
        if ($request->get('shopId')) {
            return ShopConstraint::shop((int) $request->get('shopId'));
        }

        if ($request->get('shopGroupId')) {
            return ShopConstraint::shopGroup((int) $request->get('shopGroupId'));
        }

        if ($request->get('shopIds')) {
            $shopIds = $request->get('shopIds');
            if (is_string($shopIds)) {
                $shopIds = explode(',', $shopIds);
            }

            return ShopCollection::shops(array_map(fn (string $shopId) => (int) $shopId, $shopIds));
        }

        // Parameter allShops indicate the all shops context regardless of its value, it can be empty it's enough
        if ($request->query->has('allShops') || $request->request->has('allShops') || $request->attributes->has('allShops')) {
            return ShopConstraint::allShops();
        }

        // Special use case when calling the access token controller, we don't want to block the endpoint even if no
        // context parameters was specified, so we use the default shop as a fallback
        if ($request->attributes->get('_controller') === AccessTokenController::class) {
            return ShopConstraint::shop($this->getConfiguredDefaultShopId());
        }

        return null;
    }
}
