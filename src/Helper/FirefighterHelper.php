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

namespace Skipman\FirefighterBundle\Helper;

use Contao\Database;
use Contao\StringUtil;

class FirefighterHelper
{
    public static function getDepartments(): array
    {
        $departments = [];
        $result = Database::getInstance()->execute("SELECT id, ffname FROM tl_firefighter_departments WHERE type='FF' OR type='BTF' ORDER BY ffname ASC");

        while ($result->next()) {
            $departments[$result->id] = $result->ffname;
        }

        return $departments;
    }

    public static function getRankShortOptions(): array
    {
        $options = [];
        $result = Database::getInstance()->execute("SELECT id, rank_short FROM tl_firefighter_ranks ORDER BY rank_short ASC");

        while ($result->next()) {
            $options[$result->id] = $result->rank_short;
        }

        return $options;
    }

    public static function getFunctionLocalShortOptions(): array
    {
        $options = [];
        $result = Database::getInstance()->execute("SELECT id, function_short FROM tl_firefighter_functions WHERE function_overlocal = 0 ORDER BY function_short ASC");

        while ($result->next()) {
            $options[$result->id] = $result->function_short;
        }

        return $options;
    }

    public static function getFunctionSectionShortOptions(): array
    {
        $options = [];
        $result = Database::getInstance()->execute("SELECT id, function_short FROM tl_firefighter_functions WHERE function_overlocal = 1 ORDER BY function_short ASC");

        while ($result->next()) {
            $options[$result->id] = $result->function_short;
        }

        return $options;
    }

    public static function getVehiclesByDepartment($dc)
    {
        $vehicles = [];

        if ($dc instanceof \MenAtWork\MultiColumnWizardBundle\Contao\Widgets\MultiColumnWizard) {
            // Get the active row index
            $activeRowIndex = $dc->activeRow;
            // Get the value of the active row
            $activeRowValue = $dc->value[$activeRowIndex] ?? null;

            if (isset($activeRowValue['ffname']) && $activeRowValue['ffname'] != '') {
                $ffnameId = $activeRowValue['ffname'];
            } else {
                return $vehicles;
            }
        } else {
            return $vehicles;
        }

        if ($ffnameId) {
            $result = Database::getInstance()->prepare("SELECT fleet FROM tl_firefighter_departments WHERE id=?")
                                            ->execute($ffnameId);

            if ($result->numRows) {
                $fleetData = StringUtil::deserialize($result->fleet, true);

                foreach ($fleetData as $fleet) {
                    if (isset($fleet['vehicle'])) {
                        $vehicleData = Database::getInstance()->prepare("SELECT vehicle_short FROM tl_firefighter_vehicles WHERE id=?")
                                                ->execute($fleet['vehicle']);
                        if ($vehicleData->numRows) {
                            $vehicleRow = $vehicleData->fetchAssoc();
                            $vehicles[$fleet['vehicle']] = $vehicleRow['vehicle_short'];
                        }
                    }
                }
            }
        }

        return $vehicles;
    }
}
