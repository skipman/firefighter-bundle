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

use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_firefighter_awards'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'switchToEdit' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => 1,
            'fields' => ['award_short'],
            'flag' => 1,
            'panelLayout' => 'search,limit'
        ],
        'label' => [
            'fields' => ['award_short','award_long'],
            'format' => '%s (%s)',
        ],
        'global_operations' => [
            'all' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
          'edit' => [
              'label'      => &$GLOBALS['TL_LANG']['tl_firefighter_awards']['edit'],
              'href'       => 'act=edit',
              'icon'       => 'edit.svg'
          ],
          'copy' => [
              'label'      => &$GLOBALS['TL_LANG']['tl_firefighter_awards']['copy'],
              'href'       => 'act=copy',
              'icon'       => 'copy.svg'
          ],
          'delete' => [
              'label'      => &$GLOBALS['TL_LANG']['tl_firefighter_awards']['delete'],
              'href'       => 'act=delete',
              'icon'       => 'delete.svg',
              'attributes' => 'onclick="if(!confirm(\'Do you really want to delete?\'))return false;Backend.getScrollOffset()"'
          ],
          'show' => [
              'label'      => &$GLOBALS['TL_LANG']['tl_firefighter_awards']['show'],
              'href'       => 'act=show',
              'icon'       => 'show.svg'
          ]
      ]
    ],
    'fields' => [
        'id' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'autoincrement' => true],
        ],
        'tstamp' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]
        ],
        'award_short' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter_awards']['award_short'],
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'tl_class' => 'w25', 'maxlength' => 255, ],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => '']
        ],
        'award_long' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter_awards']['award_long'],
            'search' => true,
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50', 'maxlength' => 255, 'mandatory' => false],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => '']
        ]
    ],
    'palettes' => [
        'default' => '{award_legend},award_short,award_long'
    ],
];
