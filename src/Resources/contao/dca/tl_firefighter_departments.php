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
use Contao\Backend;
use Contao\Database;
use Contao\DataContainer;

$GLOBALS['TL_DCA']['tl_firefighter_departments'] = [
    'config' => [
        'dataContainer' => DC_Table::class,        
        'enableVersioning' => true,
        'switchToEdit' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary'
            ]
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => 1,
            'fields' => ['ffname'],
            'flag' => 1,
            'length' => 3,
            'panelLayout' => 'filter;sort,search,limit',
        ],
        'label' => [
            'fields' => ['ffnumber', 'ffname'],
            'format' => '%s (%s)',
        ],
        'global_operations' => [
            'all' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset();"'
            ]
        ],
        'operations' => [
            'edit' => [
                'label' => &$GLOBALS['TL_LANG']['tl_firefighter_departments']['edit'],
                'href' => 'act=edit',
                'icon' => 'edit.svg'
            ],
            'copy' => [
                'label' => &$GLOBALS['TL_LANG']['tl_firefighter_departments']['copy'],
                'href' => 'act=copy',
                'icon' => 'copy.svg'
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_firefighter_departments']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'{{conf}}\'))return false;Backend.getScrollOffset()"'
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_firefighter_departments']['show'],
                'href' => 'act=show',
                'icon' => 'show.svg'
            ]
        ]
    ],
    'palettes' => [
        'default' => '{department_legend},type,ffnumber,ffname,bfk,afk;'
                   . '{social_legend:hide},socialChannels;'
                   . '{fleet_legend:hide},fleet',
    ],
    'fields' => [
        'id' => [
            'sql' => "int(10) unsigned NOT NULL auto_increment"
        ],
        'tstamp' => [
            'sql' => ['type' => 'integer', 'unsigned' => true, 'default' => 0]
        ],
        'type' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter_departments']['type'],
            'inputType' => 'select',
            'options' => ['BFK', 'AFK', 'FF', 'BTF'],
            'default' => 'FF',
            'filter' => true,
            'eval' => ['mandatory' => true, 'maxlength' => 3,  'sorting' => true, 'search' => true, 'flag' => 3, 'lenght' => 3, 'tl_class' => 'w25'],
            'sql' => "varchar(3) NOT NULL default ''"
        ],
        'ffnumber' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter_departments']['ffnumber'],
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 10, 'rgxp' => 'alnum', 'unique' => true, 'sorting' => true, 'search' => true, 'tl_class' => 'w25'],
            'sql' => "varchar(10) NOT NULL default ''"
        ],
        'ffname' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter_departments']['ffname'],
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255,  'sorting' => true, 'search' => true, 'flag' => 3, 'lenght' => 3, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'bfk' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter_departments']['bfk'],
            'filter' => true,
            'inputType' => 'select',
            'options_callback' => ['tl_firefighter_departments', 'getBfkOptions'],
            'eval' => [
                'mandatory' => false, 
                'chosen' => true,
                'includeBlankOption' => true,
                'tl_class' => 'w25',
                'customMandatory' => ['tl_firefighter_departments', 'checkMandatoryBfk'],
                'submitOnChange' => true, // AJAX-Auslösung bei Änderung
                'ajaxCallback' => ['tl_firefighter_departments', 'updateAfkOptions'] // AJAX-Callback
            ],
            'sql' => "int(10) unsigned NULL default NULL",
        ],
        'afk' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter_departments']['afk'],
            'filter' => true,
            'inputType' => 'select',
            'options_callback' => ['tl_firefighter_departments', 'getAfkOptions'],
            'eval' => [
                'mandatory' => false, // Default: false
                'chosen' => true,
                'includeBlankOption' => true,
                'tl_class' => 'w25',
                'customMandatory' => ['tl_firefighter_departments', 'checkMandatoryBfk']
            ],
            'sql' => "int(10) unsigned NULL default NULL",
            'dependsOn' => 'bfk' // Optional, um die Abhängigkeit anzuzeigen
        ],
        'socialChannels' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter_departments']['socialChannels'],
            'inputType' => 'multiColumnWizard',
            'eval' => [
                'tl_class' => 'clr',
                'columnFields' => [
                    'platform' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter_departments']['platform'],
                        'inputType' => 'select',
                        'options' => ['Webseite', 'Facebook', 'Instagram', 'Youtube', 'X (Twitter)', 'TikTok'],
                        'eval' => ['style' => 'width:180px', 'chosen' => true, 'includeBlankOption' => true],
                    ],
                    'url' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter_departments']['url'],
                        'inputType' => 'text',
                        'eval' => ['rgxp' => 'url', 'style' => 'width:400px', 'tl_class' => 'clr'],
                    ],
                    'linkTitle' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter_departments']['linkTitle'],
                        'inputType' => 'text',
                        'eval' => ['style' => 'width:400px'],
                    ],
                ],
            ],
            'sql' => "blob NULL"
        ],
        'fleet' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter_departments']['vehicles'],
            'inputType' => 'multiColumnWizard',
            'eval' => [                
                'columnFields' => [
                    'vehicle' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter_departments']['vehicle'],
                        'inputType' => 'select',
                        'options_callback' => ['tl_firefighter_departments', 'getVehicles'],
                        'eval' => ['class' => 'unhideLabel', 'style' => 'width:180px', 'chosen' => true, 'includeBlankOption' => true],
                    ],
                    'link' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter_departments']['link'],
                        'inputType' => 'pageTree',
                        'eval' => ['fieldType' => 'radio']
                    ],
                    'url' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter_departments']['url'],
                        'inputType' => 'text',
                        'eval' => ['rgxp' => 'url', 'style' => 'width:400px'],
                    ],
                ],
            ],
            'sql' => 'blob NULL'
        ],
    ],
];

class tl_firefighter_departments extends Backend
{
    public function getVehicles()
    {
        $vehicles = [];
        $result = Database::getInstance()->execute("SELECT id, vehicle_short FROM tl_firefighter_vehicles ORDER BY vehicle_short ASC");

        while ($result->next()) {
            $vehicles[$result->id] = $result->vehicle_short;
        }

        return $vehicles;
    }
    public function getBfkOptions()
    {
        $bfk = [];
        $result = Database::getInstance()->prepare("SELECT id, ffname FROM tl_firefighter_departments WHERE type='BFK' ORDER BY ffname ASC")
                                         ->execute();

        while ($result->next()) {
            $bfk[$result->id] = $result->ffname;
        }

        return $bfk;
    }

    public function getAfkOptions(DataContainer $dc)
    {
        $afk = [];
        if ($dc->activeRecord->bfk) {
            $result = Database::getInstance()->prepare("SELECT id, ffname FROM tl_firefighter_departments WHERE type='AFK' AND bfk=? ORDER BY ffname ASC")
                                            ->execute($dc->activeRecord->bfk);

            while ($result->next()) {
                $afk[$result->id] = $result->ffname;
            }
        }
        return $afk;
    }

    public function checkMandatoryBfk($value, DataContainer $dc)
    {
        // Check the value of 'type'
        if ($dc->activeRecord->type !== 'BFK') {
            // Wenn 'type' nicht BFK ist, setze mandatory auf true
            if (empty($value)) {
                throw new \Exception("Das Feld 'BFK' ist für diesen Eintrag erforderlich.");
            }
        }

        // If 'type' is BFK, mandatory is not required (defaults to false)
        return $value;
    }

    public function updateAfkOptions(DataContainer $dc)
    {
        if ($dc->activeRecord->bfk) {
            $afkOptions = $this->getAfkOptions($dc);

            $arrReturn = [];
            foreach ($afkOptions as $value => $label) {
                $arrReturn[] = ['value' => $value, 'label' => $label];
            }

            // Return as JSON for the AJAX request
            echo json_encode($arrReturn);
            exit;
        }
    }
}
