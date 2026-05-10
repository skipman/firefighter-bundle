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

$GLOBALS['TL_DCA']['tl_firefighter_functions'] = [
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
            'fields' => ['function_short'],
            'flag' => 1,
            'panelLayout' => 'filter,search,limit'
        ],
        'label' => [
            'fields' => ['function_short','function_long'],
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
              'label'      => &$GLOBALS['TL_LANG']['tl_firefighter_functions']['edit'],
              'href'       => 'act=edit',
              'icon'       => 'edit.svg'
          ],
          'copy' => [
              'label'      => &$GLOBALS['TL_LANG']['tl_firefighter_functions']['copy'],
              'href'       => 'act=copy',
              'icon'       => 'copy.svg'
          ],
          'delete' => [
              'label'      => &$GLOBALS['TL_LANG']['tl_firefighter_functions']['delete'],
              'href'       => 'act=delete',
              'icon'       => 'delete.svg',
              'attributes' => 'onclick="if(!confirm(\'Do you really want to delete?\'))return false;Backend.getScrollOffset()"'
          ],
          'show' => [
              'label'      => &$GLOBALS['TL_LANG']['tl_firefighter_functions']['show'],
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
        'function_short' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter_functions']['function_short'],
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'tl_class' => 'w25', 'maxlength' => 255, ],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => '']
        ],
        'function_long' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter_functions']['function_long'],
            'search' => true,
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50', 'maxlength' => 255, 'mandatory' => false],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => '']
        ],
        'function_overlocal' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter_functions']['function_overlocal'],
            'inputType' => 'checkbox',
            'filter' => true,
            'eval' => ['tl_class' => 'w25 m12', 'mandatory' => false, 'submitOnChange' => true], 
            'sql' => ['type' => 'boolean', 'default' => false]
        ],
        'function_level' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter_functions']['function_level'],
            'inputType' => 'radio',
            'filter' => true,
            'options' => ['section', 'district', 'state', 'federal'],
            'reference' => &$GLOBALS['TL_LANG']['tl_firefighter_functions']['function_level_options'],
            'eval' => [
                'tl_class' => 'w50',
                'includeBlankOption' => true,
            ],
            'save_callback' => [
                ['Skipman\\FirefighterBundle\\Helper\\FirefighterHelper', 'validateFunctionLevel'],
            ],
            'sql' => ['type' => 'string', 'length' => 16, 'default' => ''],
        ],
    ],
    'palettes' => [
        '__selector__' => ['function_overlocal'],
        'default' => '{function_legend},function_short,function_long,function_overlocal'
    ],
    'subpalettes' => [
        'function_overlocal' => 'function_level',
    ],
];
