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

namespace PrestaShop\PrestaShop\Core\Domain\Meta\Command;

use PrestaShop\PrestaShop\Core\Domain\Meta\Exception\MetaConstraintException;
use PrestaShop\PrestaShop\Core\Domain\Meta\ValueObject\Name;

/**
 * Class AddMetaCommand is responsible for saving meta entities data.
 */
class AddMetaCommand extends AbstractMetaCommand
{
    /**
     * @var Name
     */
    private $pageName;

    /**
     * @var string[]
     */
    private $localisedPageTitle;

    /**
     * @var string[]
     */
    private $localisedMetaDescription;

    /**
     * @var string[]
     */
    private $LocalisedRewriteUrls;

    /**
     * @param string $pageName
     *
     * @throws MetaConstraintException
     */
    public function __construct($pageName)
    {
        $this->pageName = new Name($pageName);
    }

    /**
     * @return Name
     */
    public function getPageName()
    {
        return $this->pageName;
    }

    /**
     * @return string[]
     */
    public function getLocalisedPageTitles()
    {
        return $this->localisedPageTitle;
    }

    /**
     * @param string[] $localisedPageTitle
     *
     * @return self
     *
     * @throws MetaConstraintException
     */
    public function setLocalisedPageTitle(array $localisedPageTitle)
    {
        foreach ($localisedPageTitle as $idLang => $title) {
            $this->assertNameMatchesRegexPattern($idLang, $title, MetaConstraintException::INVALID_PAGE_TITLE);
        }

        $this->localisedPageTitle = $localisedPageTitle;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getLocalisedMetaDescription()
    {
        return $this->localisedMetaDescription;
    }

    /**
     * @param string[] $localisedMetaDescription
     *
     * @return self
     *
     * @throws MetaConstraintException
     */
    public function setLocalisedMetaDescription(array $localisedMetaDescription)
    {
        foreach ($localisedMetaDescription as $idLang => $description) {
            $this->assertNameMatchesRegexPattern($idLang, $description, MetaConstraintException::INVALID_META_DESCRIPTION);
        }

        $this->localisedMetaDescription = $localisedMetaDescription;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getLocalisedRewriteUrls()
    {
        return $this->LocalisedRewriteUrls;
    }

    /**
     * @param string[] $LocalisedRewriteUrls
     *
     * @return self
     */
    public function setLocalisedRewriteUrls(array $LocalisedRewriteUrls)
    {
        $this->LocalisedRewriteUrls = $LocalisedRewriteUrls;

        return $this;
    }
}
