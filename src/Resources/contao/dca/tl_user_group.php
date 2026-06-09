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

// Add fields to tl_user_group
$GLOBALS['TL_DCA']['tl_user_group']['fields']['firefighter'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_user']['firefighter'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'foreignKey' => 'tl_firefighter_archive.title',
    'eval' => ['multiple' => true],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_user_group']['fields']['firefighterp'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_user']['firefighterp'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'options' => ['create', 'delete'],
    'reference' => &$GLOBALS['TL_LANG']['MSC'],
    'eval' => ['multiple' => true],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_user_group']['fields']['firefightercategories'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_user_group']['firefightercategories'],
    'exclude' => true,
    'inputType' => 'checkboxWizard',
    'options_callback' => [FirefighterHelper::class, 'getFirefighterCategoryOptions'],
    'eval' => ['multiple' => true],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_user_group']['fields']['firefighterhomebases'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_user_group']['firefighterhomebases'],
    'exclude' => true,
    'inputType' => 'checkboxWizard',
    'options_callback' => [FirefighterHelper::class, 'getDepartmentPermissionOptions'],
    'eval' => ['multiple' => true],
    'sql' => 'blob NULL',
];

// Remove the fields first so they cannot appear twice if another DCA pass already added them.
$firefighterPermissionFields = ['firefighter', 'firefighterp', 'firefighterhomebases', 'firefightercategories'];
 
PaletteManipulator::create()
    ->addLegend('firefighter_legend', 'title_legend', PaletteManipulator::POSITION_AFTER)
    ->addField($firefighterPermissionFields, 'firefighter_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_user_group')
;
