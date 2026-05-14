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

namespace Skipman\FirefighterBundle\ContaoManager;

use Contao\CalendarBundle\ContaoCalendarBundle;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\ManagerPlugin\Config\ConfigPluginInterface;
use Symfony\Component\Config\Loader\LoaderInterface;
use Skipman\FirefighterBundle\FirefighterBundle;

class Plugin implements BundlePluginInterface, ConfigPluginInterface
{
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create(FirefighterBundle::class)
                ->setLoadAfter([
                    ContaoCoreBundle::class,
                    // Load the ContaoCalendarBundle so that it is not overwritten
                    ContaoCalendarBundle::class
                ]),
        ];
    }
    public function registerContainerConfiguration(LoaderInterface $loader, array $config): void
    {
        $loader->load(__DIR__ . '/../Resources/config/config.yaml');
    }
}