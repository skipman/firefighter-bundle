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

namespace Skipman\FirefighterBundle\Helper;

use Contao\BackendUser;
use Contao\DataContainer;
use Contao\Database;
use Contao\StringUtil;
use Contao\System;
use Contao\Input;

class FirefighterHelper
{
    public static function getDepartments(DataContainer $dc = null): array
    {
        return self::getAllowedDepartmentOptions($dc);
    }

    public static function getDepartmentOptions(): array
    {
        $departments = [];
        $result = Database::getInstance()->execute("SELECT id, ffname FROM tl_firefighter_departments WHERE type='FF' OR type='BTF' ORDER BY ffname ASC");

        while ($result->next()) {
            $departments[(int) $result->id] = $result->ffname;
        }

        return $departments;
    }

    public static function getAllowedDepartmentOptions(DataContainer $dc = null): array
    {
        $user = BackendUser::getInstance();

        if ($user->isAdmin) {
            return self::getDepartmentOptions();
        }

        $selected = [];

        if (null !== $dc && null !== $dc->activeRecord && $dc->activeRecord->membersHomebase) {
            $selected = [(int) $dc->activeRecord->membersHomebase];
        }

        $options = [];
        $result = Database::getInstance()->execute("SELECT id, ffname FROM tl_firefighter_departments WHERE type='FF' OR type='BTF' ORDER BY ffname ASC");

        while ($result->next()) {
            $id = (int) $result->id;
            $isAllowed = self::canAccessHomebase($id, $user);
            $isSelected = in_array($id, $selected, true);

            if (!$isAllowed && !$isSelected) {
                continue;
            }

            $label = $result->ffname;

            if (!$isAllowed && $isSelected) {
                $label .= ' [übergeordnet vergeben]';
            }

            $options[$id] = $label;
        }

        return $options;
    }

    public static function filterAllowedDepartment($value, DataContainer $dc = null): string
    {
        $value = (string) $value;
        $user = BackendUser::getInstance();

        if ($user->isAdmin || '' === $value) {
            return $value;
        }

        if (self::canAccessHomebase((int) $value, $user)) {
            return $value;
        }

        if (null !== $dc && null !== $dc->activeRecord) {
            return (string) $dc->activeRecord->membersHomebase;
        }

        return '';
    }

    private static function canAccessHomebase(int $id, BackendUser $user): bool
    {
        if ($user->isAdmin) {
            return true;
        }

        if (System::getContainer()->has('security.helper')) {
            try {
                if (System::getContainer()
                    ->get('security.helper')
                    ->isGranted('contao_user.firefighterhomebases', (string) $id)) {
                    return true;
                }
            } catch (\Throwable) {
                // Fall back to the legacy Contao permission check.
            }
        }

        return $user->hasAccess((string) $id, 'firefighterhomebases');
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

    public static function getCoursesShortOptions(): array
    {
        $options = [];
        $result = Database::getInstance()->execute("SELECT id, course_short FROM tl_firefighter_courses ORDER BY course_short ASC");

        while ($result->next()) {
            $options[$result->id] = $result->course_short;
        }

        return $options;
    }

    public static function getBadgesShortOptions(): array
    {
        $options = [];
        $result = Database::getInstance()->execute("SELECT id, badge_short FROM tl_firefighter_badges ORDER BY badge_short ASC");

        while ($result->next()) {
            $options[$result->id] = $result->badge_short;
        }

        return $options;
    }

    public static function getAwardsShortOptions(): array
    {
        $options = [];
        $result = Database::getInstance()->execute("SELECT id, award_short FROM tl_firefighter_awards ORDER BY award_short ASC");

        while ($result->next()) {
            $options[$result->id] = $result->award_short;
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

    public static function sanitizeFunctionLocalRows($value): string
    {
        $rows = \Contao\StringUtil::deserialize($value, true);

        $cleaned = array_filter($rows, static function ($row) {
            if (!is_array($row)) {
                return false;
            }

            // Nur behalten, wenn Funktion gesetzt ist
            $function = trim((string) ($row['membersFunctionLocal'] ?? ''));

            return $function !== '';
        });

        return serialize(array_values($cleaned));
    }
    public static function filterFunctionLocalRows($value): array
    {
        $rows = \Contao\StringUtil::deserialize($value, true);

        return array_values(array_filter($rows, static function ($row) {
            if (!is_array($row)) {
                return false;
            }

            $function = trim((string) ($row['membersFunctionLocal'] ?? ''));

            return $function !== '';
        }));
    }
    public static function sanitizeFunctionSectionRows($value): string
    {
        $rows = \Contao\StringUtil::deserialize($value, true);

        $cleaned = array_filter($rows, static function ($row) {
            if (!is_array($row)) {
                return false;
            }

            // Nur behalten, wenn Funktion gesetzt ist
            $function = trim((string) ($row['membersFunctionSection'] ?? ''));

            return $function !== '';
        });

        return serialize(array_values($cleaned));
    }
    public static function filterFunctionSectionRows($value): array
    {
        $rows = \Contao\StringUtil::deserialize($value, true);

        return array_values(array_filter($rows, static function ($row) {
            if (!is_array($row)) {
                return false;
            }

            $function = trim((string) ($row['membersFunctionSection'] ?? ''));

            return $function !== '';
        }));
    }
    public static function sanitizeCourseRows($value): string
    {
        $rows = \Contao\StringUtil::deserialize($value, true);

        $cleaned = array_filter($rows, static function ($row) {
            if (!is_array($row)) {
                return false;
            }

            $course = trim((string) ($row['membersCourse'] ?? ''));

            return '' !== $course;
        });

        return serialize(array_values($cleaned));
    }

    public static function filterCourseRows($value): array
    {
        $rows = \Contao\StringUtil::deserialize($value, true);

        return array_values(array_filter($rows, static function ($row) {
            if (!is_array($row)) {
                return false;
            }

            $course = trim((string) ($row['membersCourse'] ?? ''));

            return '' !== $course;
        }));
    }

    public static function sanitizeBadgeRows($value): string
    {
        $rows = \Contao\StringUtil::deserialize($value, true);

        $cleaned = array_filter($rows, static function ($row) {
            if (!is_array($row)) {
                return false;
            }

            $badge = trim((string) ($row['membersBadge'] ?? ''));

            return '' !== $badge;
        });

        return serialize(array_values($cleaned));
    }

    public static function filterBadgeRows($value): array
    {
        $rows = \Contao\StringUtil::deserialize($value, true);

        return array_values(array_filter($rows, static function ($row) {
            if (!is_array($row)) {
                return false;
            }

            $badge = trim((string) ($row['membersBadge'] ?? ''));

            return '' !== $badge;
        }));
    }

    public static function sanitizeAwardRows($value): string
    {
        $rows = \Contao\StringUtil::deserialize($value, true);

        $cleaned = array_filter($rows, static function ($row) {
            if (!is_array($row)) {
                return false;
            }

            $award = trim((string) ($row['membersAward'] ?? ''));

            return '' !== $award;
        });

        return serialize(array_values($cleaned));
    }

    public static function filterAwardRows($value): array
    {
        $rows = \Contao\StringUtil::deserialize($value, true);

        return array_values(array_filter($rows, static function ($row) {
            if (!is_array($row)) {
                return false;
            }

            $award = trim((string) ($row['membersAward'] ?? ''));

            return '' !== $award;
        }));
    }

    public static function getFirefighterCategoryOptions(): array
    {
        $options = [];
        $result = Database::getInstance()
            ->execute("SELECT id, title FROM tl_firefighter_category ORDER BY title ASC");

        while ($result->next()) {
            $options[$result->id] = $result->title;
        }

        return $options;
    }
     /**
     * @param DataContainer|null $dc
     */
    public static function getAllowedFirefighterCategoryOptions(DataContainer $dc = null): array
    {
        $user = BackendUser::getInstance();

        if ($user->isAdmin) {
            return self::getFirefighterCategoryOptions();
        }

        $allowed = array_map(
            'intval',
            StringUtil::deserialize($user->firefightercategories, true)
        );

        $selected = [];

        if (null !== $dc && null !== $dc->activeRecord) {
            $selected = array_map(
                'intval',
                StringUtil::deserialize($dc->activeRecord->firefightercategories, true)
            );
        }

        $visible = array_values(array_unique(array_merge($allowed, $selected)));

        if (empty($visible)) {
            return [];
        }

        $options = [];
        $result = Database::getInstance()
            ->execute("SELECT id, title FROM tl_firefighter_category ORDER BY title ASC");

        while ($result->next()) {
            $id = (int) $result->id;

            if (!in_array($id, $visible, true)) {
                continue;
            }

            $label = $result->title;

            if (!in_array($id, $allowed, true) && in_array($id, $selected, true)) {
                $label .= ' [übergeordnet vergeben]';
            }

            $options[$id] = $label;
        }

        return $options;
    }

    public static function filterAllowedFirefighterCategories($value, DataContainer $dc = null): array
    {
        $user = BackendUser::getInstance();

        $selected = array_map(
            'intval',
            StringUtil::deserialize($value, true)
        );

        if ($user->isAdmin) {
            return array_values(array_unique($selected));
        }

        $allowed = array_map(
            'intval',
            StringUtil::deserialize($user->firefightercategories, true)
        );

        $existing = [];

        if (null !== $dc && null !== $dc->activeRecord) {
            $existing = array_map(
                'intval',
                StringUtil::deserialize($dc->activeRecord->firefightercategories, true)
            );
        }

        $allowedSelected = array_values(array_intersect($selected, $allowed));
        $protectedExisting = array_values(array_diff($existing, $allowed));

        return array_values(array_unique(array_merge($allowedSelected, $protectedExisting)));
    }

    public function validateFunctionLevel($value, DataContainer $dc)
    {
        $functionOverlocal = Input::post('function_overlocal');

        if ($functionOverlocal && empty($value)) {
            throw new \Exception('Bitte wählen Sie eine Ebene für die überörtliche Funktion aus.');
        }

        return $value;
        }


}
