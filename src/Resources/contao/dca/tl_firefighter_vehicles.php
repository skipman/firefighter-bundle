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
 
 $GLOBALS['TL_DCA']['tl_firefighter_vehicles'] = [
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
             'fields' => ['vehicle_short'],
             'flag' => 1
         ],
         'label' => [
             'fields' => ['vehicle_short', 'vehicle_long'],
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
                 'label'      => &$GLOBALS['TL_LANG']['tl_firefighter_vehicles']['edit'],
                 'href'       => 'act=edit',
                 'icon'       => 'edit.svg'
             ],
             'copy' => [
                 'label'      => &$GLOBALS['TL_LANG']['tl_firefighter_vehicles']['copy'],
                 'href'       => 'act=copy',
                 'icon'       => 'copy.svg'
             ],
             'delete' => [
                 'label'      => &$GLOBALS['TL_LANG']['tl_firefighter_vehicles']['delete'],
                 'href'       => 'act=delete',
                 'icon'       => 'delete.svg',
                 'attributes' => 'onclick="if(!confirm(\'Do you really want to delete?\'))return false;Backend.getScrollOffset()"'
             ],
             'show' => [
                 'label'      => &$GLOBALS['TL_LANG']['tl_firefighter_vehicles']['show'],
                 'href'       => 'act=show',
                 'icon'       => 'show.svg'
             ]
         ]
     ],
     'palettes' => [
         'default' => '{vehicle_legend},vehicle_short,vehicle_long'
     ],
     'fields' => [
         'id' => [
             'sql' => "int(10) unsigned NOT NULL auto_increment"
         ],
         'tstamp' => [
             'sql' => "int(10) unsigned NOT NULL default 0"
         ],
         'vehicle_short' => [
             'label' => &$GLOBALS['TL_LANG']['tl_firefighter_vehicles']['vehicle_short'],
             'inputType' => 'text',
             'eval' => [
                'mandatory' => true, 
                'unique' => true, 
                'maxlength' => 255, 
                'tl_class' => 'w25',
             ],
             'sql' => "varchar(255) NOT NULL default ''"
         ],
         'vehicle_long' => [
             'label' => &$GLOBALS['TL_LANG']['tl_firefighter_vehicles']['vehicle_long'],
             'inputType' => 'text',
             'eval' => [
                'mandatory' => true, 
                'tl_class' => 'w50',
             ],
             'sql' => "varchar(255) NOT NULL default ''"
         ]
     ]
 ];
 