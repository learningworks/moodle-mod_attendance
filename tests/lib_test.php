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
 * Unit tests for mod/attendance/lib.php.
 *
 * @package    mod_attendance
 * @category   phpunit
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/attendance/lib.php');
require_once($CFG->dirroot . '/mod/attendance/locallib.php');
require_once($CFG->dirroot . '/mod/attendance/classes/calendar_helpers.php');
require_once($CFG->dirroot . '/mod/attendance/tests/generator/lib.php');

use \core_calendar\local\api as calendar_local_api;
use \core_calendar\local\event\container as calendar_event_container;

class mod_attendance_lib_testcase extends advanced_testcase {

    /**
     *  @var mod_attendance_generator $plugingenerator handle to plugin generator.
     */
    protected $plugingenerator;

    public function setUp() {
        /** @var mod_attendance_generator $plugingenerator */
        $this->plugingenerator = self::getDataGenerator()->get_plugin_generator('mod_attendance');
        $this->resetAfterTest();
        self::setAdminUser();
    }

    public function test_attendance_core_calendar_provide_event_action() {
        $course = static::getDataGenerator()->create_course();
        $student = static::getDataGenerator()->create_and_enrol($course, 'student');
        $module = static::getDataGenerator()->create_module('attendance', ['course' => $course->id]);
        unset($module->cmid); // The attendance_structure class will throw exeception on not being a class property.
        $cm = get_coursemodule_from_instance('attendance', $module->id);
        $group = static::getDataGenerator()->create_group(['courseid' => $course->id]);
        static::getDataGenerator()->create_group_member(['groupid' => $group->id, 'userid' => $student->id]);
        $attendance = new mod_attendance_structure($module, $cm, $course);
        $sessiondate = strtotime("+1 day");
        $sessiondata = [
            'sessiontype' => mod_attendance_structure::SESSION_GROUP,
            'groups' => [
                $group->id
            ],
            'sessiondate' => $sessiondate,
            'sessionenddate' => $sessiondate,
            'coursestartdate' => $course->startdate,
            'sestime' => [
                'starthour' => 0,
                'startminute' => 13,
                'endhour' => 15,
                'endminute' => 0
            ],
            'statusset' => 0,
            'sdescription' => [
                'text' => 'Something about nothing',
                'format' => FORMAT_HTML,
                'itemid' => 0
            ],
            'calendarevent' => 1,
            'absenteereport' => 1,
            'automark' => 0,
            'subnet' => '',
            'usedefaultsubnet' => 1,
            'preventsharedip' => 0,
            'preventsharediptime' => 0
        ];
        $sessions = attendance_construct_sessions_data_for_add((object) $sessiondata, $attendance);
        $attendance->add_sessions($sessions);
        $session = reset($sessions);
        $event = calendar_event::load($session->caleventid);
        // Create an action factory.
        $factory = new \core_calendar\action_factory();
        $actionevent = mod_attendance_core_calendar_provide_event_action($event, $factory, $student->id);

        static::assertInstanceOf('\core_calendar\local\event\value_objects\action', $actionevent);
        static::assertInstanceOf('moodle_url', $actionevent->get_url());
        static::assertEquals(1, $actionevent->get_item_count());
        static::assertContains('mod/attendance/view.php', $actionevent->get_url()->out_as_local_url(false));
    }

    public function test_attendance_core_calendar_provide_event_action_hidden() {
        $course = static::getDataGenerator()->create_course();
        $student = static::getDataGenerator()->create_and_enrol($course, 'student');
        $module = static::getDataGenerator()->create_module('attendance', ['course' => $course->id]);
        unset($module->cmid); // The attendance_structure class will throw exeception on not being a class property.
        $cm = get_coursemodule_from_instance('attendance', $module->id);
        set_coursemodule_visible($cm->id, false); // Hide.
        $group = static::getDataGenerator()->create_group(['courseid' => $course->id]);
        static::getDataGenerator()->create_group_member(['groupid' => $group->id, 'userid' => $student->id]);
        $attendance = new mod_attendance_structure($module, $cm, $course);
        $sessiondate = strtotime("+1 day");
        $sessiondata = [
            'sessiontype' => mod_attendance_structure::SESSION_GROUP,
            'groups' => [
                $group->id
            ],
            'sessiondate' => $sessiondate,
            'sessionenddate' => $sessiondate,
            'coursestartdate' => $course->startdate,
            'sestime' => [
                'starthour' => 0,
                'startminute' => 13,
                'endhour' => 15,
                'endminute' => 0
            ],
            'statusset' => 0,
            'sdescription' => [
                'text' => 'Something about nothing',
                'format' => FORMAT_HTML,
                'itemid' => 0
            ],
            'calendarevent' => 1,
            'absenteereport' => 1,
            'automark' => 0,
            'subnet' => '',
            'usedefaultsubnet' => 1,
            'preventsharedip' => 0,
            'preventsharediptime' => 0
        ];
        $sessions = attendance_construct_sessions_data_for_add((object) $sessiondata, $attendance);
        $attendance->add_sessions($sessions);
        $session = reset($sessions);
        $event = calendar_event::load($session->caleventid);
        // Create an action factory.
        $factory = new \core_calendar\action_factory();
        $actionevent = mod_attendance_core_calendar_provide_event_action($event, $factory, $student->id);
        static::assertNull($actionevent);
    }
}
