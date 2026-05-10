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
use Contao\Database;
use Contao\StringUtil;

class FirefighterWebsElement extends ContentElement
{
    protected $strTemplate = 'ce_webs';

    protected function compile()
    {
        $departmentId = $this->webDepartment;

        if ($departmentId) {
            $result = Database::getInstance()->prepare("SELECT ffname, socialChannels FROM tl_firefighter_departments WHERE id = ?")
                                             ->execute($departmentId);

            if ($result->numRows > 0) {
                $this->Template->departmentName = $result->ffname;
                $socialChannels = StringUtil::deserialize($result->socialChannels);

                if (is_array($socialChannels) && !empty($socialChannels)) {
                    foreach ($socialChannels as &$channel) {
                        $channel['platformClass'] = strtolower(str_replace([' ', '(Twitter)'], '', $channel['platform']));
                    }
                    $this->Template->socialChannels = $socialChannels;
                }
            }
        }
    }
}
