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
