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

namespace Skipman\FirefighterBundle\Modules;

use Contao\BackendTemplate;
use Contao\Config;
use Contao\CoreBundle\Exception\InternalServerErrorException;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\Environment;
use Contao\Input;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Database;
use Contao\FilesModel;
use Contao\FrontendTemplate;
use Skipman\FirefighterBundle\Models\FirefighterModel;
use Skipman\FirefighterBundle\Classes\Firefighter;

class ModuleFirefighterReader extends ModuleFirefighter
{
    protected $strTemplate = 'mod_firefighterreader';

    public function generate()
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### '.mb_strtoupper($GLOBALS['TL_LANG']['FMD']['firefighterreader'][0]).' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', ['do' => 'themes', 'table' => 'tl_module', 'act' => 'edit', 'id' => $this->id]));

            return $objTemplate->parse();
        }

        // Do not index or cache the page if no firefighter item has been specified
        if (!Input::get('auto_item')) {
            global $objPage;
            $objPage->noSearch = 1;
            $objPage->cache = 0;

            return '';
        }

        $this->firefighter_archives = $this->sortOutProtected(StringUtil::deserialize($this->firefighter_archives));

        if (empty($this->firefighter_archives) || !\is_array($this->firefighter_archives)) {
            throw new InternalServerErrorException('The news reader ID '.$this->id.' has no archives specified.', $this->id);
        }

        return parent::generate();
    }

    protected function compile(): void
    {
        if ($this->overviewPage) {
            $this->Template->referer = PageModel::findById($this->overviewPage)->getFrontendUrl();
            $this->Template->back = $this->customLabel ?: $GLOBALS['TL_LANG']['MSC']['goBack'];
        } else {
            $this->Template->referer = 'javascript:history.go(-1)';
            $this->Template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];
        }

        $objItem = FirefighterModel::findPublishedByParentAndIdOrAlias(Input::get('auto_item'), $this->firefighter_archives);

        if (null === $objItem) {
            throw new PageNotFoundException('Page not found: '.Environment::get('uri'));
        }

        $this->Template->items = $this->parseItem($objItem);
    }

    /**
     * Parse a single item and return it as string.
     *
     * @param FirefighterModel $objItem
     * @param bool $blnAddArchive
     * @param string $strClass
     * @param int $intCount
     *
     * @return string
     */
    protected function parseItem($objItem, $blnAddArchive = false, $strClass = '', $intCount = 0): string
    {
        global $objPage;
        $objTemplate = new FrontendTemplate($this->firefighter_template);
        $objTemplate->setData($objItem->row());

        // Get the local functions
        $localFunctions = StringUtil::deserialize($objItem->membersFunctionLocalWizard, true);
        $filteredLocalFunctions = [];

        if (is_array($localFunctions) && !empty($localFunctions)) {
            foreach ($localFunctions as $function) {
                if (!empty($function['membersFunctionLocal'])) {
                    $function['long'] = $this->getFunctionLongName($function['membersFunctionLocal']);
                    $function['period'] = $function['membersFunctionLocalPeriod'] ?? '';
                    $function['LfromM'] = $function['membersFunctionLocalFromMonth'] ?? '';
                    $function['LfromY'] = $function['membersFunctionLocalFromYear'] ?? '';
                    $function['LuntilM'] = $function['membersFunctionLocalUntilMonth'] ?? '';
                    $function['LuntilY'] = $function['membersFunctionLocalUntilYear'] ?? '';
                    $filteredLocalFunctions[] = $function; // Add only if not empty
                }
            }
        }

        if (!empty($filteredLocalFunctions)) {
            $objTemplate->membersFunctionLocal = $filteredLocalFunctions;
        }

        // Get the section functions
        $sectionFunctions = StringUtil::deserialize($objItem->membersFunctionSectionWizard, true);
        $filteredSectionFunctions = [];

        if (is_array($sectionFunctions) && !empty($sectionFunctions)) {
            foreach ($sectionFunctions as $function) {
                if (!empty($function['membersFunctionSection'])) {
                    $function['long'] = $this->getFunctionLongName($function['membersFunctionSection']);
                    $function['period'] = $function['membersFunctionSectionPeriod'] ?? '';
                    $function['SfromM'] = $function['membersFunctionSectionFromMonth'] ?? '';
                    $function['SfromY'] = $function['membersFunctionSectionFromYear'] ?? '';
                    $function['SuntilM'] = $function['membersFunctionSectionUntilMonth'] ?? '';
                    $function['SuntilY'] = $function['membersFunctionSectionUntilYear'] ?? '';
                    $filteredSectionFunctions[] = $function; // Add only if not empty
                }
            }
        }

        if (!empty($filteredSectionFunctions)) {
            $objTemplate->membersFunctionSection = $filteredSectionFunctions;
        }

        // Get the course data
        $courses = StringUtil::deserialize($objItem->membersCoursesWizard, true);
        $courseData = [];

        if (!empty($courses)) {
            foreach ($courses as $entry) {
                if (!empty($entry['membersCourse'])) {
                    $course = Database::getInstance()
                        ->prepare("SELECT course_short, course_long FROM tl_firefighter_courses WHERE id = ?")
                        ->execute($entry['membersCourse']);

                    if ($course->numRows > 0) {
                        $courseData[] = [
                            'short' => $course->course_short,
                            'long'  => $course->course_long,
                            'year'  => $entry['membersCourseYear'] ?? '',
                        ];
                    }
                }
            }
        }

        if (!empty($courseData)) {
            $objTemplate->membersCourses = $courseData;
        }

        // Get the badge data
        $badges = StringUtil::deserialize($objItem->membersBadgesWizard, true);
        $badgeData = [];

        if (!empty($badges)) {
            foreach ($badges as $entry) {
                if (!empty($entry['membersBadge'])) {
                    $badge = Database::getInstance()
                        ->prepare("SELECT badge_short, badge_long FROM tl_firefighter_badges WHERE id = ?")
                        ->execute($entry['membersBadge']);

                    if ($badge->numRows > 0) {
                        $badgeData[] = [
                            'short' => $badge->badge_short,
                            'long'  => $badge->badge_long,
                            'year'  => $entry['membersBadgeYear'] ?? '',
                        ];
                    }
                }
            }
        }

        if (!empty($badgeData)) {
            $objTemplate->membersBadges = $badgeData;
        }

        // Get the award data
        $awards = StringUtil::deserialize($objItem->membersAwardsWizard, true);
        $awardData = [];

        if (!empty($awards)) {
            foreach ($awards as $entry) {
                if (!empty($entry['membersAward'])) {
                    $award = Database::getInstance()
                        ->prepare("SELECT award_short, award_long FROM tl_firefighter_awards WHERE id = ?")
                        ->execute($entry['membersAward']);

                    if ($award->numRows > 0) {
                        $awardData[] = [
                            'short' => $award->award_short,
                            'long'  => $award->award_long,
                            'year'  => $entry['membersAwardYear'] ?? '',
                        ];
                    }
                }
            }
        }

        if (!empty($awardData)) {
            $objTemplate->membersAwards = $awardData;
        }








        // Add other item data to the template
        $objTemplate->class = ('' !== $objItem->cssClass ? ' '.$objItem->cssClass : '').$strClass;
        $objTemplate->headline = $objItem->headline;
        $objTemplate->linkHeadline = $this->generateLink($objItem->headline, $objItem, $blnAddArchive);
        $objTemplate->more = $this->generateLink($GLOBALS['TL_LANG']['MSC']['more'], $objItem, $blnAddArchive, true);
        $objTemplate->link = Firefighter::generateFirefighterUrl($objItem, $blnAddArchive);
        $objTemplate->count = $intCount;
        $objTemplate->text = '';
        $objTemplate->hasText = false;
        $objTemplate->hasTeaser = false;
        $objTemplate->membersFirstname = $objItem->membersFirstname;
        $objTemplate->membersLastname = $objItem->membersLastname;
        $objTemplate->membersRank = $objItem->membersRank;
        $objTemplate->membersRankShortAbbr = '';
        $objTemplate->membersRankLongAbbr = '';
        $objTemplate->rankImage = '';

        // Fetch homebase details
        $homebaseDetails = $this->getHomebaseDetails($objItem->membersHomebase);
        $objTemplate->membersHomebase = $homebaseDetails['name'];
        $objTemplate->membersHomebaseWebsite = $homebaseDetails['website'];
        $objTemplate->membersEmail = $objItem->membersEmail;
        $objTemplate->membersPhone = $objItem->membersPhone;
        $objTemplate->membersPhoneFormatted = $this->formatPhoneNumber($objItem->membersPhone);

        // Add the rank image
        if ($objItem->membersRank) {
            $rankInfo = $this->getMembersRank($objItem->membersRank, $objItem->membersRankHonory);
            $objTemplate->membersRankShortAbbr = $rankInfo['short'];
            $objTemplate->membersRankLongAbbr = $rankInfo['long'];
            $objTemplate->rankImage = $rankInfo['image'];
        }

        // Add the member image
        $this->getMemberImage($objItem, $objTemplate);

        return $objTemplate->parse();
    }

    private function getMemberImage($objItem, $objTemplate)
    {
        $objTemplate->addImage = false;

        // Add an image
        if ($objItem->addImage && '' !== $objItem->singleSRC) {
            $objModel = FilesModel::findByUuid($objItem->singleSRC);

            $projectDir = System::getContainer()->getParameter('kernel.project_dir');

            if (null !== $objModel && is_file($projectDir.'/'.$objModel->path)) {
                $arrArticle = $objItem->row();

                $imgSize = $objItem->size ?: null;

                // Override the default image size
                if ('' !== $this->imgSize) {
                    $size = StringUtil::deserialize($this->imgSize);

                    if ($size[0] > 0 || $size[1] > 0 || is_numeric($size[2])) {
                        $arrArticle['size'] = $this->imgSize;
                        $imgSize = $this->imgSize;
                    }
                }

                $figure = System::getContainer()
                    ->get('contao.image.studio')
                    ->createFigureBuilder()
                    ->from($objModel)
                    ->setSize($imgSize)
                    ->setOverwriteMetadata($objItem->getOverwriteMetadata())
                    ->enableLightbox((bool) $objItem->fullsize)
                    ->buildIfResourceExists();

                $figure?->applyLegacyTemplateData($objTemplate);
            }
        }
    }

    private function getMembersRank($membersRank, $membersRankHonory)
    {
        $result = Database::getInstance()->prepare("SELECT rank_short, rank_long, singleSRC FROM tl_firefighter_ranks WHERE id=?")
                                        ->execute($membersRank);

        if ($result->numRows > 0) {
            $rankShort = $result->rank_short;
            $rankLong = $result->rank_long;

            if ($membersRankHonory) {
                return [
                    'short' => "E" . $rankShort,
                    'long' => "Ehren" . strtolower($rankLong),
                    'image' => $this->getRankImage($result->singleSRC)
                ];
            } else {
                return [
                    'short' => $rankShort,
                    'long' => $rankLong,
                    'image' => $this->getRankImage($result->singleSRC)
                ];
            }
        }

        return null;
    }

    private function getRankImage($singleSRC)
    {
        if ($singleSRC) {
            $objRankImage = FilesModel::findByUuid($singleSRC);

            if ($objRankImage !== null && is_file(System::getContainer()->getParameter('kernel.project_dir') . '/' . $objRankImage->path)) {
                return $objRankImage->path;
            }
        }

        return null;
    }

    private function getHomebaseDetails($homebaseId): array
    {
        $result = Database::getInstance()
            ->prepare("SELECT ffname, socialChannels FROM tl_firefighter_departments WHERE id=?")
            ->execute($homebaseId);

        if ($result->numRows > 0) {
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

        return [
            'name' => '',
            'website' => '',
        ];
    }

    protected function getFunctionLongName($functionId): string
    {
        $function = Database::getInstance()
            ->prepare("SELECT function_long FROM tl_firefighter_functions WHERE id=?")
            ->execute($functionId);

        return $function->numRows ? $function->function_long : '';
    }

    /**
     * Format phone number to remove all non-digit characters and add the international dialing code.
     *
     * @param string $phoneNumber
     *
     * @return string
     */
    protected function formatPhoneNumber($phoneNumber): string
    {
        // Remove all non-digit characters
        $formattedPhone = preg_replace('/\D/', '', $phoneNumber);
        // Remove leading zero and add international dialing code
        return 'tel:+43' . ltrim($formattedPhone, '0');
    }
}
