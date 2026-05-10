<?php 

declare(strict_types=1);

/*
 * This file is part of Firefighter Bundle for Contao Open Source CMS.
 *
 * (c) Ronald Boda 2022-2026 <info@coboda.at>
 *
 * This software is licensed under the GNU General Public License v3.0 or later.
 *
 * Commercial services (such as support, hosted services, or extended features)
 * may require a separate agreement.
 *
 * For full license information, please see the LICENSE file.
 */

namespace Skipman\FirefighterBundle\EventListener\Navigation;

use Contao\CoreBundle\ServiceAnnotation\Hook;
use Contao\PageModel;
use Skipman\FirefighterBundle\Models\FirefighterArchiveModel;
use Skipman\FirefighterBundle\Models\FirefighterModel;
use Terminal42\ChangeLanguage\Event\ChangelanguageNavigationEvent;
use Terminal42\ChangeLanguage\EventListener\Navigation\AbstractNavigationListener;

/**
 * @Hook("changelanguageNavigation")
 */
class FirefighterNavigationListener extends AbstractNavigationListener
{
    protected function getUrlKey(): string
    {
        return 'auto_item';
    }

    protected function findCurrent(): ?FirefighterModel
    {
        $alias = $this->getAutoItem();

        if ('' === $alias) {
            return null;
        }

        /** @var PageModel $objPage */
        global $objPage;

        if (null === ($archives = FirefighterArchiveModel::findBy('jumpTo', $objPage->id))) {
            return null;
        }

        // Fix Contao bug that returns a collection (see contao-changelanguage#71)
        $options = ['limit' => 1, 'return' => 'Model'];

        return FirefighterModel::findPublishedByParentAndIdOrAlias($alias, $archives->fetchEach('id'), $options);
    }

    protected function findPublishedBy(array $columns, array $values = [], array $options = []): ?FirefighterModel
    {
        return FirefighterModel::findOneBy(
            $this->addPublishedConditions($columns, FirefighterModel::getTable()),
            $values,
            $options
        );
    }
}
