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

 use Contao\Input;
 use Contao\Image;
 use Contao\System;
 use Contao\Backend;
 use Contao\DC_Table;
 use Contao\PageModel;
 use Contao\StringUtil;
 use Contao\BackendUser;
 use Contao\CoreBundle\Exception\AccessDeniedException;
 use Symfony\Component\HttpFoundation\Session\SessionInterface;
 use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
 
 $GLOBALS['TL_DCA']['tl_firefighter_archive'] = [
     'config' => [
         'dataContainer' => DC_Table::class,
         'ctable' => ['tl_firefighter'],
         'switchToEdit' => true,
         'enableVersioning' => true,
         'markAsCopy' => 'title',
         'onload_callback' => array
         (
             array('tl_firefighter_archive', 'checkPermission')
         ),
         'oncreate_callback' => array
         (
             array('tl_firefighter_archive', 'adjustPermissions')
         ),
         'oncopy_callback' => array
         (
             array('tl_firefighter_archive', 'adjustPermissions')
         ),
         'oninvalidate_cache_tags_callback' => array
         (
             array('tl_firefighter_archive', 'addSitemapCacheInvalidationTag'),
         ),
         'sql' => [
             'keys' => [
                 'id' => 'primary',
             ],
         ],
     ],
 
     'list' => [
         'sorting' => [
             'mode' => DC_Table::MODE_SORTED,
             'fields' => ['title'],
             'flag' => 1,
             'panelLayout' => 'search,limit',
         ],
         'label' => [
             'fields' => ['title'],
             'format' => '%s',
         ],
         'global_operations' => [
             'categories' => [
                 'label'      => &$GLOBALS['TL_LANG']['tl_firefighter_archive']['categories'],
                 'href'       => 'table=tl_firefighter_category',
                 'icon'       => 'bundles/firefighter/user-group.svg',
                 'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="c"',
             ],
             'all' => [
                 'label'               => &$GLOBALS['TL_LANG']['MSC']['all'],
                 'href'                => 'act=select',
                 'class'               => 'header_edit_all',
                 'attributes'          => 'onclick="Backend.getScrollOffset()" accesskey="e"',
             ],
         ],
         'operations' => [
             'editheader' => [
                 'label'               => &$GLOBALS['TL_LANG']['tl_firefighter_archive']['editheader'],
                 'href'                => 'act=edit',
                 'icon'                => 'edit.svg',
                 'button_callback'     => ['tl_firefighter_archive', 'editHeader'],
             ],
             'edit' => [
                 'label'               => &$GLOBALS['TL_LANG']['tl_firefighter_archive']['edit'],
                 'href'                => 'table=tl_firefighter',
                 'icon'                => 'children.svg',
             ],
             'copy' => [
                 'label'               => &$GLOBALS['TL_LANG']['tl_firefighter_archive']['copy'],
                 'href'                => 'act=copy',
                 'icon'                => 'copy.svg',
                 'button_callback'     => ['tl_firefighter_archive', 'copyArchive'],
             ],
             'delete' => [
                 'label'               => &$GLOBALS['TL_LANG']['tl_firefighter_archive']['delete'],
                 'href'                => 'act=delete',
                 'icon'                => 'delete.svg',
                 'attributes'          => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null).'\'))return false;Backend.getScrollOffset()"',
                 'button_callback'     => ['tl_firefighter_archive', 'deleteArchive'],
             ],
             'show' => [
                 'label'               => &$GLOBALS['TL_LANG']['tl_firefighter_archive']['show'],
                 'href'                => 'act=show',
                 'icon'                => 'show.svg',
             ],
         ],
     ],
 
     'palettes' => [
         '__selector__' => ['protected'],
         'default' => '{title_legend},title,jumpTo;{protected_legend:hide},protected;',
     ],
 
     'subpalettes' => [
         'protected' => 'groups',
     ],
 
     'fields' => [
         'id' => [
             'sql'                     => 'int(10) unsigned NOT NULL auto_increment',
         ],
         'tstamp' => [
             'sql'                     => "int(10) unsigned NOT NULL default '0'",
         ],
         'title' => [
             'label'                   => &$GLOBALS['TL_LANG']['tl_firefighter_archive']['title'],
             'exclude'                 => true,
             'search'                  => true,
             'inputType'               => 'text',
             'eval'                    => ['mandatory'=>true, 'maxlength'=>255, 'tl_class'=>'w50'],
             'sql'                     => "varchar(255) NOT NULL default ''",
         ],
         'jumpTo' => array
         (
             'label'                   => &$GLOBALS['TL_LANG']['tl_firefighter_archive']['jumpTo'],
             'exclude'                 => true,
             'inputType'               => 'pageTree',
             'foreignKey'              => 'tl_page.title',
             'eval'                    => ['mandatory'=>true, 'fieldType'=>'radio', 'tl_class'=>'clr'],
             'sql'                     => "int(10) unsigned NOT NULL default 0",
             'relation'                => ['type'=>'hasOne', 'load'=>'lazy']
         ),
         'protected' => array
         (
             'label'                   => &$GLOBALS['TL_LANG']['tl_firefighter_archive']['protected'],
             'exclude'                 => true,
             'filter'                  => true,
             'inputType'               => 'checkbox',
             'eval'                    => ['submitOnChange'=>true, 'isBoolean'=>true],
             'sql' => ['type' => 'boolean', 'default' => false],
         ),
         'groups' => array
         (
             'label'                   => &$GLOBALS['TL_LANG']['tl_firefighter_archive']['groups'],
             'exclude'                 => true,
             'inputType'               => 'checkbox',
             'foreignKey'              => 'tl_member_group.name',
             'eval'                    => ['mandatory'=>true, 'multiple'=>true],
             'sql'                     => "blob NULL",
             'relation'                => ['type'=>'hasMany', 'load'=>'lazy']
         ),
     ],
 ];
 
 class tl_firefighter_archive extends Backend
 {
     public function __construct()
     {
         parent::__construct();
         $this->import(BackendUser::class, 'User');
     }
 
     /**
      * Check permissions to edit table tl_firefighter_archive
      */
     public function checkPermission(): void
     {
         if ($this->User->isAdmin) {
             return;
         }
 
         // Set root IDs
         if (empty($this->User->firefighter) || !is_array($this->User->firefighter)) {
             $root = array(0);
         } else {
             $root = $this->User->firefighter;
         }
 
         $GLOBALS['TL_DCA']['tl_firefighter_archive']['list']['sorting']['root'] = $root;
 
         // Check permissions to add archives
         if (!$this->User->hasAccess('create', 'firefighterp')) {
             $GLOBALS['TL_DCA']['tl_firefighter_archive']['config']['closed'] = true;
             $GLOBALS['TL_DCA']['tl_firefighter_archive']['config']['notCreatable'] = true;
             $GLOBALS['TL_DCA']['tl_firefighter_archive']['config']['notCopyable'] = true;
         }
 
         // Check permissions to delete calendars
         if (!$this->User->hasAccess('delete', 'firefighterp')) {
             $GLOBALS['TL_DCA']['tl_firefighter_archive']['config']['notDeletable'] = true;
         }
 
         /** @var SessionInterface $objSession */
         System::getContainer()->get('request_stack')->getSession();
 
         // Check current action
         switch (Input::get('act')) {
             case 'select':
                 // Allow
                 break;
 
             case 'create':
                 if (!$this->User->hasAccess('create', 'firefighterp')) {
                     throw new AccessDeniedException('Not enough permissions to create firefighter archives.');
                 }
                 break;
 
             case 'edit':
             case 'copy':
             case 'delete':
             case 'show':
                 if (!in_array(Input::get('id'), $root, true) || (Input::get('act') === 'delete' && !$this->User->hasAccess('delete', 'firefighterp'))) {
                     throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' firefighter archive ID ' . Input::get('id') . '.');
                 }
                 break;
 
             case 'editAll':
             case 'deleteAll':
             case 'overrideAll':
             case 'copyAll':
                 $session = $objSession->all();
 
                 if (Input::get('act') === 'deleteAll' && !$this->User->hasAccess('delete', 'firefighterp'))
                 {
                     $session['CURRENT']['IDS'] = array();
                 }
                 else
                 {
                     $session['CURRENT']['IDS'] = array_intersect((array) $session['CURRENT']['IDS'], $root);
                 }
                 $objSession->replace($session);
                 break;
 
             default:
                 if (Input::get('act'))
                 {
                     throw new AccessDeniedException('Not enough permissions to ' . Input::get('act') . ' firefighter archives.');
                 }
                 break;
         }
     }
 
     /**
      * Add the new archive to the permissions
      */
     public function adjustPermissions($insertId): void
     {
         // The oncreate_callback passes $insertId as second argument
         if (func_num_args() === 4) {
             $insertId = func_get_arg(1);
         }
 
         if ($this->User->isAdmin) {
             return;
         }
 
         // Set root IDs
         if (empty($this->User->firefighter) || !is_array($this->User->firefighter)) {
             $root = array(0);
         }
         else
         {
             $root = $this->User->firefighter;
         }
 
         // The archive is enabled already
         if (in_array($insertId, $root, true)) {
             return;
         }
 
         /** @var AttributeBagInterface $objSessionBag */
         $objSessionBag = System::getContainer()->get('session')->getBag('contao_backend');
 
         $arrNew = $objSessionBag->get('new_records');
 
         if (is_array($arrNew['tl_firefighter_archive']) && in_array($insertId, $arrNew['tl_firefighter_archive'], true))
         {
             // Add the permissions on group level
             if ($this->User->inherit !== 'custom')
             {
                 $objGroup = $this->Database::getInstance()
                     ->execute("SELECT id, firefighter, firefighterp FROM tl_user_group WHERE id IN(" . implode(',', array_map('\intval', $this->User->groups)) . ")");
 
                 while ($objGroup->next())
                 {
                     $arrFirefighterp = StringUtil::deserialize($objGroup->firefighterp);
 
                     if (is_array($arrFirefighterp) && in_array('create', $arrFirefighterp, true)) {
                         $arrFirefighter = StringUtil::deserialize($objGroup->firefighter, true);
                         $arrFirefighter[] = $insertId;
 
                         $this->Database::getInstance()
                             ->prepare("UPDATE tl_user_group SET firefighter=? WHERE id=?")
                             ->execute(serialize($arrFirefighter), $objGroup->id);
                     }
                 }
             }
 
             // Add the permissions on user level
             if ($this->User->inherit !== 'group') {
                 $objUser = $this->Database::getInstance()
                     ->prepare("SELECT firefighter, firefighterp FROM tl_user WHERE id=?")
                     ->limit(1)
                     ->execute($this->User->id);
 
                 $arrFirefighterp = StringUtil::deserialize($objUser->firefighterp);
 
                 if (is_array($arrFirefighterp) && in_array('create', $arrFirefighterp, true)) {
                     $arrFirefighter = StringUtil::deserialize($objUser->firefighter, true);
                     $arrFirefighter[] = $insertId;
 
                     $this->Database::getInstance()
                         ->prepare("UPDATE tl_user SET firefighter=? WHERE id=?")
                         ->execute(serialize($arrFirefighter), $this->User->id);
                 }
             }
 
             // Add the new element to the user object
             $root[] = $insertId;
             $this->User->firefighter = $root;
         }
     }
 
     /**
      * Return the edit header button.
      */
     public function editHeader(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
     {
         return '<a href="'.self::addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a>';
     }
 
     /**
      * Return the copy archive button.
      */
     public function copyArchive(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
     {
         return $this->User->hasAccess('create', 'firefighterp') ? '<a href="'.self::addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)).' ';
     }
 
     /**
      * Return the delete archive button.
      */
     public function deleteArchive(array $row, string $href, string $label, string $title, string $icon, string $attributes): string
     {
         return $this->User->hasAccess('delete', 'firefighterp') ? '<a href="'.self::addToUrl($href.'&amp;id='.$row['id']).'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ' : Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)).' ';
     }
 
     public function addSitemapCacheInvalidationTag($dc, array $tags): array
     {
         $pageModel = PageModel::findWithDetails($dc->activeRecord->jumpTo);
 
         if ($pageModel === null) {
             return $tags;
         }
 
         return array_merge($tags, array('contao.sitemap.' . $pageModel->rootId));
     }
 }
 