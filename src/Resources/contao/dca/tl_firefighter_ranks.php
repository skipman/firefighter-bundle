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
 
 $GLOBALS['TL_DCA']['tl_firefighter_ranks'] = [
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
             'mode' => 2,
             'fields' => ['rank_short'],
             'flag' => 1
         ],
         'label' => [
             'fields' => ['rank_short', 'rank_long'],
             'format' => '%s (%s)'
         ],
         'global_operations' => [
             'all' => [
                 'label'      => &$GLOBALS['TL_LANG']['MSC']['all'],
                 'href'       => 'act=select',
                 'class'      => 'header_edit_all',
                 'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"'
             ]
         ],
         'operations' => [
             'edit' => [
                 'label'      => &$GLOBALS['TL_LANG']['tl_firefighter_ranks']['edit'],
                 'href'       => 'act=edit',
                 'icon'       => 'edit.svg'
             ],
             'copy' => [
                 'label'      => &$GLOBALS['TL_LANG']['tl_firefighter_ranks']['copy'],
                 'href'       => 'act=copy',
                 'icon'       => 'copy.svg'
             ],
             'delete' => [
                 'label'      => &$GLOBALS['TL_LANG']['tl_firefighter_ranks']['delete'],
                 'href'       => 'act=delete',
                 'icon'       => 'delete.svg',
                 'attributes' => 'onclick="if(!confirm(\'Do you really want to delete?\'))return false;Backend.getScrollOffset()"'
             ],
             'show' => [
                 'label'      => &$GLOBALS['TL_LANG']['tl_firefighter_ranks']['show'],
                 'href'       => 'act=show',
                 'icon'       => 'show.svg'
             ]
         ]
     ],
     'palettes' => [
         'default' => '{rank_legend},rank_short,rank_long,singleSRC'
     ],
     'fields' => [
         'id' => [
             'sql' => "int(10) unsigned NOT NULL auto_increment"
         ],
         'tstamp' => [
             'sql' => "int(10) unsigned NOT NULL default 0"
         ],
         'rank_short' => [
             'label' => &$GLOBALS['TL_LANG']['tl_firefighter_ranks']['rank_short'],
             'inputType' => 'text',
             'eval' => ['mandatory' => true, 'unique' => true, 'maxlength' => 255, 'tl_class' => 'w25'],
             'sql' => "varchar(255) NOT NULL default ''"
         ],
         'rank_long' => [
             'label' => &$GLOBALS['TL_LANG']['tl_firefighter_ranks']['rank_long'],
             'inputType' => 'text',
             'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
             'sql' => "varchar(255) NOT NULL default ''"
         ],
         'singleSRC' => [
             'label' => &$GLOBALS['TL_LANG']['tl_firefighter_ranks']['singleSRC'],
             'inputType' => 'fileTree',
             'eval' => ['filesOnly' => true, 'fieldType' => 'radio', 'tl_class' => 'clr'],
             'sql' => "binary(16) NULL"
         ]
     ]
 ];
 