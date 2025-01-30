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
 * Badges external functions tests.
 *
 * @package    core_badges
 * @category   external
 * @copyright  2016 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */

namespace core_badges\external;

use core_badges_external;
use core_external\external_api;
use core_external\external_settings;
use externallib_advanced_testcase;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');
require_once($CFG->libdir . '/badgeslib.php');

/**
 * Badges external functions tests
 *
 * @package    core_badges
 * @category   external
 * @copyright  2016 Juan Leyva <juan@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 3.1
 */
final class external_test extends externallib_advanced_testcase {

    /**
     * Prepare the test.
     *
     * @return array
     */
    private function prepare_test_data(): array {
        global $DB;

        $this->resetAfterTest();
        $this->setAdminUser();

        // Setup test data.
        $course = $this->getDataGenerator()->create_course();

        // Create users and enrolments.
        $student = $this->getDataGenerator()->create_and_enrol($course);
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');

        // Mock up a site badge.
        $now = time();
        $badge = new \stdClass();
        $badge->id = null;
        $badge->name = "Test badge site";
        $badge->description = "Testing badges site";
        $badge->timecreated = $now;
        $badge->timemodified = $now;
        $badge->usercreated = (int) $teacher->id;
        $badge->usermodified = (int) $teacher->id;
        $badge->issuername = 'Issuer name';
        $badge->issuerurl = 'https://example.com/issuer';
        $badge->issuercontact = 'Issuer contact';
        $badge->expiredate = $now + YEARSECS;
        $badge->expireperiod = YEARSECS;
        $badge->type = BADGE_TYPE_SITE;
        $badge->courseid = null;
        $badge->messagesubject = "Test message subject for badge";
        $badge->message = "Test message body for badge";
        $badge->attachment = 1;
        $badge->notification = 0;
        $badge->status = BADGE_STATUS_ACTIVE;
        $badge->version = '1';
        $badge->language = 'en';
        $badge->imageauthorname = 'Image author';
        $badge->imageauthoremail = 'imageauthor@example.com';
        $badge->imageauthorurl = 'http://image-author-url.domain.co.nz';
        $badge->imagecaption = 'Caption';

        $badgeid = $DB->insert_record('badge', $badge, true);
        $badge->id = $badgeid;
        $sitebadge = new \badge($badgeid);
        $sitebadge->issue($student->id, true);
        $siteissuedbadge = $DB->get_record('badge_issued', [ 'badgeid' => $badge->id ]);

        // Hack the database to adjust the time the badge was issued.
        $siteissuedbadge->dateissued = $now - 11;
        $DB->update_record('badge_issued', $siteissuedbadge);

        $badge->nextcron = $sitebadge->nextcron;
        $badge->issuedid = (int) $siteissuedbadge->id;
        $badge->uniquehash = $siteissuedbadge->uniquehash;
        $badge->dateissued = (int) $siteissuedbadge->dateissued;
        $badge->dateexpire = $siteissuedbadge->dateexpire;
        $badge->visible = (int) $siteissuedbadge->visible;
        $context = \context_system::instance();
        $badge->badgeurl = \moodle_url::make_webservice_pluginfile_url($context->id, 'badges', 'badgeimage', $badge->id, '/',
                                                                            'f3')->out(false);
        $badge->status = BADGE_STATUS_ACTIVE_LOCKED;
        $badge->recipientid = $student->id;
        $badge->recipientfullname = fullname($student);
        $badge->email = $student->email;

        // Add an endorsement for the badge.
        $endorsement = new \stdClass();
        $endorsement->badgeid = $badgeid;
        $endorsement->issuername = 'Issuer name';
        $endorsement->issuerurl = 'http://endorsement-issuer-url.domain.co.nz';
        $endorsement->issueremail = 'endorsementissuer@example.com';
        $endorsement->claimid = 'http://claim-url.domain.co.nz';
        $endorsement->claimcomment = 'Claim comment';
        $endorsement->dateissued = $now;
        $endorsement->id = $sitebadge->save_endorsement($endorsement);
        $badge->endorsement = (array) $endorsement;

        // Add 2 alignments.
        $alignment = new \stdClass();
        $alignment->badgeid = $badgeid;
        $alignment->targetname = 'Alignment 1';
        $alignment->targeturl = 'http://a1-target-url.domain.co.nz';
        $alignment->targetdescription = 'A1 target description';
        $alignment->targetframework = 'A1 framework';
        $alignment->targetcode = 'A1 code';
        $alignment->id = $sitebadge->save_alignment($alignment);
        $badge->alignment[] = [
            'id' => $alignment->id,
            'badgeid' => $alignment->badgeid,
            'targetName' => $alignment->targetname,
            'targetUrl' => $alignment->targeturl,
            'targetDescription' => $alignment->targetdescription,
            'targetFramework' => $alignment->targetframework,
            'targetCode' => $alignment->targetcode,
        ];

        $alignment = new \stdClass();
        $alignment->badgeid = $badgeid;
        $alignment->targetname = 'Alignment 2';
        $alignment->targeturl = 'http://a2-target-url.domain.co.nz';
        $alignment->targetdescription = 'A2 target description';
        $alignment->targetframework = 'A2 framework';
        $alignment->targetcode = 'A2 code';
        $alignment->id = $sitebadge->save_alignment($alignment);
        $badge->alignment[] = [
            'id' => $alignment->id,
            'badgeid' => $alignment->badgeid,
            'targetName' => $alignment->targetname,
            'targetUrl' => $alignment->targeturl,
            'targetDescription' => $alignment->targetdescription,
            'targetFramework' => $alignment->targetframework,
            'targetCode' => $alignment->targetcode,
        ];

        $badge->relatedbadges = [];
        $usersitebadge = (array) $badge;

        // Now a course badge.
        $badge = new \stdClass();
        $badge->id = null;
        $badge->name = "Test badge course";
        $badge->description = "Testing badges course";
        $badge->timecreated = $now;
        $badge->timemodified = $now;
        $badge->usercreated = (int) $teacher->id;
        $badge->usermodified = (int) $teacher->id;
        $badge->issuername = 'Issuer name';
        $badge->issuerurl = 'https://example.com/issuer';
        $badge->issuercontact = 'Issuer contact';
        $badge->expiredate = $now + YEARSECS;
        $badge->expireperiod = YEARSECS;
        $badge->type = BADGE_TYPE_COURSE;
        $badge->courseid = (int) $course->id;;
        $badge->messagesubject = "Test message subject for course badge";
        $badge->message = "Test message body for course badge";
        $badge->attachment = 1;
        $badge->notification = 0;
        $badge->status = BADGE_STATUS_ACTIVE;
        $badge->version = '1';
        $badge->language = 'en';
        $badge->imageauthorname = 'Image author';
        $badge->imageauthoremail = 'imageauthor@example.com';
        $badge->imageauthorurl = 'http://image-author-url.domain.co.nz';
        $badge->imagecaption = 'Caption';

        $badge->id = $DB->insert_record('badge', $badge, true);
        $coursebadge = new \badge($badge->id);
        $coursebadge->issue($student->id, true);
        $courseissuedbadge = $DB->get_record('badge_issued', [ 'badgeid' => $badge->id ]);

        $badge->nextcron = $coursebadge->nextcron;
        $badge->issuedid = (int) $courseissuedbadge->id;
        $badge->uniquehash = $courseissuedbadge->uniquehash;
        $badge->dateissued = (int) $courseissuedbadge->dateissued;
        $badge->dateexpire = $courseissuedbadge->dateexpire;
        $badge->visible = (int) $courseissuedbadge->visible;
        $context = \context_course::instance($badge->courseid);
        $badge->badgeurl = \moodle_url::make_webservice_pluginfile_url(
            $context->id, 'badges', 'badgeimage', $badge->id , '/', 'f3')->out(false);
        $badge->status = BADGE_STATUS_ACTIVE_LOCKED;
        $badge->recipientid = $student->id;
        $badge->recipientfullname = fullname($student);
        $badge->email = $student->email;
        $badge->coursefullname = \core_external\util::format_string($course->fullname, $context);

        $badge->alignment = [];
        $usercoursebadge = (array) $badge;
        // Make the site badge a related badge.
        $sitebadge->add_related_badges([$badge->id]);
        $usersitebadge['relatedbadges'][0] = [
            'id' => (int) $coursebadge->id,
            'name' => $coursebadge->name,
            'version' => $coursebadge->version,
            'language' => $coursebadge->language,
            'type' => $coursebadge->type,
        ];
        $usercoursebadge['relatedbadges'][0] = [
            'id' => (int) $sitebadge->id,
            'name' => $sitebadge->name,
            'version' => $sitebadge->version,
            'language' => $sitebadge->language,
            'type' => $sitebadge->type,
        ];
        return [
            'coursebadge' => $usercoursebadge,
            'sitebadge' => $usersitebadge,
            'student' => $student,
            'teacher' => $teacher,
        ];
    }

    /**
     * Asserts that a badge returned by the external function matches the given data.
     *
     * @param array $expected Expected badge data.
     * @param array $actual Actual badge data returned by the external function.
     * @param bool $isrecipient True if user is the badge recipient.
     * @param bool $canconfiguredetails True if user has capability "moodle/badges:configuredetails".
     * @throws \PHPUnit\Framework\ExpectationFailedException
     */
    private function assert_badge(array $expected, array $actual, bool $isrecipient, bool $canconfiguredetails): void {
        $this->assertEquals($expected['id'], $actual['id']);
        $this->assertEquals($expected['name'], $actual['name']);
        $this->assertEquals($expected['type'], $actual['type']);
        $this->assertEquals($expected['description'], $actual['description']);
        $this->assertEquals($expected['issuername'], $actual['issuername']);
        $this->assertEquals($expected['issuerurl'], $actual['issuerurl']);
        $this->assertEquals($expected['issuercontact'], $actual['issuercontact']);
        $this->assertEquals($expected['uniquehash'], $actual['uniquehash']);
        $this->assertEquals($expected['dateissued'], $actual['dateissued']);
        $this->assertEquals($expected['dateexpire'], $actual['dateexpire']);
        $this->assertEquals($expected['version'], $actual['version']);
        $this->assertEquals($expected['language'], $actual['language']);
        $this->assertEquals($expected['imageauthorname'], $actual['imageauthorname']);
        $this->assertEquals($expected['imageauthoremail'], $actual['imageauthoremail']);
        $this->assertEquals($expected['imageauthorurl'], $actual['imageauthorurl']);
        $this->assertEquals($expected['imagecaption'], $actual['imagecaption']);
        $this->assertEquals($expected['badgeurl'], $actual['badgeurl']);
        $this->assertEquals($expected['recipientid'], $actual['recipientid']);
        $this->assertEquals($expected['recipientfullname'], $actual['recipientfullname']);
        $this->assertEquals($expected['endorsement'] ?? null, $actual['endorsement'] ?? null);
        $this->assertEquals($expected['coursefullname'] ?? null, $actual['coursefullname'] ?? null);

        if ($isrecipient || $canconfiguredetails) {
            $this->assertTimeCurrent($expected['timecreated']);
            $this->assertTimeCurrent($expected['timemodified']);
            $this->assertEquals($expected['usercreated'], $actual['usercreated']);
            $this->assertEquals($expected['usermodified'], $actual['usermodified']);
            $this->assertEquals($expected['expiredate'], $actual['expiredate']);
            $this->assertEquals($expected['expireperiod'], $actual['expireperiod']);
            $this->assertEquals($expected['courseid'], $actual['courseid']);
            $this->assertEquals($expected['message'], $actual['message']);
            $this->assertEquals($expected['messagesubject'], $actual['messagesubject']);
            $this->assertEquals($expected['attachment'], $actual['attachment']);
            $this->assertEquals($expected['notification'], $actual['notification']);
            $this->assertEquals($expected['nextcron'], $actual['nextcron']);
            $this->assertEquals($expected['status'], $actual['status']);
            $this->assertEquals($expected['issuedid'], $actual['issuedid']);
            $this->assertEquals($expected['visible'], $actual['visible']);
            $this->assertEquals($expected['email'], $actual['email']);
        } else {
            $this->assertEquals(0, $actual['timecreated']);
            $this->assertEquals(0, $actual['timemodified']);
            $this->assertArrayNotHasKey('usercreated', $actual);
            $this->assertArrayNotHasKey('usermodified', $actual);
            $this->assertArrayNotHasKey('expiredate', $actual);
            $this->assertArrayNotHasKey('expireperiod', $actual);
            $this->assertArrayNotHasKey('courseid', $actual);
            $this->assertArrayNotHasKey('message', $actual);
            $this->assertArrayNotHasKey('messagesubject', $actual);
            $this->assertEquals(1, $actual['attachment']);
            $this->assertEquals(1, $actual['notification']);
            $this->assertArrayNotHasKey('nextcron', $actual);
            $this->assertEquals(0, $actual['status']);
            $this->assertArrayNotHasKey('issuedid', $actual);
            $this->assertEquals(0, $actual['visible']);
            $this->assertArrayNotHasKey('email', $actual);
        }

        $alignments = $expected['alignment'];
        if (!$canconfiguredetails) {
            foreach ($alignments as $index => $alignment) {
                $alignments[$index] = [
                    'id' => $alignment['id'],
                    'badgeid' => $alignment['badgeid'],
                    'targetName' => $alignment['targetName'],
                    'targetUrl' => $alignment['targetUrl'],
                ];
            }
        }
        $this->assertEquals($alignments, $actual['alignment']);

        $relatedbadges = $expected['relatedbadges'];
        if (!$canconfiguredetails) {
            foreach ($relatedbadges as $index => $relatedbadge) {
                $relatedbadges[$index] = [
                    'id' => $relatedbadge['id'],
                    'name' => $relatedbadge['name'],
                ];
            }
        }
        $this->assertEquals($relatedbadges, $actual['relatedbadges']);
    }

    /**
     * Test get user badges.
     * These is a basic test since the badges_get_my_user_badges used by the external function already has unit tests.
     *
     * @covers \core_badges_external::get_user_badges
     */
    public function test_get_my_user_badges(): void {
        $data = $this->prepare_test_data();

        $this->setUser($data['student']);
        $result = core_badges_external::get_user_badges();
        $result = external_api::clean_returnvalue(core_badges_external::get_user_badges_returns(), $result);
        $this->assertCount(2, $result['badges']);
        $this->assert_badge($data['coursebadge'], $result['badges'][0], true, false);
        $this->assert_badge($data['sitebadge'], $result['badges'][1], true, false);

        // Pagination and filtering.
        $result = core_badges_external::get_user_badges(0, 0, 0, 1, '', true);
        $result = external_api::clean_returnvalue(core_badges_external::get_user_badges_returns(), $result);
        $this->assertCount(1, $result['badges']);
        $this->assert_badge($data['coursebadge'], $result['badges'][0], true, false);
    }

    /**
     * Test get user badges.
     *
     * @covers \core_badges_external::get_user_badges
     */
    public function test_get_other_user_badges(): void {
        $data = $this->prepare_test_data();

        // User with "moodle/badges:configuredetails" capability.
        $this->setAdminUser();
        $result = core_badges_external::get_user_badges($data['student']->id);
        $result = external_api::clean_returnvalue(core_badges_external::get_user_badges_returns(), $result);
        $this->assertCount(2, $result['badges']);
        $this->assert_badge($data['coursebadge'], $result['badges'][0], false, true);
        $this->assert_badge($data['sitebadge'], $result['badges'][1], false, true);

        // User without "moodle/badges:configuredetails" capability.
        $this->setUser($this->getDataGenerator()->create_user());
        $result = core_badges_external::get_user_badges($data['student']->id);
        $result = external_api::clean_returnvalue(core_badges_external::get_user_badges_returns(), $result);
        $this->assertCount(2, $result['badges']);
        $this->assert_badge($data['coursebadge'], $result['badges'][0], false, false);
        $this->assert_badge($data['sitebadge'], $result['badges'][1], false, false);
    }

    /**
     * Test get_user_badges where issuername contains text to be filtered
     *
     * @covers \core_badges_external::get_user_badges
     */
    public function test_get_user_badges_filter_issuername(): void {
        global $DB;

        $data = $this->prepare_test_data();

        filter_set_global_state('multilang', TEXTFILTER_ON);
        filter_set_applies_to_strings('multilang', true);

        external_settings::get_instance()->set_filter(true);

        // Update issuer name of test badge.
        $issuername = '<span class="multilang" lang="en">Issuer (en)</span><span class="multilang" lang="es">Issuer (es)</span>';
        $DB->set_field('badge', 'issuername', $issuername, ['name' => 'Test badge site']);

        // Retrieve student badges.
        $result = core_badges_external::get_user_badges($data['student']->id);
        $result = external_api::clean_returnvalue(core_badges_external::get_user_badges_returns(), $result);

        // Site badge will be last, because it has the earlier issued date.
        $badge = end($result['badges']);
        $this->assertEquals('Issuer (en)', $badge['issuername']);
    }
}
