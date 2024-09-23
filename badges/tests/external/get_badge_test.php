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

namespace core_badges\external;

use externallib_advanced_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->libdir . '/badgeslib.php');

/**
 * Tests for external function get_badge.
 *
 * @package    core_badges
 * @category   external
 *
 * @copyright  2024 Daniel Ureña <durenadev@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 4.5
 * @coversDefaultClass \core_badges\external\get_badge
 */
final class get_badge_test extends externallib_advanced_testcase {
    /**
     * Prepare the test.
     *
     * @return array
     */
    private function prepare_test_data(): array {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        set_config('enablebadges', 1);

        $now = time();

        // Mock up a site badge.
        $sitebadge = new \stdClass();
        $sitebadge->id = null;
        $sitebadge->name = "Test badge";
        $sitebadge->description  = "Testing badges site";
        $sitebadge->issuername   = 'Issuer name';
        $sitebadge->issuerurl    = 'https://example.com/issuer';
        $sitebadge->timecreated  = $now;
        $sitebadge->timemodified = $now;
        $sitebadge->usercreated  = 2;
        $sitebadge->usermodified = 2;
        $sitebadge->expiredate    = null;
        $sitebadge->expireperiod  = null;
        $sitebadge->type = BADGE_TYPE_SITE;
        $sitebadge->courseid = null;
        $sitebadge->messagesubject = "Test message subject for badge";
        $sitebadge->message = "Test message body for badge";
        $sitebadge->attachment = 1;
        $sitebadge->notification = 0;
        $sitebadge->status = BADGE_STATUS_ACTIVE;
        $sitebadge->version = '1';
        $sitebadge->language = 'en';
        $sitebadge->imageauthorname = 'Image author';
        $sitebadge->imageauthoremail = 'imageauthor@example.com';
        $sitebadge->imageauthorurl = 'http://image-author-url.domain.co.nz';
        $sitebadge->imagecaption = 'Caption';

        $sitebadge->id = $DB->insert_record('badge', $sitebadge, true);

        $badgeinstance = new \badge($sitebadge->id);

        $alignment = new \stdClass();
        $alignment->badgeid = $sitebadge->id;
        $alignment->targetname = 'Alignment 1';
        $alignment->targeturl = 'http://a1-target-url.domain.co.nz';
        $alignment->targetdescription = 'A1 target description';
        $alignment->targetframework = 'A1 framework';
        $alignment->targetcode = 'A1 code';
        $alignment->id = $badgeinstance->save_alignment($alignment);
        $sitebadge->alignment[] = [
            'id' => $alignment->id,
            'badgeid' => $alignment->badgeid,
            'targetName' => $alignment->targetname,
            'targetUrl' => $alignment->targeturl,
            'targetDescription' => $alignment->targetdescription,
            'targetFramework' => $alignment->targetframework,
            'targetCode' => $alignment->targetcode,
        ];

        $alignment = new \stdClass();
        $alignment->badgeid = $sitebadge->id;
        $alignment->targetname = 'Alignment 2';
        $alignment->targeturl = 'http://a2-target-url.domain.co.nz';
        $alignment->targetdescription = 'A2 target description';
        $alignment->targetframework = 'A2 framework';
        $alignment->targetcode = 'A2 code';
        $alignment->id = $badgeinstance->save_alignment($alignment);
        $sitebadge->alignment[] = [
            'id' => $alignment->id,
            'badgeid' => $alignment->badgeid,
            'targetName' => $alignment->targetname,
            'targetUrl' => $alignment->targeturl,
            'targetDescription' => $alignment->targetdescription,
            'targetFramework' => $alignment->targetframework,
            'targetCode' => $alignment->targetcode,
        ];

        $context = \context_system::instance();
        $sitebadge->badgeurl = new \moodle_url('/badges/badgeclass.php', ['id' => $sitebadge->id]);
        $sitebadge->imageurl = \moodle_url::make_webservice_pluginfile_url(
            $context->id,
            'badges',
            'badgeimage',
            $sitebadge->id,
            '/',
            'f3'
        )->out(false);
        $sitebadge->status = BADGE_STATUS_ACTIVE_LOCKED;

        // Mock up a course badge.
        $course = $this->getDataGenerator()->create_course();
        $coursebadge = new \stdClass();
        $coursebadge->id = null;
        $coursebadge->name = "Test badge";
        $coursebadge->description  = "Testing badges course";
        $coursebadge->issuername   = 'Issuer name';
        $coursebadge->issuerurl    = 'https://example.com/issuer';
        $coursebadge->timecreated  = $now;
        $coursebadge->timemodified = $now;
        $coursebadge->usercreated  = 2;
        $coursebadge->usermodified = 2;
        $coursebadge->expiredate    = null;
        $coursebadge->expireperiod  = null;
        $coursebadge->type = BADGE_TYPE_COURSE;
        $coursebadge->courseid = $course->id;
        $coursebadge->messagesubject = "Test message subject for badge";
        $coursebadge->message = "Test message body for badge";
        $coursebadge->attachment = 1;
        $coursebadge->notification = 0;
        $coursebadge->status = BADGE_STATUS_ACTIVE;
        $coursebadge->version = '1';
        $coursebadge->language = 'en';
        $coursebadge->imageauthorname = 'Image author';
        $coursebadge->imageauthoremail = 'imageauthor@example.com';
        $coursebadge->imageauthorurl = 'http://image-author-url.domain.co.nz';
        $coursebadge->imagecaption = 'Caption';

        $coursebadge->id = $DB->insert_record('badge', $coursebadge, true);

        $context = \context_course::instance($course->id);
        $coursebadge->badgeurl = new \moodle_url('/badges/badgeclass.php', ['id' => $coursebadge->id]);
        $coursebadge->imageurl = \moodle_url::make_webservice_pluginfile_url(
            $context->id,
            'badges',
            'badgeimage',
            $coursebadge->id,
            '/',
            'f3'
        )->out(false);
        $coursebadge->status = BADGE_STATUS_ACTIVE_LOCKED;
        $coursebadge->coursefullname = \core_external\util::format_string($course->fullname, $context);

        return ['sitebadge' => (array) $sitebadge, 'coursebadge' => (array) $coursebadge];
    }

    /**
     * Asserts that a badge returned by the external function matches the given data.
     *
     * @param array $expected Expected badge data.
     * @param array $actual Actual badge data returned by the external function.
     * @param bool $canconfiguredetails True if user has capability "moodle/badges:configuredetails".
     * @throws \PHPUnit\Framework\ExpectationFailedException
     */
    private function assert_badgeclass(array $expected, array $actual, bool $canconfiguredetails): void {
        $this->assertEquals('BadgeClass', $actual['type']);
        $this->assertEquals($expected['badgeurl'], $actual['id']);
        $this->assertEquals($expected['issuername'], $actual['issuer']);
        $this->assertEquals($expected['name'], $actual['name']);
        $this->assertEquals($expected['imageurl'], $actual['image']);
        $this->assertEquals($expected['description'], $actual['description']);
        $this->assertEquals($expected['issuerurl'], $actual['hostedUrl']);
        $this->assertEquals($expected['coursefullname'] ?? null, $actual['coursefullname'] ?? null);

        if ($canconfiguredetails) {
            $this->assertEquals($expected['courseid'] ?? null, $actual['courseid'] ?? null);
        } else {
            $this->assertArrayNotHasKey('courseid', $actual);
        }

        $alignments = $expected['alignment'] ?? null;
        if ($alignments && !$canconfiguredetails) {
            foreach ($alignments as $index => $alignment) {
                $alignments[$index] = [
                    'id' => $alignment['id'],
                    'badgeid' => $alignment['badgeid'],
                    'targetName' => $alignment['targetName'],
                    'targetUrl' => $alignment['targetUrl'],
                ];
            }
        }
        $this->assertEquals($alignments, $actual['alignment'] ?? null);
    }

    /**
     * Test get badge by id without enablebadges active in moodle.
     * @covers ::execute
     */
    public function test_get_badge_without_enablebadges(): void {
        $data = $this->prepare_test_data();
        // Badges are not enabled on this site.
        set_config('enablebadges', 0);

        $this->expectException(\moodle_exception::class);
        $this->expectExceptionMessage('Badges are not enabled on this site.');
        get_badge::execute($data['sitebadge']['id']);
    }

    /**
     * Test get badge by id.
     * @covers ::execute
     */
    public function test_get_badge(): void {
        $data = $this->prepare_test_data();

        // Test with an existing site badge.
        $result = get_badge::execute($data['sitebadge']['id']);
        $result = \core_external\external_api::clean_returnvalue(get_badge::execute_returns(), $result);
        $this->assert_badgeclass($data['sitebadge'], $result['badge'], true);
        $this->assertEmpty($result['warnings']);

        // Test with an existing course badge.
        $result = get_badge::execute($data['coursebadge']['id']);
        $result = \core_external\external_api::clean_returnvalue(get_badge::execute_returns(), $result);
        $this->assert_badgeclass($data['coursebadge'], $result['badge'], true);
        $this->assertEmpty($result['warnings']);
    }

    /**
     * Test get badge by id with an unprivileged user.
     * @covers ::execute
     */
    public function test_get_badge_with_unprivileged_user(): void {
        $data = $this->prepare_test_data();
        $this->setUser($this->getDataGenerator()->create_user());

        // Site badge.
        $result = get_badge::execute($data['sitebadge']['id']);
        $result = \core_external\external_api::clean_returnvalue(get_badge::execute_returns(), $result);
        $this->assert_badgeclass($data['sitebadge'], $result['badge'], false);
        $this->assertEmpty($result['warnings']);

        // Course badge.
        $result = get_badge::execute($data['coursebadge']['id']);
        $result = \core_external\external_api::clean_returnvalue(get_badge::execute_returns(), $result);
        $this->assert_badgeclass($data['coursebadge'], $result['badge'], false);
        $this->assertEmpty($result['warnings']);
    }

    /**
     * Test get badge by id with an invalid badge id.
     * @covers ::execute
     */
    public function test_get_badge_with_invalid_badge_id(): void {
        $data = $this->prepare_test_data();

        $this->expectException(\moodle_exception::class);
        get_badge::execute(123);
    }
}
