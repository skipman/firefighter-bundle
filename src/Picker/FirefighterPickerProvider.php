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

namespace Skipman\FirefighterBundle\Picker;

use Contao\CoreBundle\Picker\AbstractInsertTagPickerProvider;
use Contao\CoreBundle\Picker\DcaPickerProviderInterface;
use Contao\CoreBundle\Picker\PickerConfig;
use Knp\Menu\FactoryInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
 
class FirefighterPickerProvider extends AbstractInsertTagPickerProvider implements DcaPickerProviderInterface
{
    private Security $security;
 
    public function __construct(
        FactoryInterface $menuFactory,
        RouterInterface $router,
        ?TranslatorInterface $translator,
        Security $security)
    {
        parent::__construct($menuFactory, $router, $translator); 
        $this->security = $security;
    }
 
    public function getName(): string
    {
        return 'firefighterPicker';
    }
 
    public function supportsContext($context): bool
    {
        return in_array($context, ['firefighter', 'link'], true) && $this->security->isGranted('contao_user.modules', 'firefighter');
    }
 
    public function supportsValue(PickerConfig $config): bool
    {
        if ('firefighter' === $config->getContext()) {
            return is_numeric($config->getValue());
        }
 
        return $this->isMatchingInsertTag($config);
    }
 
    public function getDcaTable(PickerConfig $config = null): string
    {
        return 'tl_firefighter';
    }
     
    public function getDcaAttributes(PickerConfig $config): array
    {
        $value = $config->getValue();
        $attributes = ['fieldType' => 'radio'];
 
        if ('firefighter' === $config->getContext()) {
            if ($fieldType = $config->getExtra('fieldType')) {
                $attributes['fieldType'] = $fieldType;
            }
 
            if ($value) {
                $attributes['value'] = array_map('intval', explode(',', $value));
            }
 
            return $attributes;
        }
 
        if ($source = $config->getExtra('source')) {
            $attributes['preserveRecord'] = $source;
        }
 
        if ($this->supportsValue($config)) {
            $attributes['value'] = $this->getInsertTagValue($config);
        }
 
        return $attributes;
    }
 
    public function convertDcaValue(PickerConfig $config, $value): string
    {
        if ('firefighter' === $config->getContext()) {
            return (string) $value;
        }
 
        return sprintf($this->getInsertTag($config), $value);
    }
 
    protected function getRouteParameters(PickerConfig $config = null): array
    {
        return ['do' => 'firefighter'];
    }
 
    protected function getDefaultInsertTag(): string
    {
        return '{{firefighter_url::%s}}';
    }
}