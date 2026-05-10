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

namespace Skipman\FirefighterBundle\Models;

use Contao\Date;
use Contao\Model;
use Contao\Database;
use Contao\StringUtil;
use Contao\Model\Collection;

class FirefighterModel extends Model
{
    use Model\MetadataTrait;

    protected static $strTable = 'tl_firefighter';

    protected static $arrFields = [
        'membersFirstname',
        'membersLastname',
        'membersRank',
        'membersHomebase',
        'website',
        'membersPhone',
        'membersEmail',
        'headline',
        'teaser',
        'addImage',
        'singleSRC',
        'cssClass',
        'firefightercategories',
    ];

    public function getHomebaseName()
    {
        if (!$this->membersHomebase) {
            return '';
        }

        $result = Database::getInstance()
            ->prepare("SELECT ffname FROM tl_firefighter_departments WHERE id=?")
            ->execute($this->membersHomebase);

        return $result->numRows ? $result->ffname : '';
    }

    public function getHomebaseDetails()
    {
        if (!$this->membersHomebase) {
            return ['name' => '', 'website' => ''];
        }

        $result = Database::getInstance()
            ->prepare("SELECT ffname, socialChannels FROM tl_firefighter_departments WHERE id=?")
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
                'website' => $website ?: $facebook,
            ];
        }

        return ['name' => '', 'website' => ''];
    }

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

    public static function findPublishedByPids(
        array $arrPids,
        bool $blnFeatured = null,
        $intLimit = 0,
        $intOffset = 0,
        array $arrOptions = [],
        array $arrFirefighterCategories = []
    ) {
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

        $hasCategoryFilter = !empty($arrFirefighterCategories);
        $filterCategories = array_map('intval', StringUtil::deserialize($arrFirefighterCategories, true));

        /*
         * Bei Kategorie Filter nicht per SQL LIKE auf serialisierte Arrays filtern.
         * Grund: LIKE '%i:2;%' findet auch Array Indizes wie i:2; und nicht nur den Wert 2.
         * Deshalb zuerst alle passenden Datensätze holen und danach in PHP exakt prüfen.
         */
        if ($hasCategoryFilter) {
            $arrOptions['limit'] = 0;
            $arrOptions['offset'] = 0;
        } else {
            $arrOptions['limit'] = $intLimit;
            $arrOptions['offset'] = $intOffset;
        }

        $collection = static::findBy($arrColumns, null, $arrOptions);

        if ($collection === null || !$hasCategoryFilter) {
            return $collection;
        }

        $models = array_filter($collection->getModels(), function ($model) use ($filterCategories) {
            $itemCategories = array_map('intval', StringUtil::deserialize($model->firefightercategories, true));

            return !empty(array_intersect($filterCategories, $itemCategories));
        });

        $models = array_values($models);

        if ($intOffset > 0 || $intLimit > 0) {
            $models = array_slice($models, $intOffset, $intLimit ?: null);
        }

        return !empty($models) ? new Collection($models, static::$strTable) : null;
    }

    public static function countPublishedByPids(
        array $arrPids,
        bool $blnFeatured = null,
        array $arrFirefighterCategories = [],
        array $arrOptions = []
    ): int {
        if (empty($arrPids) || !\is_array($arrPids)) {
            return 0;
        }

        $collection = static::findPublishedByPids(
            $arrPids,
            $blnFeatured,
            0,
            0,
            $arrOptions,
            $arrFirefighterCategories
        );

        return $collection !== null ? count($collection->getModels()) : 0;
    }

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