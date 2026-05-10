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

use Contao\Date;
use Contao\System;
use Contao\Module;
use Contao\StringUtil;
use Contao\FilesModel;
use Contao\FrontendUser;
use Contao\ContentModel;
use Contao\FrontendTemplate;
use Contao\Database;
use Skipman\FirefighterBundle\Classes\Firefighter;
use Skipman\FirefighterBundle\Models\FirefighterArchiveModel;
use Skipman\FirefighterBundle\Models\FirefighterCategoryModel;

abstract class ModuleFirefighter extends Module
{
    /**
     * Sort out protected archives.
     */
    protected function sortOutProtected(array $arrArchives): array
    {
        if (empty($arrArchives) || !is_array($arrArchives)) {
            return $arrArchives;
        }

        $this->import(FrontendUser::class, 'User');
        $objArchive = FirefighterArchiveModel::findMultipleByIds($arrArchives);
        $arrArchives = [];

        if (null !== $objArchive) {
            while ($objArchive->next()) {
                if ($objArchive->protected) {
                  $tokenChecker = System::getContainer()->get('contao.security.token_checker');
                  if (!$tokenChecker->isFrontendUserLoggedIn() || !is_array($this->User->groups)) {
                      continue;
                    }

                    $groups = StringUtil::deserialize($objArchive->groups);

                    if (empty($groups) || !is_array($groups) || !count(array_intersect($groups, $this->User->groups))) {
                        continue;
                    }
                }

                $arrArchives[] = $objArchive->id;
            }
        }

        return $arrArchives;
    }

    /**
     * Parse an item and return it as string.
     *
     * @param FirefighterModel  $objItem
     * @param bool              $blnAddArchive
     * @param mixed             $strClass
     * @param mixed             $intCount
     *
     * @throws \Exception
     */
    protected function parseItem($objItem, $blnAddArchive = false, $strClass = '', $intCount = 0): string
    {
        global $objPage;

        $objTemplate = new FrontendTemplate($this->firefighter_template);
        $objTemplate->setData($objItem->row());

        $objTemplate->class = ('' !== $objItem->cssClass ? ' '.$objItem->cssClass : '').$strClass;
        $objTemplate->headline = $objItem->headline;
        $objTemplate->linkHeadline = $this->generateLink($objItem->headline, $objItem, $blnAddArchive);
        $objTemplate->more = $this->generateLink($GLOBALS['TL_LANG']['MSC']['more'], $objItem, $blnAddArchive, true);
        $objTemplate->link = Firefighter::generateFirefighterUrl($objItem, $blnAddArchive);
        $objTemplate->count = $intCount; // see #5708
        $objTemplate->text = '';
        $objTemplate->hasText = false;
        $objTemplate->hasTeaser = false;
        $objTemplate->membersFirstname = $objItem->membersFirstname;
        $objTemplate->membersLastname = $objItem->membersLastname;
        $objTemplate->membersRank = $objItem->membersRank;

        // Block Dienstränge
        $objTemplate->membersRankShortAbbr = '';
        $objTemplate->membersRankLongAbbr = '';
        $objTemplate->rankImage = '';

        if ($objItem->membersRank) {
            $result = Database::getInstance()->prepare("SELECT rank_short, rank_long, singleSRC FROM tl_firefighter_ranks WHERE id=?")
                                            ->execute($objItem->membersRank);

            if ($result->numRows > 0) {
                $rankShort = $result->rank_short;
                $rankLong = $result->rank_long;
                $rankHonory = (bool) $objItem->membersRankHonory;

                if ($rankHonory) {
                    $objTemplate->membersRankShortAbbr = "E" . $rankShort;
                    $objTemplate->membersRankLongAbbr = "Ehren" . strtolower($rankLong);
                } else {
                    $objTemplate->membersRankShortAbbr = $rankShort;
                    $objTemplate->membersRankLongAbbr = $rankLong;
                }

                // Fetch rank image
                if ($result->singleSRC) {
                    $objRankImage = FilesModel::findByUuid($result->singleSRC);

                    if ($objRankImage !== null && is_file(System::getContainer()->getParameter('kernel.project_dir') . '/' . $objRankImage->path)) {
                        $objTemplate->rankImage = $objRankImage->path;
                    }
                }
            }
        }

        // Initialize the arrays for local and section functions
        $objTemplate->membersFunctionLocal = [];
        $objTemplate->membersFunctionSection = [];

        // Deserialize the functions
        $localFunctions = StringUtil::deserialize($objItem->membersFunctionLocalWizard, true);
        $sectionFunctions = StringUtil::deserialize($objItem->membersFunctionSectionWizard, true);

        // Process local functions
        if (!empty($localFunctions)) {
            foreach ($localFunctions as $function) {
                if (isset($function['membersFunctionLocal'])) {
                    $result = Database::getInstance()->prepare("SELECT function_short, function_long FROM tl_firefighter_functions WHERE id=?")
                                                    ->execute($function['membersFunctionLocal']);

                    if ($result->numRows > 0) {
                        $objTemplate->membersFunctionLocal[] = [
                            'short' => $result->function_short,
                            'long' => $result->function_long,
                            'period' => $function['membersFunctionLocalPeriod'] ?? ''
                        ];
                    }
                }
            }
        }

        // Process section functions
        if (!empty($sectionFunctions)) {
            foreach ($sectionFunctions as $function) {
                if (isset($function['membersFunctionSection'])) {
                    $result = Database::getInstance()->prepare("SELECT function_short, function_long FROM tl_firefighter_functions WHERE id=?")
                                                    ->execute($function['membersFunctionSection']);

                    if ($result->numRows > 0) {
                        $objTemplate->membersFunctionSection[] = [
                            'short' => $result->function_short,
                            'long' => $result->function_long,
                            'period' => $function['membersFunctionSectionPeriod'] ?? ''
                        ];
                    }
                }
            }
        }

        // Clean the RTE output
        if ($objItem->teaser) {
            $objTemplate->hasTeaser = true;
            $objTemplate->teaser = $objItem->teaser;
            $objTemplate->teaser = StringUtil::encodeEmail($objTemplate->teaser);
        }

        // Display the "read more" button for external/article links
        if ('default' !== $objItem->source) {
            $objTemplate->text = true;
        } // Compile the firefighter text
        else {
            $objElement = ContentModel::findPublishedByPidAndTable($objItem->id, 'tl_firefighter');

            if (null !== $objElement) {
                while ($objElement->next()) {
                    $objTemplate->text .= self::getContentElement($objElement->current());
                }
            }

            $objTemplate->hasText = static fn () => ContentModel::countPublishedByPidAndTable($objItem->id, 'tl_firefighter') > 0;
        }

        // Add the meta information
        $objTemplate->date = Date::parse($objPage->dateFormat, $objItem->date);
        $objTemplate->timestamp = $objItem->date;

        if ($objItem->firefightercategories) {
            $objTemplate->firefightercategories = '';
            $objCategories = [];
            $objTemplate->category_models = [];
            $categories = StringUtil::deserialize($objItem->firefightercategories);

            foreach ($categories as $category) {
                $objFirefighterCategoryModel = FirefighterCategoryModel::findByPk($category);
                $objTemplate->category_models[] = $objFirefighterCategoryModel;
                $objCategories[] = $objFirefighterCategoryModel->alias;

                if (!$objTemplate->category_titles) {
                    $objTemplate->category_titles = '<ul class="level_1"><li>'.$objFirefighterCategoryModel->title.'</li>';
                } else {
                    $objTemplate->category_titles .= '<li>'.$objFirefighterCategoryModel->title.'</li>';
                }
            }
            $objTemplate->category_titles .= '</ul>';
            $objTemplate->categories .= implode(',', $objCategories);
        }

        $objTemplate->addImage = false;

        // Add an image
        if ($objItem->addImage && '' !== $objItem->singleSRC) {
            $objModel = FilesModel::findByUuid($objItem->singleSRC);

            $projectDir = System::getContainer()->getParameter('kernel.project_dir');

            if (null !== $objModel && is_file($projectDir.'/'.$objModel->path)) {
                // Do not override the field now that we have a model registry (siehe #6303)
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

                // Link to the firefighter reader if no image link has been defined (see #30)
                if (!$objTemplate->fullsize && !$objTemplate->imageUrl && $objTemplate->text) {
                    // Unset the image title attribute
                    $picture = $objTemplate->picture;
                    unset($picture['title']);
                    $objTemplate->picture = $picture;

                    // Link to the firefighter reader
                    $objTemplate->href = $objTemplate->link;
                    $objTemplate->linkTitle = StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['readMore'], $objItem->headline), true);

                    // If the external link is opened in a new window, open the image link in a new window, too
                    if ('external' === $objTemplate->source && $objTemplate->target) {
                        $objTemplate->attributes .= ' target="_blank"';
                    }
                }
            }
        }

        return $objTemplate->parse();
    }

    /**
     * Parse one or more items and return them as array.
     *
     * @param FirefighterModel $objArticles
     * @param bool             $blnAddArchive
     *
     * @throws \Exception
     */
    protected function parseItems($objArticles, $blnAddArchive = false): array
    {
        $limit = $objArticles->count();

        if ($limit < 1) {
            return [];
        }

        $count = 0;
        $arrArticles = [];
        $uuids = [];

        foreach ($objArticles as $objArticle) {
            if ($objArticle->addImage && $objArticle->singleSRC) {
                $uuids[] = $objArticle->singleSRC;
            }
        }

        // Preload all images in one query so they are loaded into the model registry
        FilesModel::findMultipleByUuids($uuids);

        foreach ($objArticles as $objArticle) {
            $arrArticles[] = $this->parseItem($objArticle, $blnAddArchive, (1 === ++$count ? ' first' : '').($count === $limit ? ' last' : '').(0 === $count % 2 ? ' odd' : ' even'), $count);
        }

        return $arrArticles;
    }

    /**
     * Generate a link and return it as string.
     *
     * @param mixed $strLink
     * @param mixed $objItem
     * @param mixed $blnAddArchive
     * @param mixed $blnIsReadMore
     *
     * @throws \Exception
     */
    protected function generateLink($strLink, $objItem, $blnAddArchive = false, $blnIsReadMore = false): string
    {
        // Internal link
        if ('external' !== $objItem->source) {
            return sprintf(
                '<a href="%s" title="%s">%s%s</a>',
                Firefighter::generateFirefighterUrl($objItem, $blnAddArchive),
                StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['readMore'], $objItem->headline), true),
                $strLink,
                ($blnIsReadMore ? ' <span class="invisible">'.$objItem->headline.'</span>' : '')
            );
        }

        // Ampersand URIs
        $strArticleUrl = StringUtil::ampersand($objItem->url);

        global $objPage;

        $attributes = '';

        if ($objItem->target) {
            $attributes = ('xhtml' === $objPage->outputFormat ? ' onclick="return !window.open(this.href)"' : ' target="_blank"');
        }

        // External link
        return sprintf(
            '<a href="%s" title="%s"%s>%s</a>',
            $strArticleUrl,
            StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['open'], $strArticleUrl)),
            $attributes,
            $strLink
        );
    }
}
