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

use Skipman\FirefighterBundle\Helper\FirefighterHelper;

$GLOBALS['TL_DCA']['tl_content']['palettes']['webs'] = '{type_legend},type;{website_legend},webDepartment;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space;{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['palettes']['ff_resources'] = '{type_legend},type;{firefighter_legend},firefighterDetails;{otherOrganisation_legend},otherOrganisationDetails;{protected_legend:hide},protected;{expert_legend:hide},guests,invisible,start,stop';

/*
 * Add fields for content-element ff-webs
 */

$GLOBALS['TL_DCA']['tl_content']['fields']['webDepartment'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => [FirefighterHelper::class, 'getDepartments'],
    'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
    'sql' => "int(10) unsigned NOT NULL default 0"
];

/*
 * Add fields for content-element resources
 */

$GLOBALS['TL_DCA']['tl_content']['fields']['firefighterDetails'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['firefighterDetails'],
    'exclude' => true,
    'inputType' => 'multiColumnWizard',
    'eval' => [
        'columnFields' => [
            'ffname' => [
                'label' => &$GLOBALS['TL_LANG']['tl_content']['ffname'],
                'inputType' => 'select',
                'options_callback' => [FirefighterHelper::class, 'getDepartments'],
                'eval' => ['style' => 'width:200px', 'includeBlankOption' => true, 'chosen' => true, 'submitOnChange' => true]
            ],
            'vehicles' => [
                'label' => &$GLOBALS['TL_LANG']['tl_content']['vehicles'],
                'inputType' => 'checkboxWizard',
                'options_callback' => [FirefighterHelper::class, 'getVehiclesByDepartment'],
                'eval' => ['multiple' => true, 'style' => 'width:200px float:left']
            ],
            'team' => [
                'label' => &$GLOBALS['TL_LANG']['tl_content']['team'],
                'inputType' => 'text',
                'eval' => ['rgxp' => 'digit', 'maxlength' => 2, 'style' => 'width:80px']
            ],
        ],
        'tl_class' => 'clr'
    ],
    'sql' => "blob NULL",
    'maxCount' => 0,
];

$GLOBALS['TL_DCA']['tl_content']['fields']['otherOrganisationDetails'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_content']['otherOrganisationDetails'],
    'exclude' => true,
    'inputType' => 'multiColumnWizard',
    'eval' => [
        'columnFields' => [
            'otherOrganisationName' => [
                'label' => &$GLOBALS['TL_LANG']['tl_content']['otherOrganisationName'],
                'inputType' => 'text',
                'eval' => ['maxlenght' => 255,'style' => 'width:180px']
            ],
            'otherOrganisationVehicles' => [
                'label' => &$GLOBALS['TL_LANG']['tl_content']['otherOrganisationVehicles'],
                'inputType' => 'text',
                'eval' => ['maxlenght' => 255,'style' => 'width:180px']
            ],
            'team' => [
                'label' => &$GLOBALS['TL_LANG']['tl_content']['otherOrganisationTeam'],
                'inputType' => 'text',
                'eval' => ['maxlenght' => 255,'style' => 'width:80px']
            ],
        ],
        'tl_class' => 'clr',
        'maxCount' => 0,
    ],
    'sql' => "blob NULL"
];
