<?php declare(strict_types=1);

/*
 * This file is part of Firefighter Bundle for Contao Open Source CMS.
 * 
 * (c) Ronald Boda 2022 <info@coboda.at>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/skipman/firefighter-bundle
 */

 namespace Skipman\FirefighterBundle\EventListener\DataContainer;

 use Contao\Model;
 use Contao\Model\Collection;
 use Skipman\FirefighterBundle\Models\FirefighterModel;
 use Terminal42\ChangeLanguage\EventListener\DataContainer\AbstractChildTableListener;

class FirefighterChildTableListener extends AbstractChildTableListener
{
    protected function getTitleField(): string
    {
        return 'headline';
    }

    protected function getSorting(): string
    {
        return 'sorting';
    }

    /**
     * @param FirefighterModel             $current
     * @param Collection<FirefighterModel> $models
     */
    protected function formatOptions(Model $current, Collection $models): array
    {
        $options = [];

        foreach ($models as $model) {
            $options[$model->id] = sprintf('%s [ID %s]', $model->headline, $model->id);
        }

        return $options;
    }
}
