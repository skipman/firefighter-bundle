<?php declare(strict_types=1);

/*
 * This file is part of Firefighter Bundle for Contao Open Source CMS.
 * 
 * (c) Ronald Boda 2022 <info@coboda.at>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/skipman/firefighter-bundle
 */

 use Contao\CoreBundle\DataContainer\PaletteManipulator;

 // Extend the default palette
 PaletteManipulator::create()
     ->addLegend('firefighter_legend', 'amg_legend', PaletteManipulator::POSITION_BEFORE)
     ->addField(['firefighter', 'firefighterp'], 'firefighter_legend', PaletteManipulator::POSITION_APPEND)
     ->applyToPalette('default', 'tl_user_group')
 ;
 
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
 