<?php

declare(strict_types=1);

/*
 * This file is part of Firefighter Bundle for Contao Open Source CMS.
 * 
 * (c) Ronald Boda 2022 <info@coboda.at>
 * @license GPL-3.0-or-later
 * For the full copyright and license information,
 * please view the LICENSE file that was distributed with this source code.
 * @link https://github.com/skipman/firefighter-bundle
 */

namespace Skipman\FirefighterBundle\EventListener;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\ServiceAnnotation\Hook;
use Skipman\FirefighterBundle\Classes\Firefighter;
use Skipman\FirefighterBundle\Models\FirefighterModel;

/**
 * @Hook("replaceInsertTags")
 */
class InsertTagsListener
{
    private const SUPPORTED_TAGS = ['firefighter_url'];
    private ContaoFramework $framework;

    public function __construct(ContaoFramework $framework)
    {
        $this->framework = $framework;
    }

    public function __invoke(string $insertTag, bool $useCache, string $cachedValue, array $flags, array $tags, array $cache, int $_rit, int $_cnt)
    {
        $elements = explode('::', $insertTag);
        $key = strtolower($elements[0]);

        if (\in_array($key, self::SUPPORTED_TAGS, true)) {
            return $this->replaceInsertTags($key, $elements[1], $flags);
        }

        return false;
    }

    private function replaceInsertTags(string $insertTag, string $idOrAlias, array $flags): string
    {
        $this->framework->initialize();

        /** @var FirefighterModel $adapter */
        $adapter = $this->framework->getAdapter(FirefighterModel::class);
        $firefighter = $adapter->findByIdOrAlias($idOrAlias);

        if (null === $firefighter) {
            return '';
        }

        if ('firefighter_url' === $insertTag) {
            /** @var Firefighter $adapter */
            $adapter = $this->framework->getAdapter(Firefighter::class);

            return $adapter->generateFirefighterUrl($firefighter, false, \in_array('absolute', $flags, true));
        }

        return '';
    }
}
