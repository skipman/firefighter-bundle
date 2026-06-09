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
use Contao\BackendUser;
use Contao\Database;
use Contao\DataContainer;
use Contao\DC_Table;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Skipman\FirefighterBundle\Helper\FirefighterHelper;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

$GLOBALS['TL_DCA']['tl_firefighter_category'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'enableVersioning' => true,
        'onload_callback' => [
            ['tl_firefighter_category', 'checkPermission'],
        ],
        'onsubmit_callback' => [
            [FirefighterHelper::class, 'updateCategoryScopeLevelFromDepartment'],
        ],
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
                'alias' => 'index',
            ],
        ],
        'backlink' => 'do=firefighter',
    ],

    'list' => [
        'sorting' => [
            'mode' => 1,
            'flag' => 1,
            'panelLayout' => 'sort,filter;search,limit',
            'fields' => ['departmentId', 'title'],
        ],
        'label' => [
            'fields' => ['departmentId','title'],
            'label_callback' => ['tl_firefighter_category', 'listItems'],
        ],
        'global_operations' => [
            'toggleNodes' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['toggleAll'],
                'href' => 'ptg=all',
                'class' => 'header_toggle',
            ],
            'all' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => [
                'label' => &$GLOBALS['TL_LANG']['tl_firefighter_category']['edit'],
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'copy' => [
                'label' => &$GLOBALS['TL_LANG']['tl_firefighter_category']['copy'],
                'href' => 'act=paste&amp;mode=copy',
                'icon' => 'copy.svg',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_firefighter_category']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.svg',
                'attributes' => 'onclick="if(!confirm(\'' . ($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null) . '\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_firefighter_category']['show'],
                'href' => 'act=show',
                'icon' => 'show.svg',
            ],
        ],
    ],

    'palettes' => [
        'default' => '{title_legend},departmentId,title,alias,scopeLevel;{publish_legend},published',
    ],

    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'pid' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'sorting' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'title' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter_category']['title'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'alias' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter_category']['alias'],
            'exclude' => true,
            'search' => true,            
            'inputType' => 'text',
            'eval' => ['rgxp' => 'alias', 'unique' => true, 'doNotCopy' => true, 'spaceToUnderscore' => true, 'maxlength' => 128, 'tl_class' => 'w50'],
            'save_callback' => [
                [FirefighterHelper::class, 'generateCategoryAlias'],
            ],
            'sql' => "varbinary(128) NOT NULL default ''",
        ],
        'scopeLevel' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter_category']['scopeLevel'],
            'exclude' => true,
            'inputType' => 'select',
            'options_callback' => [FirefighterHelper::class, 'getAllowedScopeLevelOptions'],
            'default' => 'FF',
            'reference' => &$GLOBALS['TL_LANG']['tl_firefighter_category']['scopeLevelOptions'],
            'eval' => ['mandatory' => true, 'tl_class' => 'w50'],
            'save_callback' => [
                [FirefighterHelper::class, 'filterScopeLevelFromDepartment'],
            ],
            'sql' => "varchar(16) NOT NULL default 'FF'",
        ],
        'departmentId' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter_category']['departmentId'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'options_callback' => [FirefighterHelper::class, 'getAllowedDepartmentContextOptions'],
            'eval' => ['mandatory' => true, 'includeBlankOption' => true, 'submitOnChange' => true, 'tl_class' => 'w50'],
            'save_callback' => [
                [FirefighterHelper::class, 'filterAllowedDepartmentContext'],
            ],
            'sql' => "int(10) unsigned NULL",
        ],
        'published' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter_category']['published'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
    ],
];

/**
 * Class tl_firefighter_category.
 */
class tl_firefighter_category extends Backend
{
    /**
     * Import the back end user object.
     */
    public function __construct()
    {
        parent::__construct();
        $this->import(BackendUser::class, 'User');
    }

    public function checkPermission(DataContainer $dc): void
    {
        $user = BackendUser::getInstance();

        if ($user->isAdmin) {
            return;
        }

        $allowedDepartmentIds = FirefighterHelper::getAllowedDepartmentContextIds($user);
        $this->restrictListToAllowedDepartments($allowedDepartmentIds);

        $act = (string) Input::get('act');
        $id = (int) Input::get('id');

        if ('' === $act || 'select' === $act || 'create' === $act) {
            return;
        }

        if (in_array($act, ['editAll', 'overrideAll', 'deleteAll'], true)) {
            $this->restrictCurrentIdsToAllowedDepartments($allowedDepartmentIds);

            return;
        }

        if ($id > 0 && !$this->canAccessCategory($id, $allowedDepartmentIds)) {
            throw new AccessDeniedException('Not enough permissions to access firefighter category ID ' . $id . '.');
        }
    }

    public function listItems(array $row): string
    {
        $label = FirefighterHelper::formatFirefighterCategoryLabel(
            (string) $row['title'],
            $this->getDepartmentName((int) $row['departmentId'])
        );

        return '<div class="tl_content_left">' . StringUtil::specialchars($label) . '</div>';
    }    

    /**
     * Auto-generate the firefighter category alias if it has not been set yet.
     *
     * @param mixed $varValue
     *
     */
    public function generateAlias($varValue, DataContainer $dc): string
    {
        $varValue = (string) $varValue;
        $autoAlias = false;

        // Generate alias if there is none
        if ('' === $varValue) {
            $autoAlias = true;
            $title = trim((string) Input::post('title'));
            $departmentId = (int) Input::post('departmentId');

            if ('' === $title && null !== $dc->activeRecord) {
                $title = (string) $dc->activeRecord->title;
            }

            if (0 === $departmentId && null !== $dc->activeRecord) {
                $departmentId = (int) $dc->activeRecord->departmentId;
            }

            $departmentName = $this->getDepartmentName($departmentId);
            $varValue = StringUtil::generateAlias(
                FirefighterHelper::formatFirefighterCategoryLabel($title, $departmentName)
            );

        }

        $objAlias = Database::getInstance()
            ->prepare('SELECT id FROM tl_firefighter_category WHERE alias=? AND id<>?')
            ->execute($varValue, (int) $dc->id);

        // Check whether the firefighter alias exists
        if ($objAlias->numRows && !$autoAlias) {
            throw new RuntimeException(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
        }

        // Add ID to alias
        if ($objAlias->numRows && $autoAlias) {
            $varValue .= '-'. $dc->id;
        }

        return $varValue;
    }

    private function restrictListToAllowedDepartments(array $allowedDepartmentIds): void
    {
        $allowedDepartmentIds = array_values(array_filter(array_map('intval', $allowedDepartmentIds)));

        if ([] === $allowedDepartmentIds) {
            $GLOBALS['TL_DCA']['tl_firefighter_category']['list']['sorting']['filter'][] = 'departmentId=0';

            return;
        }

        $GLOBALS['TL_DCA']['tl_firefighter_category']['list']['sorting']['filter'][] = 'departmentId IN(' . implode(',', $allowedDepartmentIds) . ')';
    }
    
    private function restrictCurrentIdsToAllowedDepartments(array $allowedDepartmentIds): void
    {
        /** @var SessionInterface $session */
        $session = System::getContainer()->get('request_stack')->getSession();
        $data = $session->all();

        $currentIds = array_map('intval', (array) ($data['CURRENT']['IDS'] ?? []));

        if ([] === $currentIds) {
            return;
        }

        $allowedCategoryIds = FirefighterHelper::getCategoryIdsByDepartmentIds($allowedDepartmentIds);
        $data['CURRENT']['IDS'] = array_values(array_intersect($currentIds, $allowedCategoryIds));

        $session->replace($data);
    }

    private function canAccessCategory(int $id, array $allowedDepartmentIds): bool
    {
        $objCategory = Database::getInstance()
            ->prepare('SELECT departmentId, title, alias FROM tl_firefighter_category WHERE id=?')
            ->limit(1)
            ->execute($id);

        if ($objCategory->numRows < 1) {
            return false;
        }

        $departmentId = (int) $objCategory->departmentId;

        if ($departmentId < 1) {
            return '' === trim((string) $objCategory->title)
                && '' === trim((string) $objCategory->alias);
        }

        return in_array($departmentId, array_map('intval', $allowedDepartmentIds), true);
    }

    private function getDepartmentName(int $departmentId): string
    {
        if ($departmentId < 1) {
            return '';
        }

        $objDepartment = Database::getInstance()
            ->prepare('SELECT ffname FROM tl_firefighter_departments WHERE id=?')
            ->limit(1)
            ->execute($departmentId);

        return $objDepartment->numRows ? (string) $objDepartment->ffname : '';
    }

}
