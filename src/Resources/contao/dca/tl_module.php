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

use Contao\Backend;
use Contao\Database;
use Contao\BackendUser;

$GLOBALS['TL_DCA']['tl_module']['palettes']['firefighterlist'] = '{title_legend},name,headline,type;{config_legend},firefighter_archives,firefighter_readerModule,firefighter_featured,numberOfItems,filter_firefightercategories,perPage;{nav_legend},firefighter_filter,firefighter_filter_reset;{redirect_legend},jumpTo;{template_legend:hide},firefighter_template,customTpl;{image_legend:hide},imgSize;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID,space';
$GLOBALS['TL_DCA']['tl_module']['palettes']['firefighterreader'] = '{title_legend},name,headline,type;{config_legend},firefighter_archives,overviewPage,customLabel;{template_legend:hide},firefighter_template,customTpl;{protected_legend:hide},{image_legend:hide},imgSize;protected;{expert_legend:hide},guests,cssID,space';

/*
 * Add fields to tl_module
 */
$GLOBALS['TL_DCA']['tl_module']['fields']['firefighter_archives'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['firefighter_archives'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'options_callback' => ['tl_module_firefighter', 'getFirefighterArchives'],
    'eval' => ['multiple' => true, 'mandatory' => true],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_module']['fields']['firefighter_template'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['firefighter_template'],
    'default' => 'firefighter_short',
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => ['tl_module_firefighter', 'getFirefighterTemplates'],
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(32) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['firefighter_featured'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['firefighter_featured'],
    'default' => 'all_items',
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['all_items', 'featured', 'unfeatured'],
    'reference' => &$GLOBALS['TL_LANG']['tl_module'],
    'eval' => ['tl_class' => 'w50 clr'],
    'sql' => "varchar(16) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['firefighter_filter'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['firefighter_filter'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50 clr'],
    'sql' => ['type' => 'boolean', 'default' => 0],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['firefighter_filter_reset'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['firefighter_filter_reset'],
    'exclude' => true,
    'inputType' => 'checkbox',
    'eval' => ['tl_class' => 'w50'],
    'sql' => ['type' => 'boolean', 'default' => 0],
];

$GLOBALS['TL_DCA']['tl_module']['fields']['filter_firefightercategories'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['filter_firefightercategories'],
    'exclude' => true,
    'filter' => true,
    'inputType' => 'select',
    'foreignKey' => 'tl_firefighter_category.title',
    'eval' => ['multiple' => true, 'chosen' => true, 'tl_class' => 'clr w50'],
    'sql' => 'blob NULL',
];

$GLOBALS['TL_DCA']['tl_module']['fields']['firefighter_readerModule'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['firefighter_readerModule'],
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => ['tl_module_firefighter', 'getReaderModules'],
    'reference' => &$GLOBALS['TL_LANG']['tl_module'],
    'eval' => ['includeBlankOption' => true, 'tl_class' => 'w50'],
    'sql' => 'int(10) unsigned NOT NULL default 0',
];

/**
 * Class tl_module_firefighter.
 *
 * Provide miscellaneous methods that are used by the data configuration array.
 */
class tl_module_firefighter extends Backend
{
    /**
     * Import the back end user object.
     */
    public function __construct()
    {
        parent::__construct();
        $this->import(BackendUser::class, 'User');
    }

    /**
     * Return all firefighter templates as array.
     */
    public function getFirefighterTemplates(): array
    {
        return $this->getTemplateGroup('firefighter_');
    }

    /**
     * Get all firefighter archives and return them as array.
     */
    public function getFirefighterArchives(): array
    {
        if (!$this->User->isAdmin && !is_array($this->User->firefighter)) {
            return [];
        }

        $arrArchives = [];
        $objArchives = Database::getInstance()
            ->execute('SELECT id, title FROM tl_firefighter_archive ORDER BY title');

        while ($objArchives->next()) {
            if ($this->User->hasAccess($objArchives->id, 'firefighter')) {
                $arrArchives[$objArchives->id] = $objArchives->title;
            }
        }

        return $arrArchives;
    }

    /**
     * Get all firefighter reader modules and return them as array.
     */
    public function getReaderModules(): array
    {
        $arrModules = [];
        $objModules = Database::getInstance()
            ->execute("SELECT m.id, m.name, t.name AS theme FROM tl_module m LEFT JOIN tl_theme t ON m.pid=t.id WHERE m.type='firefighterreader' ORDER BY t.name, m.name");

        while ($objModules->next()) {
            $arrModules[$objModules->theme][$objModules->id] = $objModules->name.' (ID '.$objModules->id.')';
        }

        return $arrModules;
    }
}
