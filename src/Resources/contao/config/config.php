<?php

/*
 * This file is part of Firefighter Bundle for Contao Open Source CMS.
 * 
 * (c) Ronald Boda 2022 <info@coboda.at>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/skipman/firefighter-bundle
 */

//use Skipman\FirefighterBundle\ContentElement\FirefighterMembersElement;
use Skipman\FirefighterBundle\ContentElement\FirefighterResourcesElement;
use Skipman\FirefighterBundle\ContentElement\FirefighterWebsElement;
use Skipman\FirefighterBundle\Classes\Firefighter;
use Skipman\FirefighterBundle\Models\FirefighterModel;
use Skipman\FirefighterBundle\Modules\ModuleFirefighterList;
use Skipman\FirefighterBundle\Models\FirefighterArchiveModel;
use Skipman\FirefighterBundle\Models\FirefighterCategoryModel;
use Skipman\FirefighterBundle\Modules\ModuleFirefighterReader;

// Register the backend template
$GLOBALS['TL_BE']['default'] = 'backend/be_main';

// Register Backend-Modules

$GLOBALS['BE_MOD']['firefighter_settings'] = [
    'firefighter' => [
        'tables' => ['tl_firefighter_archive', 'tl_firefighter', 'tl_firefighter_category', 'tl_content'],
        'icon' => 'bundles/firefighterbundle/flame.svg',
    ],
    'departments' => [
        'tables' => ['tl_firefighter_departments'],
    ],
    'vehicles' => [
        'tables' => ['tl_firefighter_vehicles'],
    ],
    'ranks' => [
        'tables' => ['tl_firefighter_ranks'],
    ],
    'courses' => [
        'tables' => ['tl_firefighter_courses'],
    ],
    'functions' => [
        'tables' => ['tl_firefighter_functions'],
    ],
    'badges' => [
        'tables' => ['tl_firefighter_badges'],
    ],
    'awards' => [
        'tables' => ['tl_firefighter_awards'],
    ],
];

// Register Frontend-Modules
$GLOBALS['FE_MOD']['firefighter'] = [
    'firefighterlist' => ModuleFirefighterList::class,
    'firefighterreader' => ModuleFirefighterReader::class,
];

$GLOBALS['TL_MODELS']['tl_firefighter'] = FirefighterModel::class;
$GLOBALS['TL_MODELS']['tl_firefighter_archive'] = FirefighterArchiveModel::class;
$GLOBALS['TL_MODELS']['tl_firefighter_category'] = FirefighterCategoryModel::class;

// Register Content-Elements
$GLOBALS['TL_CTE']['texts']['ff_resources'] = FirefighterResourcesElement::class;
$GLOBALS['TL_CTE']['texts']['webs']         = FirefighterWebsElement::class;

$GLOBALS['TL_LANG']['CTE']['ff_resources']  = ['FF-Einsatzressourcen', 'Eingesetzte Ressourcen verwalten'];
$GLOBALS['TL_LANG']['CTE']['webs']          = ['FF-Webs', 'Webseiten und Social Media Links anzeigen'];

/*
 * Register hooks
 */
$GLOBALS['TL_HOOKS']['getSearchablePages'][] = [Firefighter::class, 'getSearchablePages'];

/*
 * Add permissions
 */
$GLOBALS['TL_PERMISSIONS'][] = 'firefighter';
$GLOBALS['TL_PERMISSIONS'][] = 'firefighterp';