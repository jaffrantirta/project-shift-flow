# ShiftFlow — Complete Workflow Guide

This document explains **what everything is**, **how it connects**, and **step-by-step how to use** the system from start to finish.

---

## The Big Picture

```
ADMIN SIDE                          EMPLOYEE SIDE
──────────────────────────────────────────────────────────────────
1. Set up company (location, dept)
2. Add employees
3. Create shifts & assign employees
                                    4. Employee clocks IN at start of shift
                                    5. Employee clocks OUT at end of shift
6. System records attendance
                                    7. Employee sees timesheet (auto-filled)
                                    8. Employee submits timesheet for approval
9. Admin approves or rejects
──────────────────────────────────────────────────────────────────
```

That's the full loop. Every feature below is part of one of these steps.

---

## Concepts Explained Simply

### 🏢 Location
A physical place where work happens — e.g. "Main Store", "Warehouse", "Branch Downtown".  
Every shift, attendance record, and timesheet is tied to a location.

### 🏗️ Department
A team or area within a location — e.g. "Kitchen", "Floor Staff", "Cashier".  
Departments are optional on shifts but help organise who does what.

### 👷 Employee
A user account that belongs to a company. Has a profile with job title, pay rate, hire date, etc.

---

### 📋 Shift
**What it is:** A scheduled block of work for one employee at a specific time and location.

**Example:** Jane works Friday 08:00–16:00 at Main Store.

**Fields:**
| Field | Meaning |
|-------|---------|
| Employee | Who is scheduled (optional = "open shift") |
| Location | Where they work |
| Start / End | The scheduled work hours |
| Break | Planned break minutes (e.g. 30 min lunch) |
| Status | `scheduled` → `confirmed` → `completed` / `cancelled` |

**Who creates it:** Admin, in the admin panel under **Scheduling → Shifts**.

---

### 🕐 Clock In / Out (Time Clock)
**What it is:** The actual record of when an employee physically started and stopped working.

**Example:** Jane was *scheduled* 08:00–16:00 but she *clocked in* at 08:03 and *clocked out* at 16:12.

**Event types:**
| Type | Meaning |
|------|---------|
| `clock_in` | Employee arrives and starts working |
| `break_start` | Employee starts their break |
| `break_end` | Employee returns from break |
| `clock_out` | Employee finishes work for the day |

**Who does it:** Employee, via the **Employee Portal app** (mobile/web).  
The app sends GPS coordinates so the admin can verify the employee was at the right place.

---

### ✅ Attendance
**What it is:** A daily summary automatically created from clock in/out events.

**Example:**
- Jane clocked in at 08:03, took 30 min break, clocked out at 16:12
- Attendance record: `total_minutes = 459`, `overtime_minutes = 9`, `status = present`

**Attendance statuses:**
| Status | Meaning |
|--------|---------|
| `present` | Employee worked the shift |
| `late` | Clocked in significantly after shift start |
| `early_out` | Clocked out significantly before shift end |
| `absent` | No clock-in recorded for that shift |
| `no_show` | Shift was assigned but employee never appeared |

**Who creates it:** Automatically by the system when clock events are processed.  
Admin can view attendance in the admin panel.

---

### 📄 Timesheet
**What it is:** A summary of all hours worked by one employee over a **pay period** (usually one week or two weeks).

**Example:**  
Jane's timesheet for May 19–25:
```
Mon May 19  08:03–16:12  break 30min  → 7.65 hrs
Tue May 20  08:00–16:30  break 30min  → 8.0 hrs
Wed May 21  OFF
Thu May 22  09:00–17:00  break 30min  → 7.5 hrs
Fri May 23  08:00–12:00  break 0min   → 4.0 hrs
─────────────────────────────────────────────────
TOTAL: 27.15 hrs  |  Overtime: 0.15 hrs
```

**Timesheet statuses — THE LIFECYCLE:**
```
draft  →  submitted  →  approved
                    ↘  rejected  →  (employee fixes) → submitted again
```

| Status | Meaning |
|--------|---------|
| `draft` | Being built, not yet sent to admin |
| `submitted` | Employee sent it for review |
| `approved` | Admin confirmed the hours — ready for payroll |
| `rejected` | Admin sent it back (wrong hours, missing entries, etc.) |

**Timesheet Entries** = the individual rows inside the timesheet (one per work day).

---

## Step-by-Step: Full Weekly Cycle

### STEP 1 — Admin sets up the week's schedule

1. Go to **Admin Panel → Scheduling → Shifts**
2. Click **New Shift**
3. Fill in:
   - **Employee** — who is working
   - **Location** — where
   - **Start / End datetime** — e.g. Monday 08:00 → 16:00
   - **Break** — e.g. 30 minutes
4. Save. Repeat for each shift that week.

> **Tip:** You can also use **Schedule Builder** to see the whole week in a grid view and create shifts there.

---

### STEP 2 — Employee sees their schedule

Employee opens the **Employee Portal** app and goes to **My Schedule** (Dashboard).  
They can see:
- Today's shifts
- Upcoming shifts for the week

This calls `GET /api/dashboard`.

---

### STEP 3 — Employee clocks IN

When the employee arrives at work, they open the app → **Clock In/Out page**.

1. The app checks their current status: `GET /api/time-clock/status`
2. Employee taps **Clock In**
3. App sends: `POST /api/time-clock` with `type: "clock_in"` + GPS location
4. System records a **TimeClock** event

> If the employee is going on break: tap **Break Start** → **Break End** when returning.

---

### STEP 4 — Employee clocks OUT

At the end of their shift:
1. Employee taps **Clock Out**
2. App sends: `POST /api/time-clock` with `type: "clock_out"`
3. System records the clock-out event
4. System calculates total minutes worked and creates/updates an **Attendance** record

---

### STEP 5 — Timesheet is built (draft)

At the end of the pay period, a **Timesheet** is created (either automatically by the system or manually by the admin) covering the period e.g. May 19–25.

Each day the employee worked becomes one **Timesheet Entry** pulled from their attendance records:

```
Timesheet (Jane, May 19–25, Main Store)
  └─ Entry: May 19  08:03–16:12  7.65 hrs  [from attendance]
  └─ Entry: May 20  08:00–16:30  8.0 hrs   [from attendance]
  └─ Entry: May 22  09:00–17:00  7.5 hrs   [from attendance]
  └─ Entry: May 23  08:00–12:00  4.0 hrs   [from attendance]
```

Status is `draft` at this point.

**Admin creates it manually:**
1. **Admin Panel → Time & Attendance → Timesheets → New**
2. Select employee, location, period start/end
3. Set status to `draft`
4. Save

---

### STEP 6 — Employee reviews and submits

Employee opens **Employee Portal → My Timesheet**.

1. They see their timesheet entries (`GET /api/timesheets/{id}`)
2. They review the hours look correct
3. They tap **Submit** — this calls `POST /api/timesheets/{id}/submit`
4. Status changes: `draft` → `submitted`
5. Admin gets notified

---

### STEP 7 — Admin approves or rejects

Admin opens **Admin Panel → Time & Attendance → Timesheets**.

The list defaults to the **Pending Approval** tab showing all `submitted` timesheets.

- Click **Approve** → status becomes `approved`, timestamp recorded. Done.
- Click **Reject** → status becomes `rejected`. Employee can see this and re-submit after correcting.

> **Bulk approve:** Tick multiple timesheets → **Approve Selected** to process many at once.

---

## Who Does What — Quick Reference

| Action | Who | Where |
|--------|-----|-------|
| Create locations & departments | Admin | Admin Panel → Organization |
| Add employees | Admin | Admin Panel → Organization → Employees |
| Create shifts | Admin | Admin Panel → Scheduling → Shifts |
| See my schedule | Employee | Portal → Dashboard |
| Clock in / Clock out | Employee | Portal → Clock In/Out |
| View my attendance | Employee | Portal → My Timesheet (entries) |
| Create a timesheet | Admin (or system) | Admin Panel → Timesheets → New |
| Submit timesheet | Employee | Portal → My Timesheet → Submit |
| Approve / Reject timesheet | Admin | Admin Panel → Timesheets |
| Request leave | Employee | Portal → Leave Request Form |
| Approve leave | Admin | Admin Panel → Leave Management |
| Swap a shift | Employee | Portal → Shift Swap Request |
| Approve shift swap | Admin | Admin Panel → Scheduling |

---

## Common Questions

**Q: What if an employee forgets to clock out?**  
Their attendance record will have no `clock_out_at`. Admin can see this and manually correct the timesheet entry (set end time, total hours) before approving.

**Q: Can a shift exist without clock in/out?**  
Yes. A shift is just a plan. Clock in/out is the reality. If an employee had a shift but never clocked in, their attendance status becomes `absent` or `no_show`.

**Q: Does a timesheet get created automatically?**  
Not yet — currently an admin creates it manually and the employee submits it. Auto-generation from attendance records can be added as a scheduled job later.

**Q: What is an "open shift"?**  
A shift with no employee assigned. Admins can create open shifts that employees can claim via **Open Shift Claims**.

**Q: Can one employee have two timesheets for the same period?**  
No. The system enforces one timesheet per employee per period. If you try to create a duplicate you will see: *"A timesheet for this employee already exists for the selected period."*

---

## Database Relationship Map

```
Company
 └── Location(s)
      └── Department(s)
      └── Schedule(s)  ← week plan
           └── Shift(s)  ← individual day/time blocks
                └── TimeClock events  ← clock in/out raw events
                └── Attendance  ← daily summary (calculated from time clocks)
                     └── TimesheetEntry  ← pulled into timesheet
      └── Timesheet(s)  ← pay period summary per employee
           └── TimesheetEntry(s)  ← one row per work day
 └── User(s) / Employees
      └── LeaveRequest(s)
      └── LeaveBalance(s)
      └── Availability(s)
      └── ShiftSwap(s)
      └── TaskAssignment(s)
```
