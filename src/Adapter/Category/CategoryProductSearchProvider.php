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

namespace PrestaShop\PrestaShop\Adapter\Category;

use Category;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchProviderInterface;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchResult;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrdersCollection;
use PrestaShopDatabaseException;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Responsible of getting products for specific category.
 */
class CategoryProductSearchProvider implements ProductSearchProviderInterface
{
    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var Category
     */
    private $category;

    /**
     * @var SortOrdersCollection
     */
    private $sortOrdersCollection;

    public function __construct(
        TranslatorInterface $translator,
        Category $category
    ) {
        $this->translator = $translator;
        $this->category = $category;
        $this->sortOrdersCollection = new SortOrdersCollection($this->translator);
    }

    /**
     * @param ProductSearchContext $context
     * @param ProductSearchQuery $query
     * @param string $type
     *
     * @return array|false|int
     *
     * @throws PrestaShopDatabaseException
     */
    private function getProductsOrCount(
        ProductSearchContext $context,
        ProductSearchQuery $query,
        $type = 'products'
    ) {
        if ($query->getSortOrder()->isRandom()) {
            return $this->category->getProducts(
                $context->getIdLang(),
                1,
                $query->getResultsPerPage(),
                null,
                null,
                $type !== 'products',
                true,
                true,
                $query->getResultsPerPage()
            );
        } else {
            return $this->category->getProducts(
                $context->getIdLang(),
                $query->getPage(),
                $query->getResultsPerPage(),
                $query->getSortOrder()->toLegacyOrderBy(),
                $query->getSortOrder()->toLegacyOrderWay(),
                $type !== 'products'
            );
        }
    }

    /**
     * @param ProductSearchContext $context
     * @param ProductSearchQuery $query
     *
     * @return ProductSearchResult
     *
     * @throws PrestaShopDatabaseException
     */
    public function runQuery(
        ProductSearchContext $context,
        ProductSearchQuery $query
    ) {
        $products = $this->getProductsOrCount($context, $query, 'products');
        $count = $this->getProductsOrCount($context, $query, 'count');

        $result = new ProductSearchResult();

        if (!empty($products)) {
            $result
                ->setProducts($products)
                ->setTotalProductsCount($count);

            // We use default set of sort orders + option to sort by position, which makes sense only here and on search page
            $result->setAvailableSortOrders(
                array_merge(
                    [
                        (new SortOrder('product', 'position', 'asc'))->setLabel(
                            $this->translator->trans('Relevance', [], 'Shop.Theme.Catalog')
                        ),
                        (new SortOrder('product', 'sales', 'desc'))->setLabel(
                            $this->translator->trans('Best sales', [], 'Shop.Theme.Catalog')
                        ),
                    ],
                    $this->sortOrdersCollection->getDefaults())
            );
        }

        return $result;
    }
}
