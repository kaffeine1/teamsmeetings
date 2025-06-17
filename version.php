<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Version information for mod_teamsmeetings
 *
 * @package    mod_teamsmeetings
 * @copyright  2024 Michele Dipace
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'mod_teamsmeetings';
$plugin->version = 2025061602;  // YYYYMMDDXX format - Ho mantenuto la tua versione aggiornata
$plugin->requires = 2022112800; // Moodle 4.1
$plugin->maturity = MATURITY_STABLE;
$plugin->release = '1.0.1'; // Ho mantenuto la versione di release più alta
$plugin->cron = 300; // Ho aggiunto la riga del cron dalla tua branch di sviluppo
