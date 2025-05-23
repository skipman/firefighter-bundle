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

use Contao\DC_Table;

$GLOBALS['TL_DCA']['tl_firefighter_courses'] = [
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
            'fields' => ['course_short'],
            'flag' => 1,
            'panelLayout' => 'search,limit'
        ],
        'label' => [
            'fields' => ['course_short','course_long'],
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
              'label'      => &$GLOBALS['TL_LANG']['tl_firefighter_courses']['edit'],
              'href'       => 'act=edit',
              'icon'       => 'edit.svg'
          ],
          'copy' => [
              'label'      => &$GLOBALS['TL_LANG']['tl_firefighter_courses']['copy'],
              'href'       => 'act=copy',
              'icon'       => 'copy.svg'
          ],
          'delete' => [
              'label'      => &$GLOBALS['TL_LANG']['tl_firefighter_courses']['delete'],
              'href'       => 'act=delete',
              'icon'       => 'delete.svg',
              'attributes' => 'onclick="if(!confirm(\'Do you really want to delete?\'))return false;Backend.getScrollOffset()"'
          ],
          'show' => [
              'label'      => &$GLOBALS['TL_LANG']['tl_firefighter_courses']['show'],
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
        'course_short' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter_courses']['course_short'],
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'tl_class' => 'w25', 'maxlength' => 255, ],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => '']
        ],
        'course_long' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter_courses']['course_long'],
            'search' => true,
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50', 'maxlength' => 255, 'mandatory' => false],
            'sql' => ['type' => 'string', 'length' => 255, 'default' => '']
        ]
    ],
    'palettes' => [
        'default' => '{course_legend},course_short,course_long'
    ],
];
