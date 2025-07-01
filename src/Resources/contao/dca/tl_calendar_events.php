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

// contao/dca/tl_calendar_events.php
use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['theme'] = [
    'label' => ['Thema', 'Gib das Thema der Übung / Veranstaltung an'],
    'search' => true,
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50', 'maxlength' => 255, 'mandatory' => false],
    'sql' => ['type' => 'string', 'length' => 255, 'default' => '']
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['responsible'] = [
    'label' => ['Verantwortlich','Gib den/die für die Veranstaltung Verantwortliche(n) ein'],
    'search' => true,
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50', 'maxlength' => 255, 'mandatory' => false],
    'sql' => ['type' => 'string', 'length' => 255, 'default' => '']
];

$GLOBALS['TL_DCA']['tl_calendar_events']['fields']['participants'] = [
    'label' => ['Teilnehmer','Wähle die erforderlichen Teilnehmer'],
    'search' => true,
    'inputType' => 'checkbox',
    'options' => [
        'Aktive',
        'Reservisten',
        'Jugend',
        '1. Zug',
        '2. Zug',
        'eingeteilte Mannschaft',
        'Fachchargen'
    ],
    'eval' => ['tl_class' => 'clr', 'multiple' => true],
    'sql' => ['type' => 'blob', 'notnull' =>false, 'default' => '']
];

PaletteManipulator::create()
    ->addField('theme', 'address', PaletteManipulator::POSITION_AFTER)
    ->addField('responsible', 'theme', PaletteManipulator::POSITION_AFTER)
    ->addField('participants','responsible', PaletteManipulator::POSITION_AFTER)
    ->applyToPalette('default', 'tl_calendar_events')
    ->applyToPalette('internal', 'tl_calendar_events')
    ->applyToPalette('article', 'tl_calendar_events')
    ->applyToPalette('external', 'tl_calendar_events')
;