<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\EmployeeProfile;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use App\Models\Location;
use App\Models\NewsFeed;
use App\Models\Schedule;
use App\Models\Shift;
use App\Models\Task;
use App\Models\TaskAssignment;
use App\Models\Timesheet;
use App\Models\TimesheetEntry;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Company ─────────────────────────────────────────────────────
        $company = Company::create([
            'name'     => 'ShiftFlow Demo Co.',
            'slug'     => 'shiftflow-demo',
            'timezone' => 'Asia/Jakarta',
            'address'  => 'Jl. Sudirman No. 1, Jakarta 10220',
            'phone'    => '+62 21 5555 0100',
            'email'    => 'hello@shiftflow.test',
        ]);

        // ── Locations ────────────────────────────────────────────────────
        $hq = Location::create([
            'company_id' => $company->id,
            'name'       => 'Head Office – Jakarta',
            'address'    => 'Jl. Sudirman No. 1, Jakarta',
            'timezone'   => 'Asia/Jakarta',
        ]);

        $branch = Location::create([
            'company_id' => $company->id,
            'name'       => 'Branch – Bandung',
            'address'    => 'Jl. Asia Afrika No. 56, Bandung',
            'timezone'   => 'Asia/Jakarta',
        ]);

        // ── Departments ──────────────────────────────────────────────────
        $deptOps    = Department::create(['location_id' => $hq->id,     'name' => 'Operations',     'description' => 'Day-to-day operations']);
        $deptFront  = Department::create(['location_id' => $hq->id,     'name' => 'Front of House', 'description' => 'Customer-facing staff']);
        $deptKitchen= Department::create(['location_id' => $hq->id,     'name' => 'Kitchen',        'description' => 'Kitchen & prep staff']);
        $deptMgmt   = Department::create(['location_id' => $hq->id,     'name' => 'Management',     'description' => 'Managers & supervisors']);
        $deptBranch = Department::create(['location_id' => $branch->id, 'name' => 'Branch Ops',     'description' => 'Branch operations']);

        // ── Admin / Manager ──────────────────────────────────────────────
        $admin = User::create([
            'company_id' => $company->id,
            'name'       => 'Admin User',
            'email'      => 'admin@shiftflow.test',
            'password'   => Hash::make('password'),
            'phone'      => '+62 812 0000 0001',
            'status'     => 'active',
        ]);
        EmployeeProfile::create([
            'user_id'          => $admin->id,
            'employee_code'    => 'EMP-001',
            'job_title'        => 'General Manager',
            'employment_type'  => 'full_time',
            'pay_type'         => 'salary',
            'pay_rate'         => 15000000,
            'hire_date'        => '2022-01-01',
        ]);
        $admin->locations()->attach($hq->id,     ['is_primary' => true]);
        $admin->locations()->attach($branch->id, ['is_primary' => false]);
        $admin->departments()->attach($deptMgmt->id);

        // ── Employees ────────────────────────────────────────────────────
        $employees = [
            ['name' => 'Sari Dewi',     'email' => 'sari@shiftflow.test',    'code' => 'EMP-002', 'title' => 'Shift Supervisor',  'type' => 'full_time',  'pay_type' => 'hourly',  'rate' => 45000, 'dept' => $deptOps,     'loc' => $hq],
            ['name' => 'Budi Santoso',  'email' => 'budi@shiftflow.test',    'code' => 'EMP-003', 'title' => 'Cashier',           'type' => 'full_time',  'pay_type' => 'hourly',  'rate' => 35000, 'dept' => $deptFront,   'loc' => $hq],
            ['name' => 'Rina Hartati',  'email' => 'rina@shiftflow.test',    'code' => 'EMP-004', 'title' => 'Barista',           'type' => 'part_time',  'pay_type' => 'hourly',  'rate' => 32000, 'dept' => $deptFront,   'loc' => $hq],
            ['name' => 'Dani Pratama',  'email' => 'dani@shiftflow.test',    'code' => 'EMP-005', 'title' => 'Cook',              'type' => 'full_time',  'pay_type' => 'hourly',  'rate' => 40000, 'dept' => $deptKitchen, 'loc' => $hq],
            ['name' => 'Mega Lestari',  'email' => 'mega@shiftflow.test',    'code' => 'EMP-006', 'title' => 'Prep Cook',         'type' => 'casual',     'pay_type' => 'hourly',  'rate' => 30000, 'dept' => $deptKitchen, 'loc' => $hq],
            ['name' => 'Hendra Putra',  'email' => 'hendra@shiftflow.test',  'code' => 'EMP-007', 'title' => 'Waiter',            'type' => 'part_time',  'pay_type' => 'hourly',  'rate' => 32000, 'dept' => $deptFront,   'loc' => $hq],
            ['name' => 'Fitri Ayu',     'email' => 'fitri@shiftflow.test',   'code' => 'EMP-008', 'title' => 'Waitress',          'type' => 'full_time',  'pay_type' => 'hourly',  'rate' => 33000, 'dept' => $deptFront,   'loc' => $hq],
            ['name' => 'Agus Widodo',   'email' => 'agus@shiftflow.test',    'code' => 'EMP-009', 'title' => 'Branch Supervisor', 'type' => 'full_time',  'pay_type' => 'salary',  'rate' => 7000000, 'dept' => $deptBranch, 'loc' => $branch],
            ['name' => 'Nanda Sari',    'email' => 'nanda@shiftflow.test',   'code' => 'EMP-010', 'title' => 'Branch Cashier',    'type' => 'full_time',  'pay_type' => 'hourly',  'rate' => 35000, 'dept' => $deptBranch, 'loc' => $branch],
            ['name' => 'Rizky Fauzan',  'email' => 'rizky@shiftflow.test',   'code' => 'EMP-011', 'title' => 'Branch Barista',    'type' => 'part_time',  'pay_type' => 'hourly',  'rate' => 30000, 'dept' => $deptBranch, 'loc' => $branch],
        ];

        $userModels = [];
        foreach ($employees as $i => $emp) {
            $user = User::create([
                'company_id' => $company->id,
                'name'       => $emp['name'],
                'email'      => $emp['email'],
                'password'   => Hash::make('password'),
                'phone'      => '+62 812 ' . str_pad($i + 2, 4, '0', STR_PAD_LEFT) . ' 0000',
                'status'     => 'active',
            ]);
            EmployeeProfile::create([
                'user_id'         => $user->id,
                'employee_code'   => $emp['code'],
                'job_title'       => $emp['title'],
                'employment_type' => $emp['type'],
                'pay_type'        => $emp['pay_type'],
                'pay_rate'        => $emp['rate'],
                'hire_date'       => Carbon::now()->subMonths(rand(3, 24))->format('Y-m-d'),
            ]);
            $user->locations()->attach($emp['loc']->id, ['is_primary' => true]);
            $user->departments()->attach($emp['dept']->id);
            $userModels[] = $user;
        }

        $hqEmployees     = array_filter($userModels, fn($u) => $u->locations->first()->id === $hq->id);
        $branchEmployees = array_filter($userModels, fn($u) => $u->locations->first()->id === $branch->id);

        // ── Leave Types ──────────────────────────────────────────────────
        $annualLeave = LeaveType::create(['company_id' => $company->id, 'name' => 'Annual Leave',  'color' => '#6366f1', 'is_paid' => true,  'max_days_per_year' => 12, 'carry_over' => true,  'requires_approval' => true]);
        $sickLeave   = LeaveType::create(['company_id' => $company->id, 'name' => 'Sick Leave',    'color' => '#f59e0b', 'is_paid' => true,  'max_days_per_year' => 10, 'carry_over' => false, 'requires_approval' => false]);
        $unpaidLeave = LeaveType::create(['company_id' => $company->id, 'name' => 'Unpaid Leave',  'color' => '#6b7280', 'is_paid' => false, 'max_days_per_year' => 30, 'carry_over' => false, 'requires_approval' => true]);
        $maternityLeave = LeaveType::create(['company_id' => $company->id, 'name' => 'Maternity Leave', 'color' => '#ec4899', 'is_paid' => true, 'max_days_per_year' => 90, 'carry_over' => false, 'requires_approval' => true]);

        // ── Schedules ────────────────────────────────────────────────────
        $thisMonday  = Carbon::now()->startOfWeek();
        $lastMonday  = $thisMonday->copy()->subWeek();
        $nextMonday  = $thisMonday->copy()->addWeek();

        $scheduleNow  = Schedule::create(['location_id' => $hq->id, 'week_start_date' => $thisMonday, 'week_end_date' => $thisMonday->copy()->endOfWeek(), 'status' => 'published', 'published_at' => Carbon::now()->subDays(2), 'published_by' => $admin->id]);
        $scheduleLast = Schedule::create(['location_id' => $hq->id, 'week_start_date' => $lastMonday, 'week_end_date' => $lastMonday->copy()->endOfWeek(), 'status' => 'published', 'published_at' => Carbon::now()->subDays(9), 'published_by' => $admin->id]);
        $scheduleNext = Schedule::create(['location_id' => $hq->id, 'week_start_date' => $nextMonday, 'week_end_date' => $nextMonday->copy()->endOfWeek(), 'status' => 'draft', 'published_by' => $admin->id]);
        $scheduleBranch = Schedule::create(['location_id' => $branch->id, 'week_start_date' => $thisMonday, 'week_end_date' => $thisMonday->copy()->endOfWeek(), 'status' => 'published', 'published_at' => Carbon::now()->subDays(2), 'published_by' => $admin->id]);

        // ── Shifts (current week – HQ) ───────────────────────────────────
        $hqStaff = array_values($hqEmployees);
        $shiftSlots = [
            ['start' => '07:00', 'end' => '15:00', 'break' => 30, 'title' => 'Morning Shift'],
            ['start' => '15:00', 'end' => '23:00', 'break' => 30, 'title' => 'Evening Shift'],
            ['start' => '09:00', 'end' => '17:00', 'break' => 60, 'title' => 'Day Shift'],
        ];

        for ($day = 0; $day < 7; $day++) {
            $date = $thisMonday->copy()->addDays($day);
            foreach ($hqStaff as $idx => $employee) {
                $slot   = $shiftSlots[$idx % count($shiftSlots)];
                $start  = Carbon::parse($date->format('Y-m-d') . ' ' . $slot['start']);
                $end    = Carbon::parse($date->format('Y-m-d') . ' ' . $slot['end']);
                $status = $date->isPast() ? 'confirmed' : 'draft';
                Shift::create([
                    'schedule_id'           => $scheduleNow->id,
                    'location_id'           => $hq->id,
                    'department_id'         => $employee->departments->first()->id,
                    'user_id'               => $employee->id,
                    'title'                 => $slot['title'],
                    'start_datetime'        => $start,
                    'end_datetime'          => $end,
                    'break_duration_minutes'=> $slot['break'],
                    'status'                => $status,
                ]);
            }
        }

        // ── Shifts (last week – HQ) ──────────────────────────────────────
        for ($day = 0; $day < 7; $day++) {
            $date = $lastMonday->copy()->addDays($day);
            foreach ($hqStaff as $idx => $employee) {
                $slot  = $shiftSlots[$idx % count($shiftSlots)];
                $start = Carbon::parse($date->format('Y-m-d') . ' ' . $slot['start']);
                $end   = Carbon::parse($date->format('Y-m-d') . ' ' . $slot['end']);
                Shift::create([
                    'schedule_id'           => $scheduleLast->id,
                    'location_id'           => $hq->id,
                    'department_id'         => $employee->departments->first()->id,
                    'user_id'               => $employee->id,
                    'title'                 => $slot['title'],
                    'start_datetime'        => $start,
                    'end_datetime'          => $end,
                    'break_duration_minutes'=> $slot['break'],
                    'status'                => 'confirmed',
                ]);
            }
        }

        // ── Shifts (branch – current week) ───────────────────────────────
        $branchStaff = array_values($branchEmployees);
        for ($day = 0; $day < 5; $day++) {
            $date = $thisMonday->copy()->addDays($day);
            foreach ($branchStaff as $idx => $employee) {
                $slot  = $shiftSlots[$idx % count($shiftSlots)];
                $start = Carbon::parse($date->format('Y-m-d') . ' ' . $slot['start']);
                $end   = Carbon::parse($date->format('Y-m-d') . ' ' . $slot['end']);
                Shift::create([
                    'schedule_id'           => $scheduleBranch->id,
                    'location_id'           => $branch->id,
                    'department_id'         => $employee->departments->first()->id,
                    'user_id'               => $employee->id,
                    'title'                 => $slot['title'],
                    'start_datetime'        => $start,
                    'end_datetime'          => $end,
                    'break_duration_minutes'=> $slot['break'],
                    'status'                => 'confirmed',
                ]);
            }
        }

        // ── Timesheets (last week – all HQ staff) ───────────────────────
        foreach ($hqStaff as $employee) {
            $timesheet = Timesheet::create([
                'user_id'      => $employee->id,
                'location_id'  => $hq->id,
                'period_start' => $lastMonday,
                'period_end'   => $lastMonday->copy()->endOfWeek(),
                'status'       => 'approved',
                'submitted_at' => $lastMonday->copy()->endOfWeek()->addHours(9),
                'approved_by'  => $admin->id,
                'approved_at'  => $lastMonday->copy()->endOfWeek()->addDays(1),
            ]);
            for ($day = 0; $day < 5; $day++) {
                $date  = $lastMonday->copy()->addDays($day);
                $hours = round(rand(72, 92) / 10, 1);
                TimesheetEntry::create([
                    'timesheet_id' => $timesheet->id,
                    'date'         => $date,
                    'start_time'   => $date->copy()->setTime(7, rand(0, 10))->format('H:i:s'),
                    'end_time'     => $date->copy()->setTime(15, rand(0, 10))->format('H:i:s'),
                    'break_minutes'=> 30,
                    'total_hours'  => $hours,
                ]);
            }
        }

        // ── Timesheets (current week – submitted/pending) ────────────────
        $submitCutoff = Carbon::now()->subDays(2);
        foreach (array_slice($hqStaff, 0, 4) as $employee) {
            $timesheet = Timesheet::create([
                'user_id'      => $employee->id,
                'location_id'  => $hq->id,
                'period_start' => $thisMonday,
                'period_end'   => $thisMonday->copy()->endOfWeek(),
                'status'       => 'submitted',
                'submitted_at' => $submitCutoff,
            ]);
            $daysWorked = (int) $thisMonday->diffInDays(Carbon::yesterday()) + 1;
            for ($day = 0; $day < min($daysWorked, 5); $day++) {
                $date = $thisMonday->copy()->addDays($day);
                TimesheetEntry::create([
                    'timesheet_id' => $timesheet->id,
                    'date'         => $date,
                    'start_time'   => $date->copy()->setTime(7, rand(0, 10))->format('H:i:s'),
                    'end_time'     => $date->copy()->setTime(15, rand(0, 10))->format('H:i:s'),
                    'break_minutes'=> 30,
                    'total_hours'  => round(rand(70, 90) / 10, 1),
                ]);
            }
        }

        // ── Leave Requests ───────────────────────────────────────────────
        LeaveRequest::create([
            'user_id'       => $hqStaff[0]->id,
            'leave_type_id' => $annualLeave->id,
            'start_date'    => Carbon::now()->addDays(7),
            'end_date'      => Carbon::now()->addDays(11),
            'total_days'    => 5,
            'reason'        => 'Family vacation to Bali',
            'status'        => 'pending',
        ]);
        LeaveRequest::create([
            'user_id'       => $hqStaff[1]->id,
            'leave_type_id' => $sickLeave->id,
            'start_date'    => Carbon::now()->subDays(3),
            'end_date'      => Carbon::now()->subDays(2),
            'total_days'    => 2,
            'reason'        => 'Fever and rest',
            'status'        => 'approved',
            'reviewed_by'   => $admin->id,
        ]);
        LeaveRequest::create([
            'user_id'       => $hqStaff[2]->id,
            'leave_type_id' => $annualLeave->id,
            'start_date'    => Carbon::now()->addDays(14),
            'end_date'      => Carbon::now()->addDays(16),
            'total_days'    => 3,
            'reason'        => 'Personal matters',
            'status'        => 'pending',
        ]);
        LeaveRequest::create([
            'user_id'       => $hqStaff[3]->id,
            'leave_type_id' => $unpaidLeave->id,
            'start_date'    => Carbon::now()->subDays(10),
            'end_date'      => Carbon::now()->subDays(8),
            'total_days'    => 3,
            'reason'        => 'Personal emergency',
            'status'        => 'approved',
            'reviewed_by'   => $admin->id,
        ]);
        LeaveRequest::create([
            'user_id'       => $hqStaff[4]->id,
            'leave_type_id' => $annualLeave->id,
            'start_date'    => Carbon::now()->addDays(21),
            'end_date'      => Carbon::now()->addDays(25),
            'total_days'    => 5,
            'reason'        => 'Annual holiday',
            'status'        => 'rejected',
            'reviewed_by'   => $admin->id,
        ]);
        LeaveRequest::create([
            'user_id'       => $branchStaff[0]->id,
            'leave_type_id' => $sickLeave->id,
            'start_date'    => Carbon::now()->subDays(1),
            'end_date'      => Carbon::now(),
            'total_days'    => 2,
            'reason'        => 'Headache and cold',
            'status'        => 'pending',
        ]);

        // ── Tasks ────────────────────────────────────────────────────────
        $tasks = [
            ['title' => 'Update staff handbook for Q3', 'priority' => 'high', 'status' => 'in_progress', 'due' => 7, 'assignees' => [$hqStaff[0]->id, $hqStaff[1]->id]],
            ['title' => 'Deep clean kitchen equipment', 'priority' => 'urgent', 'status' => 'pending', 'due' => 2, 'assignees' => [$hqStaff[3]->id, $hqStaff[4]->id]],
            ['title' => 'Monthly inventory count', 'priority' => 'medium', 'status' => 'pending', 'due' => 5, 'assignees' => [$hqStaff[0]->id]],
            ['title' => 'Onboard new part-time staff', 'priority' => 'high', 'status' => 'pending', 'due' => 10, 'assignees' => [$admin->id, $hqStaff[0]->id]],
            ['title' => 'Fix broken coffee machine', 'priority' => 'urgent', 'status' => 'completed', 'due' => -2, 'assignees' => [$hqStaff[2]->id]],
            ['title' => 'Review Q2 sales report', 'priority' => 'medium', 'status' => 'completed', 'due' => -5, 'assignees' => [$admin->id]],
            ['title' => 'Order new uniforms for branch', 'priority' => 'low', 'status' => 'in_progress', 'due' => 14, 'assignees' => [$branchStaff[0]->id]],
            ['title' => 'Staff training: POS system update', 'priority' => 'high', 'status' => 'pending', 'due' => 3, 'assignees' => [$hqStaff[1]->id, $hqStaff[2]->id, $hqStaff[5]->id]],
        ];

        foreach ($tasks as $t) {
            $task = Task::create([
                'company_id'  => $company->id,
                'created_by'  => $admin->id,
                'title'       => $t['title'],
                'priority'    => $t['priority'],
                'status'      => $t['status'],
                'due_date'    => Carbon::now()->addDays($t['due'])->toDateString(),
            ]);
            foreach ($t['assignees'] as $uid) {
                TaskAssignment::create([
                    'task_id' => $task->id,
                    'user_id' => $uid,
                    'status'  => $t['status'] === 'completed' ? 'completed' : 'pending',
                    'completed_at' => $t['status'] === 'completed' ? Carbon::now()->subDays(1) : null,
                ]);
            }
        }

        // ── News Feed / Announcements ────────────────────────────────────
        NewsFeed::create([
            'company_id'           => $company->id,
            'author_id'            => $admin->id,
            'title'                => '🎉 Welcome to ShiftFlow!',
            'body'                 => '<p>We are excited to launch our new workforce management system. All schedules, timesheets, and leave requests will now be managed here.</p><p>Please log in and update your profile. If you have any questions, contact your manager.</p>',
            'type'                 => 'announcement',
            'pinned'               => true,
            'requires_confirmation' => true,
        ]);
        NewsFeed::create([
            'company_id' => $company->id,
            'author_id'  => $admin->id,
            'title'      => 'Reminder: Submit Timesheets by Sunday',
            'body'       => '<p>Please ensure all timesheets for the current week are submitted <strong>by Sunday at 11:59 PM</strong>. Late submissions will require manager approval.</p>',
            'type'       => 'reminder',
            'pinned'     => false,
            'requires_confirmation' => false,
        ]);
        NewsFeed::create([
            'company_id' => $company->id,
            'author_id'  => $admin->id,
            'title'      => 'Eid Holiday Schedule Update',
            'body'       => '<p>Please note the updated schedule for the upcoming Eid holiday period. The branch will operate on reduced hours from June 28 – July 3.</p><ul><li>Reduced shifts: 09:00 – 17:00</li><li>Skeleton crew only on June 29 & 30</li></ul>',
            'type'       => 'announcement',
            'pinned'     => false,
            'requires_confirmation' => true,
        ]);
        NewsFeed::create([
            'company_id' => $company->id,
            'author_id'  => $hqStaff[0]->id,
            'title'      => 'Kitchen Deep Clean – This Saturday',
            'body'       => '<p>All kitchen staff are required to attend the scheduled deep clean this Saturday. Please arrive 30 minutes early. Cleaning supplies will be provided.</p>',
            'type'       => 'update',
            'pinned'     => false,
            'requires_confirmation' => false,
        ]);
        NewsFeed::create([
            'company_id' => $company->id,
            'author_id'  => $admin->id,
            'title'      => 'New Leave Policy Effective July 1',
            'body'       => '<p>Our updated leave policy is now in effect. Key changes include:</p><ul><li>Annual leave increased from 10 to 12 days</li><li>Sick leave no longer requires a doctor\'s certificate for 1–2 days</li><li>Leave requests must be submitted at least 3 days in advance</li></ul>',
            'type'       => 'policy',
            'pinned'     => true,
            'requires_confirmation' => true,
        ]);
    }
}
