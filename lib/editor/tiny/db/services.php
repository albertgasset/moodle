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

$functions = [
    'editor_tiny_get_global_configuration' => [
        'classname' => 'editor_tiny\external\get_global_configuration',
        'description' => 'Returns the global TinyMCE configuration.',
        'type' => 'read',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
    'editor_tiny_get_context_configuration' => [
        'classname' => 'editor_tiny\external\get_context_configuration',
        'description' => 'Returns the TinyMCE configuration for a context.',
        'type' => 'read',
        'services' => [MOODLE_OFFICIAL_MOBILE_SERVICE],
    ],
];
