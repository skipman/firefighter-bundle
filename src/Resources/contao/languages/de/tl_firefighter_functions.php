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

$GLOBALS['TL_LANG']['tl_firefighter_functions']['edit'] = ['Bearbeite Funktion ID %s', 'Bearbeite Funktion ID %s'];
$GLOBALS['TL_LANG']['tl_firefighter_functions']['copy'] = ['Funktion kopieren', 'Funktion ID %s kopieren'];
$GLOBALS['TL_LANG']['tl_firefighter_functions']['new'] = ['Neue Funktion anlegen', 'Legt eine neue Funktion an'];
$GLOBALS['TL_LANG']['tl_firefighter_functions']['show'] = ['Funktion anzeigen', 'Details der Funktion ID %s anzeigen'];

$GLOBALS['TL_LANG']['tl_firefighter_functions']['function_legend'] = 'Funktionen';
$GLOBALS['TL_LANG']['tl_firefighter_functions']['function_short'] = ['Funktion Kurz', 'Abkürzung der Funktion'];
$GLOBALS['TL_LANG']['tl_firefighter_functions']['function_long'] = ['Funktion Lang', 'Funktion ausgeschrieben'];
$GLOBALS['TL_LANG']['tl_firefighter_functions']['function_overlocal'] = ['Überörtlich?', 'Anhaken, wenn es sich um eine überörtliche Funktion handelt'];
$GLOBALS['TL_LANG']['tl_firefighter_functions']['function_level'] = ['Ebene der überörtlichen Funktion', 'Wähle, ob diese Funktion auf Abschnitts-, Bezirks- oder Landesebene verwendet wird.'];
$GLOBALS['TL_LANG']['tl_firefighter_functions']['function_level_options'] = [
    'section' => 'Abschnitt',
    'district' => 'Bezirk',
    'state' => 'Land',
    'federal' => 'Bund',
];