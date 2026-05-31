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
use Skipman\FirefighterBundle\Helper\FirefighterHelper;

// Add fields to tl_user
$GLOBALS['TL_DCA']['tl_user']['fields']['firefighter'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'foreignKey' => 'tl_firefighter_archive.title',
    'eval' => ['multiple' => true],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_user']['fields']['firefighterp'] = [
    'exclude' => true,
    'inputType' => 'checkbox',
    'options' => ['create', 'delete'],
    'reference' => &$GLOBALS['TL_LANG']['MSC'],
    'eval' => ['multiple' => true],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_user']['fields']['firefightercategories'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_user']['firefightercategories'],
    'exclude' => true,
    'inputType' => 'checkboxWizard',
    'options_callback' => [FirefighterHelper::class, 'getFirefighterCategoryOptions'],
    'eval' => ['multiple' => true],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_user']['fields']['firefighterhomebases'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_user']['firefighterhomebases'],
    'exclude' => true,
    'inputType' => 'checkboxWizard',
    'options_callback' => [FirefighterHelper::class, 'getDepartmentOptions'],
    'eval' => ['multiple' => true],
    'sql' => 'blob NULL',
];

// Extend the default palettes
PaletteManipulator::create()
    ->addLegend('firefighter_legend', 'amg_legend', PaletteManipulator::POSITION_BEFORE)
    ->addField(
        ['firefighter', 'firefighterp', 'firefightercategories', 'firefighterhomebases'],
        'firefighter_legend',
        PaletteManipulator::POSITION_APPEND
    )
    ->applyToPalette('extend', 'tl_user')
    ->applyToPalette('custom', 'tl_user')
;
