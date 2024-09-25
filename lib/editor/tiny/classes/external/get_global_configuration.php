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

namespace editor_tiny\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_warnings;

/**
 * External function that returns the global TinyMCE configuration.
 *
 * @package     editor_tiny
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_global_configuration extends external_api {

    /**
     * Describes the parameters of the external function.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Executes the external function.
     *
     * @return array
     */
    public static function execute(): array {
        global $PAGE;

        $siteconfig = get_config('editor_tiny');

        $installedlanguages = [];
        foreach (get_string_manager()->get_list_of_translations(true) as $lang => $name) {
            $installedlanguages[] = ['lang' => $lang, 'name' => $name];
        }

        $availablelanguages = [];
        foreach (get_string_manager()->get_list_of_languages() as $lang => $name) {
            $availablelanguages[] = ['lang' => $lang, 'name' => $name];
        }

        $config = [
            'branding' => property_exists($siteconfig, 'branding') ? !empty($siteconfig->branding) : true,

            // Language options.
            'installedlanguages' => $installedlanguages,
            'availablelanguages' => $availablelanguages,

            // Plugin configuration.
            'plugins' => [],
        ];

        $enabledplugins = \editor_tiny\plugininfo\tiny::get_enabled_plugins();

        if (isset($enabledplugins['accessibilitychecker'])) {
            $config['plugins']['accessibilitychecker'] = [];
        }

        if (isset($enabledplugins['equation'])) {
            $config['plugins']['equation'] = [
                'librarygroup1' => explode("\n", trim(get_config('tiny_equation', 'librarygroup1'))),
                'librarygroup2' => explode("\n", trim(get_config('tiny_equation', 'librarygroup2'))),
                'librarygroup3' => explode("\n", trim(get_config('tiny_equation', 'librarygroup3'))),
                'librarygroup4' => explode("\n", trim(get_config('tiny_equation', 'librarygroup4'))),
                'texdocsurl' => get_docs_url('Using_TeX_Notation'),
            ];
        }

        if (isset($enabledplugins['h5p'])) {
            $config['plugins']['h5p'] = [];
        }

        if (isset($enabledplugins['html'])) {
            $config['plugins']['html'] = [];
        }

        if (isset($enabledplugins['link'])) {
            $config['plugins']['link'] = [];
        }

        if (isset($enabledplugins['media'])) {
            $config['plugins']['media'] = [];
        }

        if (isset($enabledplugins['noautolink'])) {
            $config['plugins']['noautolink'] = [];
        }

        if (isset($enabledplugins['premium'])) {
            $config['plugins']['premium'] = [
                'apikey' => get_config('tiny_premium', 'apikey'),
                'premiumplugins' => \tiny_premium\manager::get_enabled_plugins(),
            ];
        }

        if (isset($enabledplugins['recordrtc'])) {
            [$screenwidth, $screenheight] = explode(',', get_config('tiny_recordrtc', 'screensize'));
            $config['plugins']['recordrtc'] = [
                'allowedtypes' => explode(',', get_config('tiny_recordrtc', 'allowedtypes')),
                'allowedpausing' => (bool) get_config('tiny_recordrtc', 'allowedpausing'),
                'audiobitrate' => (int) get_config('tiny_recordrtc', 'audiobitrate'),
                'videobitrate' => (int) get_config('tiny_recordrtc', 'videobitrate'),
                'screenbitrate' => (int) get_config('tiny_recordrtc', 'screenbitrate'),
                'audiotimelimit' => (int) get_config('tiny_recordrtc', 'audiotimelimit'),
                'videotimelimit' => (int) get_config('tiny_recordrtc', 'videotimelimit'),
                'screentimelimit' => (int) get_config('tiny_recordrtc', 'screentimelimit'),
                'screenwidth' => (int) $screenwidth,
                'screenheight' => (int) $screenheight,
                'maxrecsize' => get_max_upload_file_size(),
            ];
        }

        return $config;
    }

    /**
     * Describes the return structure of the external function.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'branding' => new external_value(PARAM_BOOL, 'Display the TinyMCE logo'),
            'installedlanguages' => new external_multiple_structure(
                new external_single_structure([
                    'lang' => new external_value(PARAM_LANG, 'Language code'),
                    'name' => new external_value(PARAM_RAW, 'Language name'),
                ]),
                'List of installed languages',
            ),
            'availablelanguages' => new external_multiple_structure(
                new external_single_structure([
                    'lang' => new external_value(PARAM_RAW, 'Language code'),
                    'name' => new external_value(PARAM_RAW, 'Language name'),
                ]),
                'List of available languages',
            ),
            'plugins' => new external_single_structure([
                'accessibilitychecker' => new external_single_structure([], '', VALUE_OPTIONAL),
                'equation' => new external_single_structure([
                    'librarygroup1' => new external_multiple_structure(new external_value(PARAM_RAW), 'TeX commands listed on the operators tab'),
                    'librarygroup2' => new external_multiple_structure(new external_value(PARAM_RAW), 'TeX commands listed on the arrows tab'),
                    'librarygroup3' => new external_multiple_structure(new external_value(PARAM_RAW), 'TeX commands listed on the Greek symbols tab'),
                    'librarygroup4' => new external_multiple_structure(new external_value(PARAM_RAW), 'TeX commands listed on the advanced tab'),
                    'texdocsurl' => new external_value(PARAM_URL, 'URL of the TeX notation documentation'),
                ], '', VALUE_OPTIONAL),
                'h5p' => new external_single_structure([], '', VALUE_OPTIONAL),
                'html' => new external_single_structure([], '', VALUE_OPTIONAL),
                'link' => new external_single_structure([], '', VALUE_OPTIONAL),
                'media' => new external_single_structure([], '', VALUE_OPTIONAL),
                'noautolink' => new external_single_structure([], '', VALUE_OPTIONAL),
                'premium' => new external_single_structure([
                    'apikey' => new external_value(PARAM_ALPHANUM, 'API key for Tiny Premium'),
                    'premiumplugins' => new external_multiple_structure(
                        new external_value(PARAM_PLUGIN, 'Premium plugin name'),
                        'List of enabled premium plugins'),
                ], '', VALUE_OPTIONAL),
                'recordrtc' => new external_single_structure([
                    'allowedtypes' => new external_multiple_structure(
                        new external_value(PARAM_ALPHA),
                        'Allowed recording types: audio, video and/or screen',
                    ),
                    'allowedpausing' => new external_value(PARAM_BOOL, 'Allow pausing'),
                    'audiobitrate' => new external_value(PARAM_INT, 'Quality of audio recording'),
                    'videobitrate' => new external_value(PARAM_INT, 'Quality of video recording'),
                    'screenbitrate' => new external_value(PARAM_INT, 'Quality of screen recording'),
                    'audiotimelimit' => new external_value(PARAM_INT, 'Maximum recording length for audio clips'),
                    'videotimelimit' => new external_value(PARAM_INT, 'Maximum recording length for video clips'),
                    'screentimelimit' => new external_value(PARAM_INT, 'Maximum recording length for screen recording'),
                    'screenwidth' => new external_value(PARAM_INT, 'Screen recording width'),
                    'screenheight' => new external_value(PARAM_INT, 'Screen recording height'),
                    'maxrecsize' => new external_value(PARAM_INT, 'Maximum recording size'),
                ], '', VALUE_OPTIONAL),
            ], 'Configuration of enabled plugins'),
            'warnings' => new external_warnings(),
        ]);
    }
}
