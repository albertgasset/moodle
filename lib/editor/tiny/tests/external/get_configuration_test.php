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

declare(strict_types=1);

namespace editor_tiny\external;

use advanced_testcase;
use core_external\external_api;
use editor_tiny\plugininfo\tiny;

/**
 * Unit tests for the editor_tiny\external get_configuration class.
 *
 * @package     editor_tiny
 * @covers      \editor_tiny\external\get_configuration
 * @covers      \editor_tiny\manager::get_plugin_configuration_for_external
 * @copyright   2025 Moodle Pty Ltd
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class get_configuration_test extends advanced_testcase {

    /**
     * Basic setup for tests.
     */
    public function setUp(): void {
        parent::setUp();
        $this->resetAfterTest(true);

        // Global editor settings.
        set_config('branding', false, 'editor_tiny');
        set_config('extended_valid_elements', 'script[*]', 'editor_tiny');

        // Disable a plugin.
        tiny::enable_plugin('autosave', 0);

        // AI placement plugin.
        set_config('enabled', 1, 'aiplacement_editor');
        $aimanager = \core\di::get(\core_ai\manager::class);
        $aiprovider = $aimanager->create_provider_instance(
            classname: '\aiprovider_openai\provider',
            name: 'test_provider',
            enabled: true,
            config: ['apikey' => 'test_api_key'],
        );
        $aimanager->set_action_state(
            plugin: $aiprovider->provider,
            actionbasename: \core_ai\aiactions\generate_text::class::get_basename(),
            enabled: 1,
            instanceid: $aiprovider->id
        );
        $aimanager->set_action_state(
            plugin: $aiprovider->provider,
            actionbasename: \core_ai\aiactions\generate_image::class::get_basename(),
            enabled: 1,
            instanceid: $aiprovider->id
        );

        // Premium plugins.
        set_config('apikey', 'test_api_key', 'tiny_premium');
        foreach (\tiny_premium\manager::get_plugins() as $plugin) {
            \tiny_premium\manager::set_plugin_config(['enabled' => 1], $plugin);
        }
    }

    /**
     * Test the external function with an editing teacher.
     *
     * @return void
     */
    public function test_execute_with_teacher(): void {
        global $CFG;

        // Setup user.
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();
        $context = \core\context\course::instance($course->id);
        $generator->enrol_user($user->id, $course->id, 'editingteacher');
        $this->setUser($user);

        // Execute function.
        $result = get_configuration::execute('course', (int) $course->id);
        $result = external_api::clean_returnvalue(get_configuration::execute_returns(), $result);

        // Check context id.
        self::assertEquals($context->id, $result['contextid']);

        // Check global settings.
        self::assertEquals(get_config('editor_tiny', 'branding'), $result['branding']);
        self::assertEquals(get_config('editor_tiny', 'extended_valid_elements'), $result['extendedvalidelements']);
        self::assertEquals(self::get_installed_languages(), $result['installedlanguages']);

        // Check plugin settings.
        $plugins = [
            ['name' => 'accessibilitychecker', 'settings' => []],
            ['name' => 'aiplacement', 'settings' => self::get_aiplacement_settings()],
            ['name' => 'equation', 'settings' => self::get_equation_settings()],
            ['name' => 'h5p', 'settings' => self::get_h5p_settings()],
            ['name' => 'html', 'settings' => []],
            ['name' => 'link', 'settings' => []],
            ['name' => 'media', 'settings' => []],
            ['name' => 'premium', 'settings' => self::get_premium_settings()],
            ['name' => 'recordrtc', 'settings' => self::get_recordrtc_settings()],
        ];
        self::assertEquals($plugins, $result['plugins']);
    }

    /**
     * Test the external function with a guest user.
     *
     * @return void
     */
    public function test_execute_with_guest(): void {
        global $CFG;

        // Setup user.
        $generator = $this->getDataGenerator();
        $user = $generator->create_user();
        $course = $generator->create_course();
        $context = \core\context\course::instance($course->id);
        $generator->enrol_user($user->id, $course->id, 'guest');
        $this->setUser($user);

        // Execute function.
        $result = get_configuration::execute('course', (int) $course->id);
        $result = external_api::clean_returnvalue(get_configuration::execute_returns(), $result);

        // Check context id.
        self::assertEquals($context->id, $result['contextid']);

        // Check global settings.
        self::assertEquals(get_config('editor_tiny', 'branding'), $result['branding']);
        self::assertEquals(get_config('editor_tiny', 'extended_valid_elements'), $result['extendedvalidelements']);
        self::assertEquals(self::get_installed_languages(), $result['installedlanguages']);

        // Check plugin settings.
        $plugins = [
            ['name' => 'accessibilitychecker', 'settings' => []],
            ['name' => 'equation', 'settings' => self::get_equation_settings()],
            ['name' => 'html', 'settings' => []],
            ['name' => 'link', 'settings' => []],
            ['name' => 'media', 'settings' => []],
            ['name' => 'premium', 'settings' => self::get_premium_settings()],
            ['name' => 'recordrtc', 'settings' => self::get_recordrtc_settings()],
        ];
        self::assertEquals($plugins, $result['plugins']);
    }

    /**
     * Returns the expected list of installed languages returned by the external function.
     *
     * @return array
     */
    private static function get_installed_languages(): array {
        $installedlanguages = [];
        foreach (get_string_manager()->get_list_of_translations(true) as $lang => $name) {
            $installedlanguages[] = ['lang' => $lang, 'name' => $name];
        }
        return $installedlanguages;
    }

    /**
     * Returns the expected settings of the AI placement plugin returned by the external function.
     *
     * @return array
     */
    private static function get_aiplacement_settings(): array {
        return [
            [
                'name' => 'policyagreed',
                'value' => '0',
            ],
            [
                'name' => 'generate_text',
                'value' => '1',
            ],
            [
                'name' => 'generate_image',
                'value' => '1',
            ],
        ];
    }

    /**
     * Returns the expected settings of the equation plugin returned by the external function.
     *
     * @return array
     */
    private static function get_equation_settings(): array {
        return [
            [
                'name' => 'texfilter',
                'value' => '1',
            ],
            [
                'name' => 'libraries',
                'value' => json_encode([
                    [
                        'key' => 'group1',
                        'groupname' => get_string('librarygroup1', 'tiny_equation'),
                        'elements' => explode("\n", trim(get_config('tiny_equation', 'librarygroup1'))),
                        'active' => true,
                    ],
                    [
                        'key' => 'group2',
                        'groupname' => get_string('librarygroup2', 'tiny_equation'),
                        'elements' => explode("\n", trim(get_config('tiny_equation', 'librarygroup2'))),
                    ],
                    [
                        'key' => 'group3',
                        'groupname' => get_string('librarygroup3', 'tiny_equation'),
                        'elements' => explode("\n", trim(get_config('tiny_equation', 'librarygroup3'))),
                    ],
                    [
                        'key' => 'group4',
                        'groupname' => get_string('librarygroup4', 'tiny_equation'),
                        'elements' => explode("\n", trim(get_config('tiny_equation', 'librarygroup4'))),
                    ],
                ]),
            ],
            [
                'name' => 'texdocsurl',
                'value' => get_docs_url('Using_TeX_Notation'),
            ],
        ];
    }

    /**
     * Returns the expected settings of the H5P plugin returned by the external function.
     *
     * @return array
     */
    private static function get_h5p_settings(): array {
        return [
            [
                'name' => 'embedallowed',
                'value' => '1',
            ],
            [
                'name' => 'uploadallowed',
                'value' => '1',
            ],
        ];
    }

    /**
     * Returns the expected settings of the premium plugin returned by the external function.
     *
     * @return array
     */
    private static function get_premium_settings(): array {
        return [
            [
                'name' => 'premiumplugins',
                'value' => implode(',', \tiny_premium\manager::get_enabled_plugins()),
            ],
        ];
    }

    /**
     * Returns the expected settings of the Record RTC plugin returned by the external function.
     *
     * @return array
     */
    private static function get_recordrtc_settings(): array {
        return [
            [
                'name' => 'videoallowed',
                'value' => '1',
            ],
            [
                'name' => 'audioallowed',
                'value' => '1',
            ],
            [
                'name' => 'screenallowed',
                'value' => '0',
            ],
            [
                'name' => 'pausingallowed',
                'value' => get_config('tiny_recordrtc', 'allowedpausing'),
            ],
            [
                'name' => 'allowedtypes',
                'value' => get_config('tiny_recordrtc', 'allowedtypes'),
            ],
            [
                'name' => 'audiobitrate',
                'value' => get_config('tiny_recordrtc', 'audiobitrate'),
            ],
            [
                'name' => 'videobitrate',
                'value' => get_config('tiny_recordrtc', 'videobitrate'),
            ],
            [
                'name' => 'screenbitrate',
                'value' => get_config('tiny_recordrtc', 'screenbitrate'),
            ],
            [
                'name' => 'audiotimelimit',
                'value' => get_config('tiny_recordrtc', 'audiotimelimit'),
            ],
            [
                'name' => 'videotimelimit',
                'value' => get_config('tiny_recordrtc', 'videotimelimit'),
            ],
            [
                'name' => 'screentimelimit',
                'value' => get_config('tiny_recordrtc', 'screentimelimit'),
            ],
            [
                'name' => 'maxrecsize',
                'value' => (string) get_max_upload_file_size(),
            ],
            [
                'name' => 'videoscreenwidth',
                'value' => explode(',', get_config('tiny_recordrtc', 'screensize'))[0],
            ],
            [
                'name' => 'videoscreenheight',
                'value' => explode(',', get_config('tiny_recordrtc', 'screensize'))[1],
            ],
        ];
    }

}
