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

 namespace Skipman\FirefighterBundle\Models;

use Contao\Date;
use Contao\Model;
use Contao\System;
use Contao\Database;
use Contao\StringUtil;
use Contao\Model\Collection;
use Contao\Model\MetadataTrait;

class FirefighterModel extends Model
{
    use Model\MetadataTrait;

    protected static $strTable = 'tl_firefighter';

    // Liste aller Felder sicherstellen
    protected static $arrFields = ['membersFirstname', 'membersLastname', 'membersRank', 'membersHomebase', 'website', 'membersPhone', 'membersEmail', 'headline', 'teaser', 'addImage', 'singleSRC', 'cssClass', 'firefightercategories'];

    
    
    public function getHomebaseName()
    {
        if (!$this->membersHomebase) {
            return '';
        }

        $result = Database::getInstance()->prepare("SELECT ffname FROM tl_firefighter_departments WHERE id=?")
                                         ->execute($this->membersHomebase);

        return $result->numRows ? $result->ffname : '';
    }

    public function getHomebaseDetails()
    {
        if (!$this->membersHomebase) {
            return ['name' => '', 'website' => ''];
        }

        $result = Database::getInstance()->prepare("SELECT ffname, socialChannels FROM tl_firefighter_departments WHERE id=?")
                                        ->execute($this->membersHomebase);

        if ($result->numRows) {
            $socialChannels = StringUtil::deserialize($result->socialChannels, true);
            $website = '';
            $facebook = '';

            foreach ($socialChannels as $channel) {
                if ($channel['platform'] === 'Webseite' && !empty($channel['url'])) {
                    $website = $channel['url'];
                } elseif ($channel['platform'] === 'Facebook' && !empty($channel['url'])) {
                    $facebook = $channel['url'];
                }
            }

            return [
                'name' => $result->ffname,
                'website' => $website ?: $facebook
            ];
        }

        return ['name' => '', 'website' => ''];
    }


    /**
     * Find a published firefighter item from one or more firefighter archives by its ID or alias.
     *
     * @param mixed $varId      The numeric ID or alias name
     * @param array $arrPids    An array of parent IDs
     * @param array $arrOptions An optional options array
     *
     * @return FirefighterModel|null The model or null if there are no firefighter items
     */
    public static function findPublishedByParentAndIdOrAlias($varId, array $arrPids, array $arrOptions = []): ?self
    {
        if (empty($arrPids) || !\is_array($arrPids)) {
            return null;
        }

        $t = static::$strTable;
        $arrColumns = !preg_match('/^[1-9]\d*$/', $varId) ? ["BINARY $t.alias=?"] : ["$t.id=?"];
        $arrColumns[] = "$t.pid IN(".implode(',', array_map('\intval', $arrPids)).')';

        if (!static::isPreviewMode($arrOptions)) {
            $time = Date::floorToMinute();
            $arrColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time')";
        }

        return static::findOneBy($arrColumns, $varId, $arrOptions);
    }

    /**
     * Find published firefighter items by their parent ID.
     *
     * @param array     $arrPids     An array of firefighter archive IDs
     * @param bool|null $blnFeatured If true, return only featured firefighter items, if false, return only unfeatured firefighter items
     * @param int       $intLimit    An optional limit
     * @param int       $intOffset   An optional offset
     * @param array     $arrOptions  An optional options array
     *
     * @return Collection|FirefighterModel[]|FirefighterModel|null A collection of models or null if there are no firefighter items
     */
    public static function findPublishedByPids(array $arrPids, bool $blnFeatured = null, $intLimit = 0, $intOffset = 0, array $arrOptions = [], array $arrFirefighterCategories = [])
    {
        if (empty($arrPids) || !\is_array($arrPids)) {
            return null;
        }

        $t = static::$strTable;
        $arrColumns = ["$t.pid IN(".implode(',', array_map('\intval', $arrPids)).')'];

        if (true === $blnFeatured) {
            $arrColumns[] = "$t.featured='1'";
        } elseif (false === $blnFeatured) {
            $arrColumns[] = "$t.featured=''";
        }

        if (!static::isPreviewMode($arrOptions)) {
            $time = Date::floorToMinute();
            $arrColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time')";
        }

        if (!isset($arrOptions['order'])) {
            $arrOptions['order'] = "$t.date DESC";
        }

        // check if firefighter categories are selected and filter by them
        if ($arrFirefighterCategories) {
            $deserializedCategories = array_map('intval', StringUtil::deserialize($arrFirefighterCategories, true));
            $conditions = array_map(fn($category) => "$t.firefightercategories LIKE '%\"$category\"%'", $deserializedCategories);
            $arrColumns[] = '(' . implode(' OR ', $conditions) . ')';
        }

        $arrOptions['limit'] = $intLimit;
        $arrOptions['offset'] = $intOffset;

        return static::findBy($arrColumns, null, $arrOptions);
    }

    /**
     * Count published firefighter items by their parent ID.
     *
     * @param array     $arrPids     An array of firefighter archive IDs
     * @param bool|null $blnFeatured If true, return only featured firefighter items, if false, return only unfeatured firefighter items
     * @param array     $arrOptions  An optional options array
     *
     * @return int The number of firefighter items
     */
    public static function countPublishedByPids(array $arrPids, bool $blnFeatured = null, array $arrFirefighterCategories = [], array $arrOptions = []): int
    {
        if (empty($arrPids) || !is_array($arrPids)) {
            return 0;
        }

        $t = static::$strTable;
        $arrColumns = ["$t.pid IN(".implode(',', array_map('\intval', $arrPids)).')'];

        if (true === $blnFeatured) {
            $arrColumns[] = "$t.featured='1'";
        } elseif (false === $blnFeatured) {
            $arrColumns[] = "$t.featured=''";
        }

        if (!static::isPreviewMode($arrOptions)) {
            $time = Date::floorToMinute();
            $arrColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time')";
        }

        // check if firefighter categories are selected and filter by them
        if ($arrFirefighterCategories) {
            $stringCategories = StringUtil::deserialize($arrFirefighterCategories);
            $arrColumns[] = "$t.firefightercategories LIKE '%\"".implode("\"%' OR $t.firefightercategories LIKE '%\"", array_map('\intval', $stringCategories))."\"%'";
        }

        return static::countBy($arrColumns, null, $arrOptions);
    }

    /**
     * Find published firefighter items by their parent ID.
     *
     * @param int   $intId      The firefighter archive ID
     * @param int   $intLimit   An optional limit
     * @param array $arrOptions An optional options array
     *
     * @return Collection|FirefighterModel[]|FirefighterModel|null A collection of models or null if there are no firefighter items
     */
    public static function findPublishedByPid(int $intId, int $intLimit = 0, array $arrOptions = [])
    {
        $t = static::$strTable;
        $arrColumns = ["$t.pid=?"];

        if (!static::isPreviewMode($arrOptions)) {
            $time = Date::floorToMinute();
            $arrColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time')";
        }

        if (!isset($arrOptions['order'])) {
            $arrOptions['order'] = "$t.date DESC";
        }

        if ($intLimit > 0) {
            $arrOptions['limit'] = $intLimit;
        }

        return static::findBy($arrColumns, $intId, $arrOptions);
    }

    /**
     * Find published firefighter items with the default redirect target by their parent ID.
     *
     * @param int   $intPid     The firefighter archive ID
     * @param array $arrOptions An optional options array
     *
     * @return Collection|FirefighterModel[]|FirefighterModel|null A collection of models or null if there are no firefighter items
     */
    public static function findPublishedDefaultByPid(int $intPid, array $arrOptions = [])
    {
        $t = static::$strTable;
        $arrColumns = ["$t.pid=? AND $t.source='default'"];

        if (!static::isPreviewMode($arrOptions)) {
            $time = Date::floorToMinute();
            $arrColumns[] = "$t.published='1' AND ($t.start='' OR $t.start<='$time') AND ($t.stop='' OR $t.stop>'$time')";
        }

        if (!isset($arrOptions['order'])) {
            $arrOptions['order'] = "$t.date DESC";
        }

        return static::findBy($arrColumns, $intPid, $arrOptions);
    }
}
