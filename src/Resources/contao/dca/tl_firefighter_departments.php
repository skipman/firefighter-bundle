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
use Contao\Backend;
use Contao\Database;
use Contao\DataContainer;
use Contao\Input;
use Contao\System;
use Contao\BackendUser;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Skipman\FirefighterBundle\Helper\FirefighterHelper;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

$GLOBALS['TL_DCA']['tl_firefighter_departments'] = [
    'config' => [
        'dataContainer' => DC_Table::class,        
        'enableVersioning' => true,
        'switchToEdit' => true,
        'onload_callback' => [
            ['tl_firefighter_departments', 'checkPermission'],
        ],
        'sql' => [
            'keys' => [
                'id' => 'primary'
            ]
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => 1,
            'fields' => ['ffnumber', 'ffname'],
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
        'default' => '{department_legend},type,ffnumber,ffname,bfk,afk,ua;'
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
            'eval' => [
                'mandatory' => true,
                'maxlength' => 3,
                'sorting' => true,
                'search' => true,
                'flag' => 3,
                'lenght' => 3,
                'tl_class' => 'w25',
                ],
            'sql' => "varchar(3) NOT NULL default ''"
        ],
        'ffnumber' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter_departments']['ffnumber'],
            'inputType' => 'text',
            'eval' => [
                'mandatory' => true,
                'maxlength' => 10, 
                'rgxp' => 'alnum', 
                'unique' => true, 
                'sorting' => true, 
                'search' => true, 
                'tl_class' => 'w25',
                ],
            'sql' => "varchar(10) NOT NULL default ''"
        ],
        'ffname' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter_departments']['ffname'],
            'inputType' => 'text',
            'eval' => [
                'mandatory' => true, 
                'maxlength' => 255,  
                'sorting' => true, 
                'search' => true, 
                'flag' => 3, 
                'lenght' => 3, 
                'tl_class' => 'w50',
                ],
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
                'submitOnChange' => true,
            ],
            'save_callback' => [
                ['tl_firefighter_departments', 'checkMandatoryBfk'],
            ],
            'sql' => "int(10) unsigned NULL default NULL",
        ],
        'afk' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter_departments']['afk'],
            'filter' => true,
            'inputType' => 'select',
            'options_callback' => ['tl_firefighter_departments', 'getAfkOptions'],
            'eval' => [
                'mandatory' => false,
                'chosen' => true,
                'includeBlankOption' => true,
                'tl_class' => 'w25',
            ],
            'save_callback' => [
                ['tl_firefighter_departments', 'checkMandatoryBfk'],
            ],
            'sql' => "int(10) unsigned NULL default NULL",
            'dependsOn' => 'bfk',
        ],        
        'ua' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter_departments']['ua'],
            'filter' => true,
            'inputType' => 'select',
            'options' => ['1', '2', '3', '4', '5', '6', '7', '8'],
            'eval' => [
                'mandatory' => false, // Default: false
                'chosen' => true,
                'includeBlankOption' => true,
                'tl_class' => 'w25',
            ],
            'sql' => "int(1) unsigned NULL default NULL",
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
                        'options' => ['Webseite', 'Facebook', 'Instagram', 'Youtube', 'X (Twitter)', 'TikTok', 'WhatsApp'],
                        'eval' => [
                            'wrapper_style' => 'width:20%', 
                            'style' => 'width:100%',
                            'includeBlankOption' => true,
                            ],
                    ],
                    'urlSM' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter_departments']['urlSM'],
                        'inputType' => 'text',
                        'eval' => [
                            'rgxp' => 'url', 
                            'wrapper_style' => 'width:40%', 
                            'style' => 'width:100%', 
                            'tl_class' => 'clr',
                            ],
                    ],
                    'linkTitle' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter_departments']['linkTitle'],
                        'inputType' => 'text',
                        'eval' => [
                            'wrapper_style' => 'width:40%', 
                            'style' => 'width:100%',
                            ],
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
                        'eval' => [
                            'class' => 'unhideLabel',
                            'wrapper_style' => 'width:20%', 
                            'style' => 'width:100%',
                            'includeBlankOption' => true,
                            ],
                    ],
                    'link' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter_departments']['link'],
                        'inputType' => 'pageTree',
                        'eval' => [
                            'fieldType' => 'radio', 
                            'wrapper_style' => 'width:40%', 
                            'style' => 'width:100%',
                            ]
                    ],
                    'url' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter_departments']['url'],
                        'inputType' => 'text',
                        'eval' => [
                            'rgxp' => 'url', 
                            'wrapper_style' => 'width:40%', 
                            'style' => 'width:100%',
                            ],
                    ],
                ],
            ],
            'sql' => 'blob NULL'
        ],
    ],
];

class tl_firefighter_departments extends Backend
{
    public function checkPermission(DataContainer $dc): void
    {
        $user = BackendUser::getInstance();

        if ($user->isAdmin) {
            return;
        }

        $allowedIds = FirefighterHelper::getAllowedDepartmentIds($user);
        $this->restrictListToAllowedDepartments($allowedIds);

        $act = (string) Input::get('act');
        $id = (int) Input::get('id');

        if ('' === $act) {
            return;
        }

        switch ($act) {
            case 'select':
                return;

            case 'edit':
            case 'show':
                if ($id < 1 || !in_array($id, $allowedIds, true)) {
                    throw new AccessDeniedException('Not enough permissions to access fire department ID ' . $id . '.');
                }

                return;

            case 'editAll':
            case 'overrideAll':
                $this->restrictCurrentIdsToAllowedDepartments($allowedIds);

                return;

            case 'create':
            case 'copy':
            case 'delete':
            case 'copyAll':
            case 'deleteAll':
                throw new AccessDeniedException('Not enough permissions to ' . $act . ' fire departments.');
        }

        if ($id > 0 && !in_array($id, $allowedIds, true)) {
            throw new AccessDeniedException('Not enough permissions to access fire department ID ' . $id . '.');
        }
    }

    private function restrictListToAllowedDepartments(array $allowedIds): void
    {
        $allowedIds = array_values(array_filter(array_map('intval', $allowedIds)));

        if ([] === $allowedIds) {
            $GLOBALS['TL_DCA']['tl_firefighter_departments']['list']['sorting']['filter'][] = 'id=0';

            return;
        }

        $GLOBALS['TL_DCA']['tl_firefighter_departments']['list']['sorting']['filter'][] = 'id IN(' . implode(',', $allowedIds) . ')';
    }

    private function restrictCurrentIdsToAllowedDepartments(array $allowedIds): void
    {
        /** @var SessionInterface $session */
       $session = System::getContainer()->get('request_stack')->getSession();
        $data = $session->all();

        $currentIds = array_map('intval', (array) ($data['CURRENT']['IDS'] ?? []));
        $data['CURRENT']['IDS'] = array_values(array_intersect($currentIds, $allowedIds));

        $session->replace($data);
    }

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

    public function checkMandatoryBfk($value, DataContainer $dc)
    {
        $type = $dc->activeRecord->type ?? Input::post('type');

        if ('BFK' !== $type && empty($value)) {
            throw new \Exception("Das Feld 'BFK' ist für diesen Eintrag erforderlich.");
        }

        return $value;
    }

    public function getAfkOptions(DataContainer $dc): array
    {
        $afk = [];
        $activeRecord = $dc->activeRecord ?? null;
        $bfk = $activeRecord->bfk ?? Input::post('bfk');

        if (null === $activeRecord) {
            $result = Database::getInstance()
                ->execute("SELECT id, ffname
                            FROM tl_firefighter_departments
                            WHERE type='AFK'
                        ORDER BY ffname ASC");
        } elseif ($bfk) {
            $result = Database::getInstance()
                ->prepare("SELECT id, ffname
                            FROM tl_firefighter_departments
                            WHERE type='AFK'
                            AND bfk=?
                        ORDER BY ffname ASC")
                ->execute($bfk);
        } else {
            return [];
        }

        while ($result->next()) {
            $afk[$result->id] = $result->ffname;
        }

        return $afk;
    }
}
