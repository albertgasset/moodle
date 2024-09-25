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
use core_external\external_single_structure;
use core_external\external_value;
use core_external\external_warnings;

/**
 * External function that returns the TinyMCE configuration for a context.
 *
 * @package     editor_tiny
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_context_configuration extends external_api {

    /**
     * Describes the parameters of the external function.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'contextlevel' => new external_value(PARAM_ALPHA, 'Context level: system, user, coursecat, course, module or block'),
            'instanceid' => new external_value(PARAM_INT, 'Id of item associated with the context level'),
        ]);
    }

    /**
     * Executes the external function.
     *
     * @param string $contextlevel
     * @param int $instanceid
     * @return array
     */
    public static function execute(string $contextlevel, int $instanceid): array {
        global $PAGE;
        [
            'contextlevel' => $contextlevel,
            'instanceid' => $instanceid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'contextlevel' => $contextlevel,
            'instanceid' => $instanceid,
        ]);

        $classname = \core\context_helper::parse_external_level($contextlevel);
        if (!$classname) {
            throw new \invalid_parameter_exception('Invalid context level = '. $contextlevel);
        }
        $context = $classname::instance($instanceid);
        self::validate_context($context);

        $config = [
            'plugins' => [],
        ];

        $enabledplugins = \editor_tiny\plugininfo\tiny::get_enabled_plugins();

        if (isset($enabledplugins['accessibilitychecker'])) {
            $config['plugins']['accessibilitychecker'] = [];
        }

        if (isset($enabledplugins['equation'])) {
            $texexample = '$$\pi$$';
            // Format a string with the active filter set.
            // If it is modified - we assume that some sort of text filter is working in this context.
            $formattedtext = format_text($texexample, true, ['context' => $context]);
            $config['plugins']['equation'] = [
                'texfilterenabled' => ($texexample !== $formattedtext),
            ];
        }

        if (isset($enabledplugins['h5p']) && has_capability('tiny/h5p:addembed', $context)) {
            $config['plugins']['h5p'] = [
                'canembed' => has_capability('tiny/h5p:addembed', $context),
                'canupload' => has_capability('moodle/h5p:deploy', $context),
            ];
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
                'canacesspremium' => has_capability('tiny/premium:accesspremium', $context),
            ];
        }

        if (isset($enabledplugins['recordrtc'])) {
            $config['plugins']['recordrtc'] = [
                'canrecordaudio' => has_capability('tiny/recordrtc:recordaudio', $context),
                'canrecordvideo' => has_capability('tiny/recordrtc:recordvideo', $context),
                'canrecordscreen' => has_capability('tiny/recordrtc:recordscreen', $context),
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
            'plugins' => new external_single_structure([
                'accessibilitychecker' => new external_single_structure([], '', VALUE_OPTIONAL),
                'equation' => new external_single_structure([
                    'texfilterenabled' => new external_value(PARAM_BOOL, 'TeX filter is enabled'),
                ], '', VALUE_OPTIONAL),
                'h5p' => new external_single_structure([
                    'canembed' => new external_value(PARAM_BOOL, 'User can add embedded H5P'),
                    'canupload' => new external_value(PARAM_BOOL, 'User can upload new H5P content'),
                ], '', VALUE_OPTIONAL),
                'html' => new external_single_structure([], '', VALUE_OPTIONAL),
                'link' => new external_single_structure([], '', VALUE_OPTIONAL),
                'media' => new external_single_structure([], '', VALUE_OPTIONAL),
                'noautolink' => new external_single_structure([], '', VALUE_OPTIONAL),
                'premium' => new external_single_structure([
                    'canacesspremium' => new external_value(PARAM_BOOL, 'User can access TinyMCE Premium features'),
                ], '', VALUE_OPTIONAL),
                'recordrtc' => new external_single_structure([
                    'canrecordaudio' => new external_value(PARAM_BOOL, 'User can record audio in the text editor'),
                    'canrecordvideo' => new external_value(PARAM_BOOL, 'User can record video in the text editor'),
                    'canrecordscreen' => new external_value(PARAM_BOOL, 'User can record screen in the text editor'),
                ], '', VALUE_OPTIONAL),
            ], 'Configuration of enabled plugins for the context'),
            'warnings' => new external_warnings(),
        ]);
    }
}
