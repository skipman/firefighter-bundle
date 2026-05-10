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

namespace Skipman\FirefighterBundle\ContentElement;

use Contao\ContentElement;
use Contao\StringUtil;
use Contao\Database;
use Contao\PageModel;

class FirefighterResourcesElement extends ContentElement
{
    protected $strTemplate = 'ce_resources';

    protected function compile()
    {
        $arrData = [];
        $firefighterDetails = StringUtil::deserialize($this->firefighterDetails);

        if (is_array($firefighterDetails)) {
            foreach ($firefighterDetails as $detail) {
                if (!empty($detail['ffname']) || !empty($detail['vehicles']) || !empty($detail['team'])) {
                    $departmentData = Database::getInstance()->prepare("SELECT * FROM tl_firefighter_departments WHERE id=?")->execute($detail['ffname'])->fetchAssoc();
                    if ($departmentData) {
                        $selectedVehicles = StringUtil::deserialize($detail['vehicles'], true);
                        $vehicles = [];
                        foreach (StringUtil::deserialize($departmentData['fleet'], true) as $fleet) {
                            if (in_array($fleet['vehicle'], $selectedVehicles)) {
                                $vehicleData = Database::getInstance()->prepare("SELECT vehicle_short FROM tl_firefighter_vehicles WHERE id=?")->execute($fleet['vehicle'])->fetchAssoc();
                                if ($vehicleData) {
                                    if ($fleet['link']) {
                                        $page = PageModel::findByPk($fleet['link']);
                                        if ($page !== null) {
                                            $url = $page->getFrontendUrl();
                                            $vehicles[] = sprintf('<a href="%s">%s</a>', $url, $vehicleData['vehicle_short']);
                                        } else {
                                            $vehicles[] = $vehicleData['vehicle_short'];
                                        }
                                    } elseif ($fleet['url']) {
                                        $vehicles[] = sprintf('<a href="%s">%s</a>', $fleet['url'], $vehicleData['vehicle_short']);
                                    } else {
                                        $vehicles[] = $vehicleData['vehicle_short'];
                                    }
                                }
                            }
                        }
                        $arrData[] = [
                            'ffname' => $departmentData['ffname'],
                            'vehicles' => implode(', ', $vehicles),
                            'team' => $detail['team']
                        ];
                    }
                }
            }
        }

        $filteredOtherOrganisations = [];
        $otherOrganisationDetails = StringUtil::deserialize($this->otherOrganisationDetails);
        if (is_array($otherOrganisationDetails)) {
            foreach ($otherOrganisationDetails as $organisation) {
                if (!empty($organisation['otherOrganisationName'])) {
                    $filteredOtherOrganisations[] = $organisation;
                }
            }
        }

        $this->Template->departments = $arrData;
        $this->Template->otherOrganisations = $filteredOtherOrganisations;
    }
}
