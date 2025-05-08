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

use Contao\Image;
use Contao\Input;
use Contao\Config;
use Contao\System;
use Contao\Backend;
use Contao\Database;
use Contao\DC_Table;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\BackendUser;
use Contao\DataContainer;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Skipman\FirefighterBundle\Helper\FirefighterHelper;
use Skipman\FirefighterBundle\Models\FirefighterArchiveModel;

System::loadLanguageFile('tl_content');

$GLOBALS['TL_DCA']['tl_firefighter']['fields']['headline']['save_callback'][] = ['tl_firefighter', 'generateHeadline'];
$GLOBALS['TL_DCA']['tl_firefighter']['fields']['alias']['save_callback'][] = ['tl_firefighter', 'generateAlias'];

$GLOBALS['TL_DCA']['tl_firefighter'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_firefighter_archive',
        'ctable' => ['tl_content'],
        'switchToEdit' => true,
        'enableVersioning' => true,
        'onsubmit_callback' => [
            ['tl_firefighter', 'updateHeadlineAndAlias'],
            ['tl_firefighter', 'createChildElement'],
        ],
        'oninvalidate_cache_tags_callback' => [
            ['tl_firefighter', 'addSitemapCacheInvalidationTag'],
        ],
        'onload_callback' => [
            ['tl_firefighter', 'checkPermission'],
        ],
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'alias' => 'index',
                'pid,published,featured,start,stop' => 'index',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => [
                'membersHomebase',
                'membersLastname'
            ],
            'panelLayout' => 'filter;sort,search,limit',
            'headerFields' => ['title'],
            'defaultSearchField' => 'membersLastname',
            'child_record_callback' => ['tl_firefighter', 'listItems'],
            'paste_button_callback' => ['tl_firefighter', 'pasteElement'],
            'group_callback' => ['tl_firefighter', 'getGroupHeader'],
        ],
        'label' => [
            'fields' => ['membersLastname', 'membersFirstname', 'membersRank', 'membersHomebase'],
            'format' => '%s %s, %s  [%s]',
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
            'editheader' => [
                'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['editmeta'],
                'href'  => 'act=edit',
                'icon'  => 'edit.svg',
            ],
            'edit' => [
                'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['edit'],
                'href'  => 'table=tl_content',
                'icon'  => 'children.svg',
            ],
            'copy',
            'cut',
            'delete',
            'toggle' => [
                'href' => 'act=toggle&amp;field=published',
                'icon' => 'visible.svg',
                'showInHeader' => true,
            ],
            'feature' => [
                'href' => 'act=toggle&amp;field=featured',
                'icon' => 'featured.svg',
            ],
            'show',
        ],
    ],
    'palettes' => [
        '__selector__' => ['addImage', 'source', 'overwriteMeta'],
        'default' => '{title_legend},membersFirstname,membersLastname,membersRank,membersRankHonory,membersHomebase,membersSince,firefightercategories;'
                   . '{image_legend:hide},addImage;'
                   . '{ffMemberFunctionLocal_legend:hide},membersFunctionLocalWizard;'
                   . '{ffMemberFunctionSection_legend:hide},membersFunctionSectionWizard;'
                   . '{ffMemberContact_legend:hide},membersEmail,membersPhone;'
                   . '{expert_legend:hide},cssClass,noComments,featured;'
                   . '{publish_legend},published,start,stop',
    ],
    'subpalettes' => [
        'addImage' => 'singleSRC,size,floating,fullsize,overwriteMeta',
        'source_internal' => 'jumpTo',
        'source_article' => 'articleId',
        'source_external' => 'url,target',
        'overwriteMeta' => 'alt,imageTitle,imageUrl,caption',
    ],
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'pid' => [
            'foreignKey' => 'tl_firefighter_archive.title',
            'sql' => "int(10) unsigned NOT NULL default 0",
            'relation' => ['type' => 'belongsTo', 'load' => 'lazy']
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'sorting' => [
            'label' => &$GLOBALS['TL_LANG']['MSC']['sorting'],
            'sorting' => false,
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'headline' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['headline'],
            'exclude' => true,
            'flag' => 1,
            'inputType' => 'text',
            'eval' => ['mandatory' => false, 'doNotCopy' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'alias' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['alias'],
            'exclude' => true,
            'search' => false,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'alias', 'unique' => true, 'maxlength' => 128, 'tl_class' => 'w50'],
            'sql' => "varchar(255) BINARY NOT NULL default ''",
        ],
        'membersFirstname' => [
            'exclude' => true,
            'search' => true,
            'flag' => 1,
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 255,
                'tl_class' => 'w25',
            ],
            'sql' => "varchar(255) NOT NULL default ''",
        ],        
        'membersLastname' => [
            'exclude' => true,
            'search' => true,
            'sorting' => true,
            'flag' => 1,
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 255,
                'tl_class' => 'w25',
            ],
            'sql' => "varchar(255) NOT NULL default ''",
        ],        
        'membersRank' => [
            'exclude' => true,
            'search' => true,
            'flag' => 1,
            'inputType' => 'select',
            'foreignKey' => 'tl_firefighter_ranks.rank_short',
            'options_callback' => [FirefighterHelper::class, 'getRankShortOptions'],
            'eval' => [
                'maxlength' => 255,
                'chosen' => true,
                'includeBlankOption' => true,
                'tl_class' => 'w25',
            ],
            'sql' => "varchar(255) NOT NULL default ''",
        ],        
        'membersRankHonory' => [
            'exclude' => true,
            'search' => false,
            'flag' => 1,
            'inputType' => 'checkbox',
            'eval' => [
                'isBoolean' => true,
                'tl_class' => 'm12 w25'
            ],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'membersHomebase' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['membersHomebase'],
            'exclude' => true,
            'search' => false,
            'sorting' => true,
            'filter' => true,
            'flag' => 11,
            'inputType' => 'select',
            'options_callback' => [FirefighterHelper::class, 'getDepartments'],
            'eval' => [
                'maxlength' => 255,
                'chosen' => true,
                'includeBlankOption' => true,
                'tl_class' => 'w25',
            ],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'membersSince' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['membersSince'],
            'exclude' => true,
            'search' => false,
            'flag' => 1,
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 10,
                'tl_class' => 'w25',
            ],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'firefightercategories' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['firefightercategories'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'foreignKey' => 'tl_firefighter_category.title',
            'eval' => ['multiple' => true, 'chosen' => true, 'tl_class' => 'w50'],
            'sql' => 'blob NULL',
        ],         
        'addImage' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['addImage'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'membersFunctionLocalWizard' => [
            'exclude' => true,
            'inputType' => 'multiColumnWizard',
            'eval' => [
                'columnFields' => [
                    'membersFunctionLocal' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['membersFunctionLocal'],
                        'inputType' => 'select',
                        'options_callback' => [FirefighterHelper::class, 'getFunctionLocalShortOptions'],
                        'eval' => ['includeBlankOption' => true, 'chosen' => true, 'style' => 'width:250px']
                    ],
                    'membersFunctionLocalFromMonth' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['membersFunctionLocalFromMonth'],
                        'inputType' => 'select',
                        'options' => ['01','02','03','04','05','06','07','08','09','10','11','12'],
                        'eval' => ['includeBlankOption' => true, 'style' => 'width:50px',]
                    ],
                    'membersFunctionLocalFromYear' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['membersFunctionLocalFromYear'],
                        'inputType' => 'text',
                        'eval' => ['style' => 'width:80px', 'maxlength' => 4,]
                    ],
                    'membersFunctionLocalUntilMonth' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['membersFunctionLocalUntilMonth'],
                        'inputType' => 'select',
                        'options' => ['01','02','03','04','05','06','07','08','09','10','11','12'],
                        'eval' => ['includeBlankOption' => true, 'style' => 'width:50px',]
                    ],
                    'membersFunctionLocalUntilYear' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['membersFunctionLocalUntilYear'],
                        'inputType' => 'text',
                        'eval' => ['style' => 'width:80px', 'maxlength' => 4,]
                    ]
                ],
                'tl_class' => 'clr',
                'minCount' => 0,
            ],
            'sql' => "blob NULL"
        ],        
        'membersFunctionSectionWizard' => [
            'exclude' => true,
            'inputType' => 'multiColumnWizard',
            'eval' => [
                'columnFields' => [
                    'membersFunctionSection' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['membersFunctionSection'],
                        'inputType' => 'select',
                        'options_callback' => [FirefighterHelper::class, 'getFunctionSectionShortOptions'],
                        'eval' => ['includeBlankOption' => true, 'chosen' => true, 'style' => 'width:250px']
                    ],
                    'membersFunctionSectionFromMonth' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['membersFunctionSectionFromMonth'],
                        'inputType' => 'select',
                        'options' => ['01','02','03','04','05','06','07','08','09','10','11','12'],
                        'eval' => ['includeBlankOption' => true,'style' => 'width:50px',]
                    ],
                    'membersFunctionSectionFromYear' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['membersFunctionSectionFromYear'],
                        'inputType' => 'text',
                        'eval' => ['style' => 'width:80px', 'maxlength' => 4,]
                    ],
                    'membersFunctionSectionUntilMonth' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['membersFunctionSectionUntilMonth'],
                        'inputType' => 'select',
                        'options' => ['01','02','03','04','05','06','07','08','09','10','11','12'],
                        'eval' => ['includeBlankOption' => true, 'style' => 'width:50px',]
                    ],
                    'membersFunctionSectionUntilYear' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['membersFunctionSectionUntilYear'],
                        'inputType' => 'text',
                        'eval' => ['style' => 'width:80px', 'maxlength' => 4,]
                    ],
                ],
                'tl_class' => 'clr',
                'minCount' => 0,
            ],
            'sql' => "blob NULL"
        ],
        'membersEmail' => [
            'exclude' => true,
            'search' => false,
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 255,
                'rgxp' => 'email',
                'decodeEntities' => true,
                'tl_class' => 'w50',
            ],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'membersPhone' => [
            'exclude' => true,
            'search' => false,
            'flag' => 1,
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 255,
                'tl_class' => 'w50',
            ],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'pageTitle' => [
            'exclude' => true,
            'search' => false,
            'inputType' => 'text',
            'eval' => array('maxlength' => 255, 'decodeEntities' => true, 'tl_class' => 'w50'),
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'robots' => [
            'exclude' => true,
            'search' => false,
            'inputType' => 'select',
            'options' => ['index,follow', 'index,nofollow', 'noindex,follow', 'noindex,nofollow'],
            'eval' => ['tl_class' => 'w50', 'includeBlankOption' => true],
            'sql' => "varchar(32) NOT NULL default ''"
        ],
        'description' => [
            'exclude' => true,
            'search' => false,
            'inputType' => 'textarea',
            'eval' => array('style' => 'height:60px', 'decodeEntities' => true, 'tl_class' => 'clr'),
            'sql' => "text NULL"
        ],
        'serpPreview' => [
            'label' => &$GLOBALS['TL_LANG']['MSC']['serpPreview'],
            'exclude' => true,
            'inputType' => 'serpPreview',
            'eval' => [
                'url_callback' => ['tl_firefighter', 'getSerpUrl'],
                'title_tag_callback' => ['tl_firefighter', 'getTitleTag'],
                'titleFields' => ['pageTitle', 'headline'],
                'descriptionFields' => ['description', 'teaser']
            ],
            'sql' => null
        ],
        'teaser' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['teaser'],
            'exclude' => true,
            'search' => false,
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE', 'helpwizard' => true, 'tl_class' => 'clr'],
            'explanation' => 'insertTags',
            'sql' => 'mediumtext NULL',
        ],
        'date' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['date'],
            'default' => time(),
            'exclude' => true,
            'filter' => false,
            'sorting' => false,
            'flag' => 8,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'date', 'doNotCopy' => true, 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'overwriteMeta' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['overwriteMeta'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['submitOnChange' => true, 'tl_class' => 'w50 clr'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'singleSRC' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['singleSRC'],
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => ['fieldType' => 'radio', 'filesOnly' => true, 'extensions' => Config::get('validImageTypes'), 'mandatory' => true],
            'sql' => 'binary(16) NULL',
        ],
        'alt' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['alt'],
            'exclude' => true,
            'search' => false,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'imageTitle' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['imageTitle'],
            'exclude' => true,
            'search' => false,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'size' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['size'],
            'exclude' => true,
            'inputType' => 'imageSize',
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => ['rgxp' => 'natural', 'includeBlankOption' => true, 'nospace' => true, 'helpwizard' => true, 'tl_class' => 'w50'],
            'options_callback' => static function () {
                return System::getContainer()->get('contao.image.sizes')->getOptionsForUser(BackendUser::getInstance());
            },
            'sql' => "varchar(128) COLLATE ascii_bin NOT NULL default ''",
        ],
        'imageUrl' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['imageUrl'],
            'exclude' => true,
            'search' => false,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'url', 'decodeEntities' => true, 'maxlength' => 255, 'dcaPicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'fullsize' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['fullsize'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 m12'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'caption' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['caption'],
            'exclude' => true,
            'search' => false,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'allowHtml' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'floating' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['floating'],
            'default' => 'above',
            'exclude' => true,
            'inputType' => 'radioTable',
            'options' => ['above', 'left', 'right', 'below'],
            'eval' => ['cols' => 4, 'tl_class' => 'w50'],
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'sql' => "varchar(12) NOT NULL default ''",
        ],
        'source' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['source'],
            'default' => 'default',
            'exclude' => true,
            'filter' => false,
            'inputType' => 'radio',
            'options_callback' => ['tl_firefighter', 'getSourceOptions'],
            'reference' => &$GLOBALS['TL_LANG']['tl_firefighter'],
            'eval' => ['submitOnChange' => true, 'helpwizard' => true],
            'sql' => "varchar(12) NOT NULL default ''",
        ],
        'jumpTo' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['jumpTo'],
            'exclude' => true,
            'inputType' => 'pageTree',
            'foreignKey' => 'tl_page.title',
            'eval' => ['mandatory' => true, 'fieldType' => 'radio'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
            'relation' => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],
        'articleId' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['articleId'],
            'exclude' => true,
            'inputType' => 'select',
            'options_callback' => ['tl_firefighter', 'getArticleAlias'],
            'eval' => ['chosen' => true, 'mandatory' => true],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'url' => [
            'label' => &$GLOBALS['TL_LANG']['MSC']['url'],
            'exclude' => true,
            'search' => false,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'decodeEntities' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'target' => [
            'label' => &$GLOBALS['TL_LANG']['MSC']['target'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 m12'],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'cssClass' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['cssClass'],
            'exclude' => true,
            'inputType' => 'text',
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'published' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['published'],
            'exclude' => true,
            'filter' => true,
            'toggle' => true,
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'start' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['start'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
        'stop' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['stop'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
        'featured' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['featured'],
            'exclude' => true,
            'filter' => false,
            'toggle' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50', 'doNotCopy' => true],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
    ],
];

class tl_firefighter extends Backend
{
    public function __construct()
    {
        parent::__construct();
        $this->import(BackendUser::class, 'User');
    }

    /**
     * Check permissions to edit table tl_firefighter
     *
     * @throws AccessDeniedException
     */
    public function checkPermission(): void
    {
        if ($this->User->isAdmin) {
            return;
        }

        // Set the root IDs
        if (empty($this->User->firefighter) || !is_array($this->User->firefighter)) {
            $root = array(0);
        } else {
            $root = $this->User->firefighter;
        }

        $id = Input::get('id');

        // Check current action
        switch (Input::get('act')) {
            case 'paste':
            case 'select':
                // Check CURRENT_ID
                if (!in_array(Input::get('id'), $root, true)) {
                    throw new AccessDeniedException('Not enough permissions to access firefighter archive ID ' . $id . '.');
                }
                break;

            case 'create':
                if (!Input::get('pid') || !in_array(Input::get('pid'), $root, true)) {
                    throw new AccessDeniedException('Not enough permissions to create firefighter items in firefighter archive ID ' . Input::get('pid') . '.');
                }
                break;

            case 'cut':
            case 'copy':
                if (Input::get('act') === 'cut' && Input::get('mode') === 1) {
                    $objArchive = Database::getInstance()
                        ->prepare("SELECT pid FROM tl_firefighter WHERE id=?")
                        ->limit(1)
                        ->execute(Input::get('pid'));

                    if ($objArchive->numRows < 1) {
                        throw new AccessDeniedException('Invalid firefighter item ID ' . Input::get('pid') . '.');
                    }

                    $pid = $objArchive->pid;
                } else {
                    $pid = Input::get('pid');
                }

                if (!in_array($pid, $root, true)) {
                    throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' firefighter item ID ' . $id . ' to firefighter archive ID ' . $pid . '.');
                }
            // no break

            case 'edit':
            case 'show':
            case 'delete':
            case 'toggle':
            case 'feature':
                $objArchive = Database::getInstance()
                    ->prepare("SELECT pid FROM tl_firefighter WHERE id=?")
                    ->limit(1)
                    ->execute($id);

                if ($objArchive->numRows < 1) {
                    throw new AccessDeniedException('Invalid firefighter item ID ' . $id . '.');
                }

                if (!in_array($objArchive->pid, $root, true)) {
                    throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' firefighter item ID ' . $id . ' of firefighter archive ID ' . $objArchive->pid . '.');
                }
                break;

            case 'editAll':
            case 'deleteAll':
            case 'overrideAll':
            case 'cutAll':
            case 'copyAll':
                if (!in_array($id, $root, true)) {
                    throw new AccessDeniedException('Not enough permissions to access firefighter archive ID ' . $id . '.');
                }

                $objArchive = Database::getInstance()
                    ->prepare("SELECT id FROM tl_firefighter WHERE pid=?")
                    ->execute($id);

                /** @var SessionInterface $objSession */
                $objSession = System::getContainer()->get('session');

                $session = $objSession->all();
                $session['CURRENT']['IDS'] = array_intersect((array)$session['CURRENT']['IDS'], $objArchive->fetchEach('id'));
                $objSession->replace($session);
                break;

            default:
                if (Input::get('act')) {
                    throw new AccessDeniedException('Invalid command "' . Input::get('act') . '".');
                }

                if (!in_array($id, $root, true)) {
                    throw new AccessDeniedException('Not enough permissions to access firefighter archive ID ' . $id . '.');
                }
                break;
        }
    }

    /**
     * Add the type of input field.
     *
     * @param array $arrRow
     *
     * @return string
     */
    public function listItems($arrRow): string
    {
        $department = $this->getDepartmentName($arrRow['membersHomebase']);
        $membersRankShortAbbr = $this->getMembersRankShortAbbr($arrRow['membersRank'], $arrRow['membersRankHonory']);
        
        return '<div class="tl_content_left">' . $arrRow['membersLastname']. " " . $arrRow['membersFirstname'] . ", " . $membersRankShortAbbr . ' <span style="color:#999;padding-left:3px">[' . $department . ']</span></div>';
    }

    public function getDepartmentName($homebaseId): string
    {
        $result = Database::getInstance()
            ->prepare("SELECT ffname FROM tl_firefighter_departments WHERE id=?")
            ->execute($homebaseId);

        return $result->numRows ? $result->ffname : '';
    }
/*
    protected function getDepartmentDetails($homebaseId): string
    {
        $result = Database::getInstance()
            ->prepare("SELECT ffnumber, ffname FROM tl_firefighter_departments WHERE id=?")
            ->execute($homebaseId);

        return $result->numRows ? $result->ffnumber . ' - ' . $result->ffname : '';
    }

    public function getGroupHeader($homebaseId, $mode, $field, $row, $dc): string
    {
        $departmentDetails = $this->getDepartmentDetails($homebaseId);
        return '<div class="tl_content_header">' . $departmentDetails . '</div>';
    }
*/
    /**
     * Get the short abbreviation of the rank based on the ID.
     *
     * @param int $membersRankId
     * @param bool $rankHonory
     * @return string
     */
    protected function getMembersRankShortAbbr($membersRankId, $rankHonory): string
    {
        $membersRankShortAbbr = Database::getInstance()
            ->prepare("SELECT rank_short FROM tl_firefighter_ranks WHERE id=?")
            ->execute($membersRankId);

        if (!$membersRankShortAbbr->numRows) {
            return '';
        }

        return $rankHonory ? 'E' . $membersRankShortAbbr->rank_short : $membersRankShortAbbr->rank_short;
    }

    

    /**
     * Auto-generate the firefighter alias if it has not been set yet.
     *
     * @param mixed $varValue
     *
     * @param DataContainer $dc
     * @return string
     * @throws Exception
     */
    public function generateAlias($varValue, DataContainer $dc)
    {
        $aliasExists = function (string $alias) use ($dc): bool {
            return Database::getInstance()
                    ->prepare("SELECT id FROM tl_firefighter WHERE alias=? AND id!=?")
                    ->execute($alias, $dc->id)->numRows > 0;
        };

        // Generate alias if there is none
        if (!$varValue) {
            $headline = $this->generateHeadline($dc->activeRecord->headline, $dc);
            $varValue = System::getContainer()->get('contao.slug')->generate($headline, FirefighterArchiveModel::findByPk($dc->activeRecord->pid)->jumpTo, $aliasExists);
        } elseif (preg_match('/^[1-9]\d*$/', $varValue)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasNumeric'], $varValue));
        } elseif ($aliasExists($varValue)) {
            throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
        }

        return $varValue;
    }

    /**
     * Auto-generate the headline based on the members' details.
     *
     * @param mixed $varValue
     * @param DataContainer $dc
     * @return string
     */
    public function generateHeadline($varValue, DataContainer $dc)
    {
        if ($dc->activeRecord) {
            $membersFirstname = $dc->activeRecord->membersFirstname;
            $membersLastname = $dc->activeRecord->membersLastname;
            $membersRank = $dc->activeRecord->membersRank ? $this->getRankName($dc->activeRecord->membersRank, $dc->activeRecord->membersRankHonory) : '';

            $varValue = sprintf('%s %s %s', $membersRank, $membersFirstname, $membersLastname);
        }

        return $varValue;
    }

    /**
     * Update headline and alias on form submission.
     *
     * @param DataContainer $dc
     */
    public function updateHeadlineAndAlias(DataContainer $dc): void
    {
        if ($dc->activeRecord) {
            $headline = $this->generateHeadline($dc->activeRecord->headline, $dc);

            Database::getInstance()->prepare("UPDATE tl_firefighter SET headline=? WHERE id=?")
                ->execute($headline, $dc->id);

            // Re-fetch the active record to get the updated headline
            $activeRecord = Database::getInstance()->prepare("SELECT * FROM tl_firefighter WHERE id=?")
                ->execute($dc->id)->fetchAssoc();

            $alias = $this->generateAlias($activeRecord['alias'], $dc);

            Database::getInstance()->prepare("UPDATE tl_firefighter SET alias=? WHERE id=?")
                ->execute($alias, $dc->id);
        }
    }

    /**
     * Create a child element for the saved firefighter record.
     *
     * @param DataContainer $dc
     */
    public function createChildElement(DataContainer $dc): void
    {
        if ($dc->activeRecord) {
            $firefighterId = $dc->activeRecord->id;

            // Check if a child element already exists
            $existingContent = Database::getInstance()
                ->prepare("SELECT id FROM tl_content WHERE pid = ? AND ptable = 'tl_firefighter'")
                ->execute($firefighterId);

            if (!$existingContent->numRows) {
                // Insert a new child element
                Database::getInstance()->prepare("
                    INSERT INTO tl_content (pid, ptable, type, text, tstamp)
                    VALUES (?, 'tl_firefighter', 'text', '.', ?)
                ")->execute($firefighterId, time());
            }
        }
    }


    protected function getRankName($rankId, $rankHonory)
    {
        $rank = Database::getInstance()->prepare("SELECT rank_short FROM tl_firefighter_ranks WHERE id=?")
                                       ->execute($rankId);

        if (!$rank->numRows) {
            return '';
        }

        return $rankHonory ? 'E' . $rank->rank_short : $rank->rank_short;
    }

    /**
     * Return the SERP URL
     *
     * @param Skipman\FirefighterBundle\Models\FirefighterModel $model
     *
     * @return string
     */
    public function getSerpUrl(\Skipman\FirefighterBundle\Models\FirefighterModel $model)
    {
        return \Skipman\ContaoFirefighterBundle\Classes\Firefighter::generateFirefighterUrl($model, false, true);
    }

    /**
     * Return the title tag from the associated page layout
     *
     * @param Skipman\FirefighterBundle\Models\FirefighterModel $model
     *
     * @return string
     */
    public function getTitleTag(\Skipman\ContaoFirefighterBundle\Models\FirefighterModel $model)
    {
        /** @var Skipman\FirefighterBundle\Models\FirefighterArchiveModel $archive */
        if (!$archive = $model->getRelated('pid')) {
            return '';
        }

        /** @var Contao\PageModel $page */
        if (!$page = $archive->getRelated('jumpTo')) {
            return '';
        }

        $page->loadDetails();

        /** @var Contao\LayoutModel $layout */
        if (!$layout = $page->getRelated('layout')) {
            return '';
        }

        $origObjPage = $GLOBALS['objPage'] ?? null;

        // Override the global page object, so we can replace the insert tags
        $GLOBALS['objPage'] = $page;

        $title = implode(
            '%s',
            array_map(
                static function ($strVal) {
                    return str_replace('%', '%%', System::getContainer()->get('contao.insert_tag.parser')->replaceInline($strVal));
                },
                explode('{{page::pageTitle}}', $layout->titleTag ?: '{{page::pageTitle}} - {{page::rootPageTitle}}', 2)
            )
        );

        $GLOBALS['objPage'] = $origObjPage;

        return $title;
    }

    /**
     * Get all articles and return them as array.
     *
     * @param DataContainer
     *
     * @return array
     */
    public function getArticleAlias(DataContainer $dc): array
    {
        $arrPids = [];
        $arrAlias = [];

        if (!$this->User->isAdmin) {
            foreach ($this->User->pagemounts as $id) {
                $arrPids[] = $id;
                $arrPids = array_merge($arrPids, $this->Database->getChildRecords($id, 'tl_page'));
            }

            if (empty($arrPids)) {
                return $arrAlias;
            }

            $objAlias = Database::getInstance()
                ->prepare('SELECT a.id, a.title, a.inColumn, p.title AS parent FROM tl_article a LEFT JOIN tl_page p ON p.id=a.pid WHERE a.pid IN(' . implode(',', array_map('intval', array_unique($arrPids))) . ') ORDER BY parent, a.sorting')
                ->execute($dc->id);
        } else {
            $objAlias = $this->Database->prepare('SELECT a.id, a.title, a.inColumn, p.title AS parent FROM tl_article a LEFT JOIN tl_page p ON p.id=a.pid ORDER BY parent, a.sorting')
                ->execute($dc->id);
        }

        if ($objAlias->numRows) {
            System::loadLanguageFile('tl_article');

            while ($objAlias->next()) {
                $arrAlias[$objAlias->parent][$objAlias->id] = $objAlias->title . ' (' . ($GLOBALS['TL_LANG']['tl_article'][$objAlias->inColumn] ?: $objAlias->inColumn) . ', ID ' . $objAlias->id . ')';
            }
        }

        return $arrAlias;
    }

    /**
     * Add the source options depending on the allowed fields
     *
     * @param DataContainer
     *
     * @return array
     */
    public function getSourceOptions(DataContainer $dc): array
    {
        if ($this->User->isAdmin) {
            return ['default', 'internal', 'article', 'external'];
        }

        $arrOptions = ['default'];

        // Add the "internal" option
        if ($this->User->hasAccess('tl_firefighter::jumpTo', 'alexf')) {
            $arrOptions[] = 'internal';
        }

        // Add the "article" option
        if ($this->User->hasAccess('tl_firefighter::articleId', 'alexf')) {
            $arrOptions[] = 'article';
        }

        // Add the "external" option
        if ($dc->activeRecord && $dc->activeRecord->source !== 'default') {
            $arrOptions[] = 'external';
        }

        return $arrOptions;
    }

    /**
     * Adjust start end end time of the event based on date, span, startTime and endTime.
     *
     * @param DataContainer $dc
     */
    public function adjustTime(DataContainer $dc): void
    {
        // Return if there is no active record (override all)
        if (!$dc->activeRecord) {
            return;
        }

        $arrSet['date'] = strtotime(date('d.m.Y', ((int)$dc->activeRecord->date)));
        $this->Database->prepare('UPDATE tl_firefighter %s WHERE id=?')->set($arrSet)->execute($dc->id);
    }

    /**
     * @param DataContainer $dc
     * @param $row
     * @param $table
     * @param $cr
     * @param $arrClipboard
     *
     * @return string
     */
    public function pasteElement(DataContainer $dc, $row, $table, $cr, $arrClipboard): string
    {
        $imagePasteAfter = Image::getHtml('pasteafter.svg', sprintf($GLOBALS['TL_LANG'][$table]['pasteafter'][1], $row['id']));

        return '<a href="' . self::addToUrl('act=' . $arrClipboard['mode'] . '&mode=1&pid=' . $row['id']) . '" title="' . StringUtil::specialchars(sprintf($GLOBALS['TL_LANG'][$table]['pasteafter'][1], $row['id'])) . '" onclick="Backend.getScrollOffset()">' . $imagePasteAfter . '</a> ';
    }

    /**
     * @param DataContainer $dc
     *
     * @return array
     */
    public function addSitemapCacheInvalidationTag($dc, array $tags)
    {
        $archiveModel = FirefighterArchiveModel::findByPk($dc->activeRecord->pid);

        if ($archiveModel === null) {
            return $tags;
        }

        $pageModel = PageModel::findWithDetails($archiveModel->jumpTo);

        if ($pageModel === null) {
            return $tags;
        }

        return array_merge($tags, array('contao.sitemap.' . $pageModel->rootId));
    }
}

