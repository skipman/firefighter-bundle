<?php declare(strict_types=1);

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

 use Contao\CoreBundle\ServiceAnnotation\Hook;
 use Skipman\FirefighterBundle\EventListener\DataContainer\MissingLanguageIconListener;
 use Skipman\FirefighterBundle\EventListener\DataContainer\FirefighterChildTableListener;
 use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
 use Terminal42\ChangeLanguage\EventListener\BackendView\ParentChildViewListener;
 use Terminal42\ChangeLanguage\EventListener\DataContainer\ParentTableListener;

/**
 * @Hook("loadDataContainer")
 */
class LoadDataContainerListener
{
    private ParameterBagInterface $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    public function __invoke(string $table): void
    {
        $bundles = $this->params->get('kernel.bundles');

        if (isset($bundles['Terminal42ChangeLanguageBundle'])) {
            switch ($table) {
                case 'tl_firefighter_archive':
                    $listener = new ParentTableListener($table);
                    $listener->register();
                    break;

                case 'tl_firefighter':
                    $listener = new MissingLanguageIconListener();
                    $listener->register($table);

                    $listener = new FirefighterChildTableListener($table);
                    $listener->register();

                    $listener = new ParentChildViewListener($table);
                    $listener->register();
                    break;
            }
        }
    }
}
