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

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_page']['fields']['firefighter_support_key'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_page']['firefighter_support_key'],
    'inputType' => 'text',
    'eval' => [
        'tl_class' => 'w50',
        'maxlength' => 128,
    ],
    'sql' => [
        'type' => 'string',
        'length' => 128,
        'default' => '',
    ],
];

foreach (['root', 'rootfallback'] as $palette) {
    PaletteManipulator::create()
        ->addLegend('firefighter_support_legend', 'global_legend', PaletteManipulator::POSITION_AFTER)
        ->addField('firefighter_support_key', 'firefighter_support_legend', PaletteManipulator::POSITION_APPEND)
        ->applyToPalette($palette, 'tl_page');
}