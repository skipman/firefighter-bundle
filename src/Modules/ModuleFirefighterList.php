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
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Environment;
use Contao\Input;
use Contao\Model\Collection;
use Contao\Pagination;
use Contao\StringUtil;
use Contao\System;
use Contao\FrontendTemplate;
use Contao\ContentModel;
use Contao\FilesModel;
use Contao\Database;
use Skipman\FirefighterBundle\Models\FirefighterCategoryModel;
use Skipman\FirefighterBundle\Models\FirefighterModel;
use Skipman\FirefighterBundle\Classes\Firefighter;

/**
 * Class ModuleFirefighterList.
 *
 * Front end module "firefighter list".
 */
class ModuleFirefighterList extends ModuleFirefighter
{
    /**
     * Template.
     *
     * @var string
     */
    protected $strTemplate = 'mod_firefighterlist';

    /**
     * Display a wildcard in the back end.
     */
    public function generate(): string
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
            $objTemplate = new BackendTemplate('be_wildcard');
            $objTemplate->wildcard = '### '.$GLOBALS['TL_LANG']['FMD']['firefighterlist'][0].' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = StringUtil::specialcharsUrl(System::getContainer()->get('router')->generate('contao_backend', ['do' => 'themes', 'table' => 'tl_module', 'act' => 'edit', 'id' => $this->id]));
            return $objTemplate->parse();
        }

        return parent::generate();
            throw new \Exception('TEST');
    }

    /**
     * Generate the module.
     *
     * @throws \Exception
     */
    protected function compile(): void
    {
        // Add the "reset categories" link
        if ($this->firefighter_filter_reset) {
            $this->Template->firefighter_filter_reset = $GLOBALS['TL_LANG']['MSC']['filter_reset'][0];
            $this->Template->firefighter_filter_resetTitle = $GLOBALS['TL_LANG']['MSC']['filter_reset'][1];
        }

        // Get the selected categories for filtering
        $selectedCategories = StringUtil::deserialize($this->filter_firefightercategories, true);

        $sortedCategories = [];
        if (!empty($selectedCategories)) {
            $objCategories = FirefighterCategoryModel::findMultipleByIds($selectedCategories);

            if ($objCategories !== null) {
                // Hole die Kategorien in einem Array mit ID als Schlüssel
                $categoriesById = [];
                while ($objCategories->next()) {
                    if ($objCategories->alias !== null && $objCategories->title !== null) {
                        $categoriesById[$objCategories->id] = [
                            'alias' => $objCategories->alias,
                            'title' => $objCategories->title,
                        ];
                    }
                }

                // Sortiere die Kategorien nach der Reihenfolge in $selectedCategories
                foreach ($selectedCategories as $categoryId) {
                    if (isset($categoriesById[$categoryId])) {
                        $sortedCategories[] = $categoriesById[$categoryId];
                    }
                }
            }
        }

        // Übergabe der sortierten Kategorien an das Template
        $this->Template->firefightercategories = $sortedCategories;

        // Prüfen, ob der Filter "kommando" im Parameter enthalten ist
        $isKommandoFilter = false;
        if ($this->filter_firefightercategories) {
            $selectedCategory = Input::get('filter');
            if (stripos((string) $selectedCategory, 'kommando') !== false) {
                $isKommandoFilter = true;
            }
        }

        // Check if group filter should be displayed
        $showGroupFilter = $this->firefighter_filter !== 0;

        // Pass the flag to the template
        $this->Template->showGroupFilter = $showGroupFilter;

        $limit = null;
        $offset = (int) $this->skipFirst;

        // Maximum number of items
        if ($this->numberOfItems > 0) {
            $limit = $this->numberOfItems;
        }

        // Handle featured firefighter items
        if ('featured' === $this->firefighter_featured) {
            $blnFeatured = true;
        } elseif ('unfeatured' === $this->firefighter_featured) {
            $blnFeatured = false;
        } else {
            $blnFeatured = null;
        }

        $arrPids = StringUtil::deserialize($this->firefighter_archives);
        $arrFirefighterCategoryIds = [];

        // Pre filter items based on filter_firefightercategories
        if ($this->filter_firefightercategories) {
            $arrFirefighterCategoryIds = $selectedCategories;
        }

        // Add firefighter pagination
        // Get the total number of items
        $intTotal = $this->countItems($arrPids, $blnFeatured, $arrFirefighterCategoryIds);

        if ($intTotal < 1) {
            return;
        }

        $total = $intTotal - $offset;

        // Split the results
        if ($this->perPage > 0 && (!isset($limit) || $this->numberOfItems > $this->perPage)) {
            // Adjust the overall limit
            if (isset($limit)) {
                $total = min($limit, $total);
            }

            // Get the current page
            $id = 'page_n'.$this->id;
            $page = Input::get($id) ?? 1;

            // Do not index or cache the page if the page number is outside the range
            if ($page < 1 || $page > max(ceil($total / $this->perPage), 1)) {
                throw new PageNotFoundException('Page not found: '.Environment::get('uri'));
            }

            // Set limit and offset
            $limit = (int) $this->perPage;
            $offset += (max($page, 1) - 1) * $this->perPage;
            $skip = (int) $this->skipFirst;

            // Overall limit
            if ($offset + $limit > $total + $skip) {
                $limit = $total + $skip - $offset;
            }

            // Add the pagination menu
            $objPagination = new Pagination($total, $this->perPage, Config::get('maxPaginationLinks'), $id);
            $this->Template->pagination = $objPagination->generate("\n  ");
        }

        $objItems = $this->fetchItems(
            $arrPids,
            $blnFeatured,
            ($limit ?: 0),
            $offset,
            $arrFirefighterCategoryIds,
            $isKommandoFilter
        );

        if (null !== $objItems) {
            $this->Template->items = $this->parseItems($objItems, false, $isKommandoFilter);
        }
    }

    /**
     * Count the total matching items.
     *
     * @param array $firefighterArchives
     * @param bool  $blnFeatured
     * @param array $arrFirefighterCategories
     */
    protected function countItems($firefighterArchives, $blnFeatured, $arrFirefighterCategories): int
    {
        return FirefighterModel::countPublishedByPids($firefighterArchives, $blnFeatured, $arrFirefighterCategories);
    }

    /**
     * Fetch the matching items.
     *
     * @param array $firefighterArchives
     * @param bool  $blnFeatured
     * @param int   $limit
     * @param int   $offset
     * @param array $arrFirefighterCategories
     *
     * @return Collection|array<FirefighterModel>|FirefighterModel|null
     */
    protected function fetchItems($firefighterArchives, $blnFeatured, $limit, $offset, $arrFirefighterCategories, $isKommandoFilter = false)
    {
        // Wichtig: zuerst alle passenden Datensätze holen, dann sortieren, dann offset/limit anwenden
        $items = FirefighterModel::findPublishedByPids($firefighterArchives, $blnFeatured, 0, 0, [], $arrFirefighterCategories);

        if (null === $items) {
            return null;
        }

        $arrItems = $items->getModels();

        usort($arrItems, function ($a, $b) {
            $aPriority = $this->getCombinedCommandPriority($a);
            $bPriority = $this->getCombinedCommandPriority($b);

            if ($aPriority !== $bPriority) {
                return $aPriority <=> $bPriority;
            }

            return strcmp((string) $a->membersLastname, (string) $b->membersLastname)
                ?: strcmp((string) $a->membersFirstname, (string) $b->membersFirstname);
        });

        // Pagination erst nach der Sortierung anwenden
        if ($offset > 0 || $limit > 0) {
            $arrItems = array_slice($arrItems, $offset, $limit ?: null);
        }

        return new Collection($arrItems, FirefighterModel::getTable());
    }

    /**
     * Ermittelt die Priorität passend zum verwendeten Template.
     * firefighter_short nutzt lokale Funktionen.
     * firefighter_short_abschnitt nutzt Abschnittsfunktionen.
     * firefighter_short_bezirk nutzt Bezirksfunktionen.
     * firefighter_short_land nutzt Landesfunktionen.
     */
    protected function getCombinedCommandPriority($member): int
    {
        $template = (string) $this->firefighter_template;

        if (str_contains($template, 'bezirk')) {
            return $this->getOverlocalPriority($member, 'district');
        }

        if (str_contains($template, 'land')) {
            return $this->getOverlocalPriority($member, 'state');
        }

        if (str_contains($template, 'abschnitt')) {
            return $this->getOverlocalPriority($member, 'section');
        }

        return $this->getLocalPriority($member);
    }

    protected function getLocalPriority($member): int
    {
        $localOrder = ['3', '4', '5', '83', '7'];

        $functionId = $this->getHighestPriorityFunctionFromWizard(
            $member->membersFunctionLocalWizard ?? null,
            'membersFunctionLocal',
            'membersFunctionLocalUntilYear',
            'membersFunctionLocalUntilMonth',
            $localOrder
        );

        if ($functionId === null) {
            return PHP_INT_MAX;
        }

        $position = array_search($functionId, $localOrder, true);

        return $position !== false ? $position : PHP_INT_MAX;
    }

    protected function getOverlocalPriority($member, string $level): int
    {
        $orders = [
            'section' => ['61', '62', '82', '86'],
            'district' => ['65', '66', '128', '96'],
            'state' => ['71', '72'],
        ];

        if (!isset($orders[$level])) {
            return PHP_INT_MAX;
        }

        $functionId = $this->getHighestPriorityOverlocalFunction(
            $member->membersFunctionSectionWizard ?? null,
            $level,
            $orders[$level]
        );

        if ($functionId === null) {
            return PHP_INT_MAX;
        }

        $position = array_search($functionId, $orders[$level], true);

        return $position !== false ? $position : PHP_INT_MAX;
    }

    protected function getTemplateFunctionLevel(): ?string
    {
        $template = (string) $this->firefighter_template;

        if (str_contains($template, 'bezirk')) {
            return 'district';
        }

        if (str_contains($template, 'land')) {
            return 'state';
        }

        if (str_contains($template, 'abschnitt')) {
            return 'section';
        }

        return null;
    }

    protected function getFunctionOrderForLevel(?string $level): array
    {
        $orders = [
            'local' => ['3', '4', '5', '83', '7'],
            'section' => ['61', '62', '82', '86'],
            'district' => ['65', '66', '128', '96'],
            'state' => ['71', '72'],
        ];

        return $orders[$level] ?? [];
    }

    protected function getHighestPriorityOverlocalFunction($wizardData, string $level, array $priorityOrder): ?string
    {
        $functions = StringUtil::deserialize($wizardData, true);

        if (!is_array($functions) || empty($functions)) {
            return null;
        }

        $currentYear = (int) date('Y');
        $currentMonth = (int) date('m');

        foreach ($priorityOrder as $priorityFunction) {
            foreach ($functions as $function) {
                if ((string) ($function['membersFunctionSection'] ?? '') !== (string) $priorityFunction) {
                    continue;
                }

                if (!$this->isFunctionActive(
                    $function,
                    'membersFunctionSectionUntilYear',
                    'membersFunctionSectionUntilMonth',
                    $currentYear,
                    $currentMonth
                )) {
                    continue;
                }

                if ($this->getFunctionLevel($priorityFunction) === $level) {
                    return (string) $priorityFunction;
                }
            }
        }

        return null;
    }

    protected function getFunctionLevel($functionId): string
    {
        $result = Database::getInstance()
            ->prepare("SELECT function_level FROM tl_firefighter_functions WHERE id=? AND function_overlocal='1'")
            ->execute($functionId);

        if ($result->numRows > 0) {
            return (string) $result->function_level;
        }

        return '';
    }

    protected function isFunctionActive(
        array $function,
        string $yearKey,
        string $monthKey,
        int $currentYear,
        int $currentMonth
    ): bool {
        $endYear = (int) ($function[$yearKey] ?? 0);
        $endMonth = (int) ($function[$monthKey] ?? 0);

        return (
            $endYear === 0
            || $endYear > $currentYear
            || ($endYear === $currentYear && ($endMonth === 0 || $endMonth >= $currentMonth))
        );
    }

    /**
     * Liefert die erste aktive Funktion aus einem Wizard gemäß Prioritätsreihenfolge.
     */
    protected function getHighestPriorityFunctionFromWizard(
        $wizardData,
        string $functionKey,
        string $yearKey,
        string $monthKey,
        array $priorityOrder
    ): ?string {
        $functions = StringUtil::deserialize($wizardData, true);

        if (!is_array($functions) || empty($functions)) {
            return null;
        }

        $currentYear = (int) date('Y');
        $currentMonth = (int) date('m');

        foreach ($priorityOrder as $priorityFunction) {
            foreach ($functions as $function) {
                if (!isset($function[$functionKey])) {
                    continue;
                }

                if ((string) $function[$functionKey] !== (string) $priorityFunction) {
                    continue;
                }

                $endYear = (int) ($function[$yearKey] ?? 0);
                $endMonth = (int) ($function[$monthKey] ?? 0);

                $isActive = (
                    $endYear === 0
                    || $endYear > $currentYear
                    || ($endYear === $currentYear && $endMonth >= $currentMonth)
                );

                if ($isActive) {
                    return (string) $priorityFunction;
                }
            }
        }

        return null;
    }

    /**
     * Wählt aus aktiven Funktionen die passende Funktion anhand der Priorität.
     * Falls keine der Funktionen in der Prioritätsliste vorkommt, wird die erste aktive Funktion verwendet.
     */
    protected function getPrioritizedActiveFunction(array $functions, string $functionKey, array $priorityOrder): ?array
    {
        if (empty($functions)) {
            return null;
        }

        foreach ($priorityOrder as $priorityId) {
            foreach ($functions as $function) {
                if ((string) ($function[$functionKey] ?? '') === (string) $priorityId) {
                    return $function;
                }
            }
        }

        return reset($functions) ?: null;
    }

    /**
     * Parse the items and return them as array.
     *
     * @param Collection|FirefighterModel[] $objItems
     * @param bool                          $blnAddArchive
     *
     * @return array
     */
    protected function parseItems($objItems, $blnAddArchive = false, $isKommandoFilter = false): array
    {
        $arrItems = [];

        foreach ($objItems as $i => $objItem) {
            $arrItems[] = $this->parseItem($objItem, $blnAddArchive, '', $i, $isKommandoFilter);
        }

        return $arrItems;
    }

    /**
     * Parse a single item and return it as string.
     *
     * @param FirefighterModel $objItem
     * @param bool             $blnAddArchive
     * @param string           $strClass
     * @param int              $intCount
     */
    protected function parseItem($objItem, $blnAddArchive = false, $strClass = '', $intCount = 0, $isKommandoFilter = false): string
    {
        global $objPage;
        $objTemplate = new FrontendTemplate($this->firefighter_template);
        $objTemplate->setData($objItem->row());

        // check if category is 'kommando'
        if ($isKommandoFilter) {
            $priority = $this->getCombinedCommandPriority($objItem);
            $objTemplate->highlightFunction = $priority !== PHP_INT_MAX ? 'Kommando Rang aktiv' : null;
        }

        // Get the local functions
        $localFunctions = StringUtil::deserialize($objItem->membersFunctionLocalWizard, true);
        if (is_array($localFunctions) && !empty($localFunctions)) {
            // Filter the functions to exclude those with a past end date
            $filteredLocalFunctions = array_filter($localFunctions, function ($function) {
                $currentYear = (int) date('Y');
                $currentMonth = (int) date('m');

                if (!empty($function['membersFunctionLocalUntilYear']) || !empty($function['membersFunctionLocalUntilMonth'])) {
                    $endYear = (int) ($function['membersFunctionLocalUntilYear'] ?? 0);
                    $endMonth = (int) ($function['membersFunctionLocalUntilMonth'] ?? 1);

                    if ($endYear < $currentYear || ($endYear === $currentYear && $endMonth < $currentMonth)) {
                        return false;
                    }
                }

                return true;
            });

            if (!empty($filteredLocalFunctions)) {
                $localPriorityOrder = ['3', '4', '5', '83', '7'];
                $selectedFunction = $this->getPrioritizedActiveFunction(
                    $filteredLocalFunctions,
                    'membersFunctionLocal',
                    $localPriorityOrder
                );

                if ($selectedFunction !== null) {
                    $functionDetails = $this->getFunctionDetails($selectedFunction['membersFunctionLocal']);

                    $selectedFunction['short'] = $functionDetails['short'];
                    $selectedFunction['long'] = $functionDetails['long'];
                    $selectedFunction['period'] = $selectedFunction['membersFunctionLocalPeriod'] ?? '';

                    $objTemplate->membersFunctionLocal = [$selectedFunction];
                }
            }
        }

        // Get the section / supra local functions
        $sectionFunctions = StringUtil::deserialize($objItem->membersFunctionSectionWizard, true);
        if (is_array($sectionFunctions) && !empty($sectionFunctions)) {
            // Filter the functions to exclude those with a past end date
            $filteredSectionFunctions = array_filter($sectionFunctions, function ($function) {
                $currentYear = (int) date('Y');
                $currentMonth = (int) date('m');

                if (!empty($function['membersFunctionSectionUntilYear']) || !empty($function['membersFunctionSectionUntilMonth'])) {
                    $endYear = (int) ($function['membersFunctionSectionUntilYear'] ?? 0);
                    $endMonth = (int) ($function['membersFunctionSectionUntilMonth'] ?? 1);

                    if ($endYear < $currentYear || ($endYear === $currentYear && $endMonth < $currentMonth)) {
                        return false;
                    }
                }

                return true;
            });

            if (!empty($filteredSectionFunctions)) {
                $templateLevel = $this->getTemplateFunctionLevel();
                $priorityOrder = $this->getFunctionOrderForLevel($templateLevel);
                $selectedFunction = null;

                if ($templateLevel !== null && !empty($priorityOrder)) {
                    foreach ($priorityOrder as $priorityId) {
                        foreach ($filteredSectionFunctions as $function) {
                            if ((string) ($function['membersFunctionSection'] ?? '') !== (string) $priorityId) {
                                continue;
                            }

                            if ($this->getFunctionLevel($priorityId) === $templateLevel) {
                                $selectedFunction = $function;
                                break 2;
                            }
                        }
                    }
                }

                if ($selectedFunction === null) {
                    $selectedFunction = reset($filteredSectionFunctions) ?: null;
                }

                if ($selectedFunction !== null) {
                    $functionDetails = $this->getFunctionDetails($selectedFunction['membersFunctionSection']);

                    $selectedFunction['short'] = $functionDetails['short'];
                    $selectedFunction['long'] = $functionDetails['long'];
                    $selectedFunction['period'] = $selectedFunction['membersFunctionSectionPeriod'] ?? '';

                    $objTemplate->membersFunctionSection = [$selectedFunction];
                }
            }
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

        if ($objItem->membersRank) {
            $result = Database::getInstance()
                ->prepare("SELECT rank_short, rank_long, singleSRC FROM tl_firefighter_ranks WHERE id=?")
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

        // Clean the RTE output
        if ($objItem->teaser) {
            $objTemplate->hasTeaser = true;
            $objTemplate->teaser = $objItem->teaser;
            $objTemplate->teaser = StringUtil::encodeEmail($objTemplate->teaser);
        }

        // Display the "read more" button for external/article links
        if ('default' !== $objItem->source) {
            $objTemplate->text = true;
        } else {
            $objElement = ContentModel::findPublishedByPidAndTable($objItem->id, 'tl_firefighter');

            if (null !== $objElement) {
                while ($objElement->next()) {
                    $objTemplate->text .= self::getContentElement($objElement->current());
                }
            }

            $objTemplate->hasText = static fn () => ContentModel::countPublishedByPidAndTable($objItem->id, 'tl_firefighter') > 0;
        }

        // Add the meta information
        if ($objItem->firefightercategories) {
            $objTemplate->firefightercategories = '';
            $objCategories = [];
            $objTemplate->category_models = [];
            $categories = StringUtil::deserialize($objItem->firefightercategories);

            foreach ($categories as $category) {
                $objFirefighterCategoryModel = FirefighterCategoryModel::findByPk($category);
                if ($objFirefighterCategoryModel !== null) {
                    $objTemplate->category_models[] = $objFirefighterCategoryModel;
                    $objCategories[] = $objFirefighterCategoryModel->alias;

                    if (!$objTemplate->category_titles) {
                        $objTemplate->category_titles = '<ul class="level_1"><li>'.$objFirefighterCategoryModel->title.'</li>';
                    } else {
                        $objTemplate->category_titles .= '<li>'.$objFirefighterCategoryModel->title.'</li>';
                    }
                }
            }
            $objTemplate->category_titles .= '</ul>';
            $objTemplate->firefightercategories .= implode(',', $objCategories);
        }

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

                // Link to the firefighter reader if no image link has been defined
                if (!$objTemplate->fullsize && !$objTemplate->imageUrl && $objTemplate->text) {
                    $picture = $objTemplate->picture;
                    unset($picture['title']);
                    $objTemplate->picture = $picture;

                    $objTemplate->href = $objTemplate->link;
                    $objTemplate->linkTitle = StringUtil::specialchars(sprintf($GLOBALS['TL_LANG']['MSC']['readMore'], $objItem->headline), true);

                    if ('external' === $objTemplate->source && $objTemplate->target) {
                        $objTemplate->attributes .= ' target="_blank"';
                    }
                }
            }
        }

        return $objTemplate->parse();
    }

    /**
     * Format phone number to remove all non digit characters and add the international dialing code.
     *
     * @param string $phoneNumber
     */
    protected function formatPhoneNumber($phoneNumber): string
    {
        $formattedPhone = preg_replace('/\D/', '', $phoneNumber);
        return 'tel:+43' . ltrim((string) $formattedPhone, '0');
    }

    protected function getFunctionDetails($functionId): array
    {
        $function = Database::getInstance()
            ->prepare("SELECT function_short, function_long FROM tl_firefighter_functions WHERE id=?")
            ->execute($functionId);

        if ($function->numRows) {
            return [
                'short' => $function->function_short,
                'long' => $function->function_long,
            ];
        }

        return ['short' => '', 'long' => ''];
    }

    /**
     * Fetch homebase details from the database.
     *
     * @param int $homebaseId
     */
    protected function getHomebaseDetails($homebaseId): array
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
}