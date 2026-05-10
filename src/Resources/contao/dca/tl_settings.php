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

$GLOBALS['TL_DCA']['tl_settings']['palettes']['default'] .= ';{firefighter_support_legend},firefighter_support_key';

$GLOBALS['TL_DCA']['tl_settings']['fields']['firefighter_support_key'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_settings']['firefighter_support_key'],
    'inputType' => 'text',
    'eval' => [
        'tl_class' => 'w50',
        'maxlength' => 128,
        'decodeEntities' => true,
    ],
];