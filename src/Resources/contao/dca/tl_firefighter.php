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
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Skipman\FirefighterBundle\Helper\FirefighterHelper;
use Skipman\FirefighterBundle\Models\FirefighterArchiveModel;
use Composer\InstalledVersions;

System::loadLanguageFile('tl_content');

$contaoVersion = InstalledVersions::getPrettyVersion('contao/core-bundle');
$isContao57OrHigher = $contaoVersion && version_compare($contaoVersion, '5.7.0', '>=');

if ($isContao57OrHigher) {
    $firefighterOperations = [
        '!edit',
        '!copy',
        'cut',
        'delete',
        'toggle' => [
            'href' => 'act=toggle&amp;field=published',
            'icon' => 'visible.svg',
            'primary' => true,
        ],
        'feature' => [
            'href' => 'act=toggle&amp;field=featured',
            'icon' => 'featured.svg',
        ],
        'show',
    ];
} else {
    $firefighterOperations = [
        'editheader' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['editmeta'],
            'href'  => 'act=edit',
            'icon'  => 'edit.svg',
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
    ];
}

$GLOBALS['TL_DCA']['tl_firefighter'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_firefighter_archive',
        'switchToEdit' => true,
        'enableVersioning' => true,
        'onload_callback' => [
            ['tl_firefighter', 'checkPermission'],
        ],
        'onsubmit_callback' => [
            ['tl_firefighter', 'updateHeadlineAndAlias'],
        ],
        'oninvalidate_cache_tags_callback' => [
            ['tl_firefighter', 'addSitemapCacheInvalidationTag'],
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
        'operations' => $firefighterOperations,
    ],
    'palettes' => [
        '__selector__' => ['addImage', 'source', 'overwriteMeta'],
        'default' => '{title_legend},membersFirstname,membersLastname,membersRank,membersRankHonory,membersHomebase,membersSince,firefightercategories;'
                   . '{image_legend:hide},addImage;'
                   . '{ffMemberFunctionLocal_legend:hide},membersFunctionLocalWizard;'
                   . '{ffMemberFunctionSection_legend:hide},membersFunctionSectionWizard;'
                   . '{ffMemberCourses_legend:hide},membersCoursesWizard;'
                   . '{ffMemberBadges_legend:hide},membersBadgesWizard;'
                   . '{ffMemberAwards_legend:hide},membersAwardsWizard;'
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
            'options_callback' => [FirefighterHelper::class, 'getAllowedDepartmentOptions'],
            'eval' => [
                'maxlength' => 255,
                'includeBlankOption' => true,
                'tl_class' => 'w25',
            ],
            'sql' => "varchar(255) NOT NULL default ''",
            'save_callback' => [
                [FirefighterHelper::class, 'filterAllowedDepartment'],
            ],
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
            'options_callback' => [FirefighterHelper::class, 'getAllowedFirefighterCategoryOptions'],
            'eval' => ['multiple' => true, 'tl_class' => 'w50', 'chosen' => true,],
            'save_callback' => [
                [FirefighterHelper::class, 'filterAllowedFirefighterCategories'],
            ],
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
                        'eval' => [
                            'includeBlankOption' => true, 
                            'wrapper_style' => 'width:5%', 
                            'style' => 'width:100%',
                            ]
                    ],
                    'membersFunctionLocalFromMonth' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['membersFunctionLocalFromMonth'],
                        'inputType' => 'select',
                        'options' => ['01','02','03','04','05','06','07','08','09','10','11','12'],
                        'eval' => [
                            'includeBlankOption' => true,  
                            'wrapper_style' => 'width:5%', 
                            'style' => 'width:100%',
                            ]
                    ],
                    'membersFunctionLocalFromYear' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['membersFunctionLocalFromYear'],
                        'inputType' => 'text',
                        'eval' => [ 
                            'wrapper_style' => 'width:5%', 
                            'style' => 'width:100%', 
                            'maxlength' => 4,
                            ]
                    ],
                    'membersFunctionLocalUntilMonth' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['membersFunctionLocalUntilMonth'],
                        'inputType' => 'select',
                        'options' => ['01','02','03','04','05','06','07','08','09','10','11','12'],
                        'eval' => [
                            'includeBlankOption' => true,  
                            'wrapper_style' => 'width:5%', 
                            'style' => 'width:100%',
                            ]
                    ],
                    'membersFunctionLocalUntilYear' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['membersFunctionLocalUntilYear'],
                        'inputType' => 'text',
                        'eval' => [ 
                            'wrapper_style' => 'width:5%', 
                            'style' => 'width:100%', 
                            'maxlength' => 4,
                            ]
                    ]
                ],
                'tl_class' => 'clr',
                'minCount' => 0,
                'allowHtml' => true,
                'ignoreEmptySubmit' => true,
            ],
            'sql' => "blob NULL",
            'load_callback' => [
                [FirefighterHelper::class, 'filterFunctionLocalRows'],
            ],
            'save_callback' => [
                [FirefighterHelper::class, 'sanitizeFunctionLocalRows'],
            ],
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
                        'eval' => [
                            'includeBlankOption' => true, 
                            'wrapper_style' => 'width:5%', 
                            'style' => 'width:100%',
                            ]
                    ],
                    'membersFunctionSectionFromMonth' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['membersFunctionSectionFromMonth'],
                        'inputType' => 'select',
                        'options' => ['01','02','03','04','05','06','07','08','09','10','11','12'],
                        'eval' => [
                            'includeBlankOption' => true,
                            'wrapper_style' => 'width:5%', 
                            'style' => 'width:100%',
                            ]
                    ],
                    'membersFunctionSectionFromYear' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['membersFunctionSectionFromYear'],
                        'inputType' => 'text',
                        'eval' => [
                            'wrapper_style' => 'width:5%', 
                            'style' => 'width:100%', 
                            'maxlength' => 4,
                            ]
                    ],
                    'membersFunctionSectionUntilMonth' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['membersFunctionSectionUntilMonth'],
                        'inputType' => 'select',
                        'options' => ['01','02','03','04','05','06','07','08','09','10','11','12'],
                        'eval' => [
                            'includeBlankOption' => true, 
                            'wrapper_style' => 'width:5%', 
                            'style' => 'width:100%',
                            ]
                    ],
                    'membersFunctionSectionUntilYear' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['membersFunctionSectionUntilYear'],
                        'inputType' => 'text',
                        'eval' => [
                            'wrapper_style' => 'width:5%', 
                            'style' => 'width:100%', 
                            'maxlength' => 4,
                            ]
                    ],
                ],
                'tl_class' => 'clr',
                'minCount' => 0,
            ],
            'sql' => "blob NULL",
            'load_callback' => [
                [FirefighterHelper::class, 'filterFunctionSectionRows'],
            ],
            'save_callback' => [
                [FirefighterHelper::class, 'sanitizeFunctionSectionRows'],
            ],
        ],
        'membersCoursesWizard' => [
            'exclude' => true,
            'inputType' => 'multiColumnWizard',
            'eval' => [
                'columnFields' => [
                    'membersCourse' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['membersCourse'],
                        'inputType' => 'select',
                        'options_callback' => [FirefighterHelper::class, 'getCoursesShortOptions'],
                        'eval' => [
                            'includeBlankOption' => true, 
                            'wrapper_style' => 'width:5%', 
                            'style' => 'width:100%',
                            ]
                    ],
                    'membersCourseYear' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['membersCourseYear'],
                        'inputType' => 'text',
                        'eval' => [
                            'wrapper_style' => 'width:5%', 
                            'style' => 'width:100%',
                            ]
                    ],
                ],
                'tl_class' => 'clr',
                'minCount' => 0,
                'ignoreEmptySubmit' => true,
            ],
            'sql' => "blob NULL",
            'load_callback' => [
                [FirefighterHelper::class, 'filterCourseRows'],
            ],
            'save_callback' => [
                [FirefighterHelper::class, 'sanitizeCourseRows'],
            ],
        ],
        'membersBadgesWizard' => [
            'exclude' => true,
            'inputType' => 'multiColumnWizard',
            'eval' => [
                'columnFields' => [
                    'membersBadge' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['membersBadge'],
                        'inputType' => 'select',
                        'options_callback' => [FirefighterHelper::class, 'getBadgesShortOptions'],
                        'eval' => [
                            'includeBlankOption' => true, 
                            'wrapper_style' => 'width:5%', 
                            'style' => 'width:100%',
                            ]
                    ],
                    'membersBadgeYear' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['membersBadgeYear'],
                        'inputType' => 'text',
                        'eval' => [
                            'wrapper_style' => 'width:5%', 
                            'style' => 'width:100%',
                            ]
                    ],
                ],
                'tl_class' => 'clr',
                'minCount' => 0,
            ],
            'sql' => "blob NULL",
            'load_callback' => [
                [FirefighterHelper::class, 'filterBadgeRows'],
            ],
            'save_callback' => [
                [FirefighterHelper::class, 'sanitizeBadgeRows'],
            ],
        ],
        'membersAwardsWizard' => [
            'exclude' => true,
            'inputType' => 'multiColumnWizard',
            'eval' => [
                'columnFields' => [
                    'membersAward' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['membersAward'],
                        'inputType' => 'select',
                        'options_callback' => [FirefighterHelper::class, 'getAwardsShortOptions'],
                        'eval' => [
                            'includeBlankOption' => true, 
                            'wrapper_style' => 'width:5%', 
                            'style' => 'width:100%',
                            ]
                    ],
                    'membersAwardYear' => [
                        'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['membersAwardYear'],
                        'inputType' => 'text',
                        'eval' => [
                            'wrapper_style' => 'width:5%', 
                            'style' => 'width:100%',
                            ]
                    ],
                ],
                'tl_class' => 'clr',
                'minCount' => 0,
            ],
            'sql' => "blob NULL",
            'load_callback' => [
                [FirefighterHelper::class, 'filterAwardRows'],
            ],
            'save_callback' => [
                [FirefighterHelper::class, 'sanitizeAwardRows'],
            ],
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
            'eval' => [
                'maxlength' => 255, 
                'decodeEntities' => true, 
                'tl_class' => 'w50',
                ],
            'sql' => "varchar(255) NOT NULL default ''"
        ],
        'robots' => [
            'exclude' => true,
            'search' => false,
            'inputType' => 'select',
            'options' => ['index,follow', 'index,nofollow', 'noindex,follow', 'noindex,nofollow'],
            'eval' => [
                'tl_class' => 'w50', 
                'includeBlankOption' => true,
                ],
            'sql' => "varchar(32) NOT NULL default ''"
        ],
        'description' => [
            'exclude' => true,
            'search' => false,
            'inputType' => 'textarea',
            'eval' => [
                'style' => 'height:60px', 
                'decodeEntities' => true, 
                'tl_class' => 'clr',
                ],
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
                'descriptionFields' => ['description', 'teaser'],
            ],
            'sql' => null
        ],
        'teaser' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['teaser'],
            'exclude' => true,
            'search' => false,
            'inputType' => 'textarea',
            'eval' => [
                'rte' => 'tinyMCE', 
                'helpwizard' => true, 
                'tl_class' => 'clr',
                ],
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
            'eval' => [
                'rgxp' => 'date', 
                'doNotCopy' => true, 
                'datepicker' => true, 
                'tl_class' => 'w50 wizard',
                ],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'overwriteMeta' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['overwriteMeta'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => [
                'submitOnChange' => true, 
                'tl_class' => 'w50 clr',
                ],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'singleSRC' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['singleSRC'],
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => [
                'fieldType' => 'radio', 
                'filesOnly' => true, 
                'extensions' => Config::get('validImageTypes'), 
                'mandatory' => true,
                ],
            'sql' => 'binary(16) NULL',
        ],
        'alt' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['alt'],
            'exclude' => true,
            'search' => false,
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 255, 
                'tl_class' => 'w50',
                ],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'imageTitle' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['imageTitle'],
            'exclude' => true,
            'search' => false,
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 255, 
                'tl_class' => 'w50',
                ],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'size' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['size'],
            'exclude' => true,
            'inputType' => 'imageSize',
            'reference' => &$GLOBALS['TL_LANG']['MSC'],
            'eval' => [
                'rgxp' => 'natural', 
                'includeBlankOption' => true, 
                'nospace' => true, 
                'helpwizard' => true, 
                'tl_class' => 'w50',
                ],
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
            'eval' => [
                'rgxp' => 'url', 
                'decodeEntities' => true, 
                'maxlength' => 255, 
                'dcaPicker' => true, 
                'tl_class' => 'w50 wizard',
                ],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'fullsize' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['fullsize'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'w50 m12',
                ],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'caption' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['caption'],
            'exclude' => true,
            'search' => false,
            'inputType' => 'text',
            'eval' => [
                'maxlength' => 255, 
                'allowHtml' => true, 
                'tl_class' => 'w50',
                ],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'floating' => [
            'label' => &$GLOBALS['TL_LANG']['tl_content']['floating'],
            'default' => 'above',
            'exclude' => true,
            'inputType' => 'radioTable',
            'options' => ['above', 'left', 'right', 'below'],
            'eval' => [
                'cols' => 4, 
                'tl_class' => 'w50',
                ],
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
            'eval' => [
                'submitOnChange' => true, 
                'helpwizard' => true,
                ],
            'sql' => "varchar(12) NOT NULL default ''",
        ],
        'jumpTo' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['jumpTo'],
            'exclude' => true,
            'inputType' => 'pageTree',
            'foreignKey' => 'tl_page.title',
            'eval' => [
                'mandatory' => true, 
                'fieldType' => 'radio',
                ],
            'sql' => "int(10) unsigned NOT NULL default '0'",
            'relation' => ['type' => 'belongsTo', 'load' => 'lazy'],
        ],
        'articleId' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['articleId'],
            'exclude' => true,
            'inputType' => 'select',
            'options_callback' => ['tl_firefighter', 'getArticleAlias'],
            'eval' => ['mandatory' => true,],
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'url' => [
            'label' => &$GLOBALS['TL_LANG']['MSC']['url'],
            'exclude' => true,
            'search' => false,
            'inputType' => 'text',
            'eval' => [
                'mandatory' => true, 
                'decodeEntities' => true, 
                'maxlength' => 255, 
                'tl_class' => 'w50',
                ],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'target' => [
            'label' => &$GLOBALS['TL_LANG']['MSC']['target'],
            'exclude' => true,
            'inputType' => 'checkbox',
            'eval' => ['tl_class' => 'w50 m12',],
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
            'eval' => ['doNotCopy' => true,],
            'sql' => ['type' => 'boolean', 'default' => false],
        ],
        'start' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['start'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => [
                'rgxp' => 'datim', 
                'datepicker' => true, 
                'tl_class' => 'w50 wizard',
                ],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
        'stop' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['stop'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => [
                'rgxp' => 'datim', 
                'datepicker' => true, 
                'tl_class' => 'w50 wizard',
                ],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
        'featured' => [
            'label' => &$GLOBALS['TL_LANG']['tl_firefighter']['featured'],
            'exclude' => true,
            'filter' => false,
            'toggle' => true,
            'inputType' => 'checkbox',
            'eval' => [
                'tl_class' => 'w50', 
                'doNotCopy' => true,
                ],
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
    public function checkPermission(DataContainer $dc): void
    {
        if ($this->User->isAdmin) {
            return;
        }

        if (empty($this->User->firefighter) || !is_array($this->User->firefighter)) {
            $root = [0];
        } else {
            $root = array_map('intval', $this->User->firefighter);
        }

        $currentPid = (int) ($dc->currentPid ?? 0);
        $id = (int) (Input::get('id') !== '' ? Input::get('id') : $currentPid);

        switch (Input::get('act')) {
            case 'paste':
            case 'select':
                if (!in_array($currentPid, $root, true)) {
                    throw new AccessDeniedException('Not enough permissions to access firefighter archive ID ' . $currentPid . '.');
                }
                break;

            case 'create':
                $pid = (int) Input::get('pid');

                if (!$pid || !in_array($pid, $root, true)) {
                    throw new AccessDeniedException('Not enough permissions to create firefighter items in firefighter archive ID ' . $pid . '.');
                }
                break;

            case 'cut':
            case 'copy':
                if (Input::get('act') === 'cut' && Input::get('mode') === 1) {
                    $objArchive = Database::getInstance()
                        ->prepare("SELECT pid FROM tl_firefighter WHERE id=?")
                        ->limit(1)
                        ->execute((int) Input::get('pid'));

                    if ($objArchive->numRows < 1) {
                        throw new AccessDeniedException('Invalid firefighter item ID ' . (int) Input::get('pid') . '.');
                    }

                    $pid = (int) $objArchive->pid;
                } else {
                    $pid = (int) Input::get('pid');
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

                if (!in_array((int) $objArchive->pid, $root, true)) {
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
                $objSession = System::getContainer()->get('request_stack')->getSession();

                $session = $objSession->all();
                $session['CURRENT']['IDS'] = array_intersect((array) ($session['CURRENT']['IDS'] ?? []), $objArchive->fetchEach('id'));
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
     * Auto-generate the headline based on the members' details.
     *
     * @param mixed $varValue
     * @param DataContainer $dc
     * @return string
     */

    public function generateHeadline($varValue, DataContainer $dc): string
    {
        $record = $dc->activeRecord;

        if (null === $record) {
            return (string) $varValue;
        }

        $membersFirstname = trim((string) $record->membersFirstname);
        $membersLastname = trim((string) $record->membersLastname);
        $membersRank = '';

        if ($record->membersRank) {
            $membersRank = trim((string) $this->getRankName($record->membersRank, $record->membersRankHonory));
        }

        return trim(sprintf('%s %s %s', $membersRank, $membersFirstname, $membersLastname));
    }

    /**
     * Update headline and alias on form submission.
     *
     * @param DataContainer $dc
     */

    public function updateHeadlineAndAlias(DataContainer $dc): void
    {
        if (!$dc->activeRecord) {
            return;
        }

        $headline = $this->generateHeadline('', $dc);

        $aliasExists = function (string $alias) use ($dc): bool {
            return Database::getInstance()
                ->prepare("SELECT id FROM tl_firefighter WHERE alias=? AND id!=?")
                ->execute($alias, $dc->id)
                ->numRows > 0;
        };

        $archive = FirefighterArchiveModel::findByPk($dc->activeRecord->pid);
        $jumpTo = $archive ? $archive->jumpTo : null;

        $alias = System::getContainer()
            ->get('contao.slug')
            ->generate($headline, $jumpTo, $aliasExists);

        Database::getInstance()
            ->prepare("UPDATE tl_firefighter SET headline=?, alias=? WHERE id=?")
            ->execute($headline, $alias, $dc->id);
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
        return \Skipman\FirefighterBundle\Classes\Firefighter::generateFirefighterUrl($model, false, true);
    }

    /**
     * Return the title tag from the associated page layout
     *
     * @param Skipman\FirefighterBundle\Models\FirefighterModel $model
     *
     * @return string
     */
    public function getTitleTag(\Skipman\FirefighterBundle\Models\FirefighterModel $model)
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

    protected function hasAlexfAccess(string $field): bool
    {
        // Neuer Weg (Contao 5)
        if (System::getContainer()->has('security.helper')) {
            return System::getContainer()
                ->get('security.helper')
                ->isGranted('contao_user.alexf', 'tl_firefighter::' . $field);
        }

        // Fallback (Contao 5.3)
        return $this->User->hasAccess('tl_firefighter::' . $field, 'alexf');
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
        if ($this->hasAlexfAccess('jumpTo')) {
            $arrOptions[] = 'internal';
        }

        // Add the "article" option
        if ($this->hasAlexfAccess('articleId')) {
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

