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
    public static function getDepartments($dc = null): array
    {
        return self::getDepartmentOptions();
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

    public static function getDepartmentPermissionOptions(): array
    {
        $departments = [];
        $result = Database::getInstance()
            ->execute('SELECT id, ffnumber, ffname FROM tl_firefighter_departments ORDER BY ffnumber ASC, ffname ASC');

        while ($result->next()) {
            $ffnumber = trim((string) $result->ffnumber);
            $ffname = (string) $result->ffname;
            $departments[(int) $result->id] = '' !== $ffnumber ? $ffnumber . ' - ' . $ffname : $ffname;
        }

        return $departments;
    }

    public static function getDepartmentContextOptions(): array
    {
        $departments = [];
        $result = Database::getInstance()->execute("SELECT id, ffname FROM tl_firefighter_departments ORDER BY ffname ASC");

        while ($result->next()) {
            $departments[(int) $result->id] = $result->ffname;
        }

        return $departments;
    }

    public static function getAllowedDepartmentContextIds(?BackendUser $user = null): array
    {
        $user ??= BackendUser::getInstance();

        $ids = [];
        $result = Database::getInstance()->execute("SELECT id FROM tl_firefighter_departments ORDER BY ffname ASC");

        while ($result->next()) {
            $id = (int) $result->id;

            if ($user->isAdmin || self::canAccessHomebase($id, $user)) {
                $ids[] = $id;
            }
        }

        return $ids;
    }   

    public static function getAllowedDepartmentContextOptions($dc = null): array
    {
        $user = BackendUser::getInstance();

        if ($user->isAdmin) {
            return self::getDepartmentContextOptions();
        }

        $selected = [];

        if ($dc instanceof DataContainer && null !== $dc->activeRecord && $dc->activeRecord->departmentId) {
            $selected = [(int) $dc->activeRecord->departmentId];
        }

        $options = [];
        $result = Database::getInstance()->execute("SELECT id, ffname FROM tl_firefighter_departments ORDER BY ffname ASC");

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

    public static function filterAllowedDepartmentContext($value, $dc = null): string
    {
        $value = (string) $value;
        $user = BackendUser::getInstance();

        if ($user->isAdmin || '' === $value) {
            return $value;
        }

        if (self::canAccessHomebase((int) $value, $user)) {
            return $value;
        }

        if ($dc instanceof DataContainer && null !== $dc->activeRecord) {
            return (string) $dc->activeRecord->departmentId;
        }

        return '';
    }


    public static function getAllowedDepartmentIds(?BackendUser $user = null): array
    {
        $user ??= BackendUser::getInstance();

        $ids = [];
        $result = Database::getInstance()->execute('SELECT id FROM tl_firefighter_departments ORDER BY ffnumber ASC, ffname ASC');

        while ($result->next()) {
            $id = (int) $result->id;

            if ($user->isAdmin || self::canAccessHomebase($id, $user)) {
                $ids[] = $id;
            }
        }

        return $ids;
    }

    public static function canAccessDepartment(int $id, ?BackendUser $user = null): bool
    {
        $user ??= BackendUser::getInstance();

        return self::canAccessHomebase($id, $user);
    }

    public static function getAllowedDepartmentOptions($dc = null): array
    {
        $user = BackendUser::getInstance();

        if ($user->isAdmin) {
            return self::getDepartmentOptions();
        }

        $selected = [];

        if ($dc instanceof DataContainer && null !== $dc->activeRecord && $dc->activeRecord->membersHomebase) {
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

    public static function filterAllowedDepartment($value, $dc = null): string
    {
        $value = (string) $value;
        $user = BackendUser::getInstance();

        if ($user->isAdmin || '' === $value) {
            return $value;
        }

        if (self::canAccessHomebase((int) $value, $user)) {
            return $value;
        }

        if ($dc instanceof DataContainer && null !== $dc->activeRecord) {
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
		
		private static function normalizeAliasSource(string $value): string
    {
        $value = trim($value);

        if ('' === $value) {
            return '';
        }

        // Repair common UTF-8-as-Latin1 mojibake before transliteration.
        $value = strtr($value, [
            'Ã„' => 'Ä',
            'Ã–' => 'Ö',
            'Ãœ' => 'Ü',
            'Ã¤' => 'ä',
            'Ã¶' => 'ö',
            'Ã¼' => 'ü',
            'ÃŸ' => 'ß',
        ]);

        return strtr($value, [
            'Ä' => 'Ae',
            'Ö' => 'Oe',
            'Ü' => 'Ue',
            'ä' => 'ae',
            'ö' => 'oe',
            'ü' => 'ue',
            'ß' => 'ss',
            'ẞ' => 'SS',
        ]);
    }

    public static function generateCategoryAlias(mixed $value, DataContainer $dc): string
    {
        $departmentId = self::getSubmittedOrActiveDepartmentId($dc);

        $title = '';

        if (isset($_POST['title'])) {
            $title = trim((string) $_POST['title']);
        } elseif (null !== $dc->activeRecord && isset($dc->activeRecord->title)) {
            $title = trim((string) $dc->activeRecord->title);
        }

        if ($departmentId < 1 || '' === $title) {
            return (string) $value;
        }

        $department = Database::getInstance()
            ->prepare('SELECT ffname FROM tl_firefighter_departments WHERE id=?')
            ->limit(1)
            ->execute($departmentId);

        if (!$department->numRows) {
            return (string) $value;
        }

        $aliasBase = trim((string) $department->ffname) . ' ' . $title;
        $alias = StringUtil::standardize(self::normalizeAliasSource($aliasBase));

        $existing = Database::getInstance()
            ->prepare('SELECT id FROM tl_firefighter_category WHERE alias=? AND id<>?')
            ->limit(1)
            ->execute($alias, (int) $dc->id);

        if ($existing->numRows) {
            throw new \RuntimeException(sprintf('Der Alias "%s" ist bereits vorhanden.', $alias));
        }

        return $alias;
    }

    public static function getAllowedScopeLevelOptions($dc = null): array
    {
        $user = BackendUser::getInstance();
        $departmentId = self::getSubmittedOrActiveDepartmentId($dc);

        if ($departmentId > 0) {
            $type = self::getDepartmentType($departmentId);

            if (self::isAllowedScopeLevel($type)) {
                return [$type => $type];
            }

            return [];
        }

        if ($user->isAdmin) {
            return [
                'BFK' => 'BFK',
                'AFK' => 'AFK',
                'FF' => 'FF',
                'BTF' => 'BTF',
            ];
        }

        $allowedDepartmentIds = self::getAllowedDepartmentContextIds($user);

        if ([] === $allowedDepartmentIds) {
            return [];
        }

        $result = Database::getInstance()
            ->execute('SELECT DISTINCT type FROM tl_firefighter_departments WHERE id IN(' . implode(',', array_map('intval', $allowedDepartmentIds)) . ') ORDER BY type ASC');

        $options = [];

        while ($result->next()) {
            $type = (string) $result->type;

            if (self::isAllowedScopeLevel($type)) {
                $options[$type] = $type;
            }
        }

        return $options;
    }

    public static function filterScopeLevelFromDepartment($value, $dc = null): string
    {
        $departmentId = self::getSubmittedOrActiveDepartmentId($dc);
        $type = self::getDepartmentType($departmentId);

        if (self::isAllowedScopeLevel($type)) {
            return $type;
        }

        return 'FF';
    }

    public static function updateCategoryScopeLevelFromDepartment(DataContainer $dc): void
    {
        if (!$dc->id) {
            return;
        }

        $category = Database::getInstance()
            ->prepare('SELECT departmentId FROM tl_firefighter_category WHERE id=?')
            ->limit(1)
            ->execute($dc->id);

        if ($category->numRows < 1) {
            return;
        }

        $type = self::getDepartmentType((int) $category->departmentId);

        if (!self::isAllowedScopeLevel($type)) {
            $type = 'FF';
        }

        Database::getInstance()
            ->prepare('UPDATE tl_firefighter_category SET scopeLevel=? WHERE id=?')
            ->execute($type, $dc->id);
    }

    private static function getSubmittedOrActiveDepartmentId($dc = null): int
    {
        $postDepartmentId = Input::post('departmentId');

        if (null !== $postDepartmentId && '' !== $postDepartmentId) {
            return (int) $postDepartmentId;
        }

        if ($dc instanceof DataContainer && null !== $dc->activeRecord && isset($dc->activeRecord->departmentId)) {
            return (int) $dc->activeRecord->departmentId;
        }

        return 0;
    }

    private static function getDepartmentType(int $departmentId): string
    {
        if ($departmentId < 1) {
            return '';
        }

        $department = Database::getInstance()
            ->prepare('SELECT type FROM tl_firefighter_departments WHERE id=?')
            ->limit(1)
            ->execute($departmentId);

        return $department->numRows ? (string) $department->type : '';
    }

    private static function isAllowedScopeLevel(string $scopeLevel): bool
    {
        return in_array($scopeLevel, ['BFK', 'AFK', 'FF', 'BTF'], true);
    }

    public static function getFirefighterCategoryOptions(): array
    {
        $options = [];

        $result = Database::getInstance()
            ->execute('SELECT c.id, c.title, c.departmentId, d.ffname AS departmentName
                FROM tl_firefighter_category c
                LEFT JOIN tl_firefighter_departments d ON c.departmentId=d.id
                ORDER BY d.ffname ASC, c.title ASC');

        while ($result->next()) {
            $departmentName = trim((string) ($result->departmentName ?? ''));
            $title = trim((string) $result->title);

            $options[(int) $result->id] = '' !== $departmentName
                ? $departmentName . ' - ' . $title
                : $title;
        }

        return $options;
    }
    /**
     * @param mixed $dc
     */
    public static function getAllowedFirefighterCategoryOptions($dc = null): array
    {
        $user = BackendUser::getInstance();

        if ($user->isAdmin) {
            return self::getFirefighterCategoryOptions();
        }

        $allowedDepartmentIds = self::getAllowedDepartmentContextIds($user);
        $selected = [];

        if ($dc instanceof DataContainer && null !== $dc->activeRecord) {
            $selected = array_map(
                'intval',
                StringUtil::deserialize($dc->activeRecord->firefightercategories, true)
            );
        }

        $options = [];
        $result = Database::getInstance()

            ->execute("SELECT c.id, c.title, c.departmentId, d.ffname AS departmentName
                FROM tl_firefighter_category c
                LEFT JOIN tl_firefighter_departments d ON c.departmentId=d.id
                ORDER BY d.ffname ASC, c.title ASC");

        while ($result->next()) {
            $id = (int) $result->id;
            $departmentId = (int) $result->departmentId;
            $isAllowed = in_array($departmentId, $allowedDepartmentIds, true);
            $isSelected = in_array($id, $selected, true);

            if (!$isAllowed && !$isSelected) {
                continue;
            }

            $label = self::formatFirefighterCategoryLabel(
                (string) $result->title,
                (string) ($result->departmentName ?? '')
            );

            if (!$isAllowed && $isSelected) {
                $label .= ' [übergeordnet vergeben]';
            }

            $options[$id] = $label;
        }

        return $options;
    }

    public static function filterAllowedFirefighterCategories($value, $dc = null): array
    {
        $user = BackendUser::getInstance();

        $selected = array_map(
            'intval',
            StringUtil::deserialize($value, true)
        );

        if ($user->isAdmin) {
            return $selected;
        }

        $allowedDepartmentIds = self::getAllowedDepartmentContextIds($user);
        $allowedCategoryIds = self::getCategoryIdsByDepartmentIds($allowedDepartmentIds);

        $existing = [];

        if ($dc instanceof DataContainer && null !== $dc->activeRecord) {
            $existing = array_map(
                'intval',
                StringUtil::deserialize($dc->activeRecord->firefightercategories, true)
            );
        }

        $allowedSelected = array_values(array_intersect($selected, $allowedCategoryIds));
        $protectedExisting = array_values(array_diff($existing, $allowedCategoryIds));

        return array_values(array_unique(array_merge($allowedSelected, $protectedExisting)));
    }

    public static function getCategoryIdsByDepartmentIds(array $departmentIds): array
    {
        $departmentIds = array_values(array_filter(array_map('intval', $departmentIds)));

        if ([] === $departmentIds) {
            return [];
        }

        $result = Database::getInstance()
            ->execute('SELECT id FROM tl_firefighter_category WHERE departmentId IN(' . implode(',', $departmentIds) . ')');

        return array_map('intval', $result->fetchEach('id'));
    }


    public static function formatFirefighterCategoryLabel(string $title, string $departmentName = ''): string
    {
        $title = trim($title);
        $departmentName = trim($departmentName);

        if ('' === $departmentName) {
            return $title;
        }

        if ('' === $title) {
            return $departmentName;
        }

        return $departmentName . ' - ' . $title;
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
