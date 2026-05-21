# ShiftFlow Employee Portal — API Documentation

**Base URL:** `http://your-domain.com/api`  
**Auth:** Bearer token (Laravel Sanctum)  
**Content-Type:** `application/json`

---

## Authentication

### Login
```
POST /api/auth/login
```
**Body:**
```json
{
  "email": "employee@example.com",
  "password": "secret",
  "device_name": "MyPhone" // optional
}
```
**Response `200`:**
```json
{
  "token": "1|abcdef...",
  "user": {
    "id": 1,
    "name": "Jane Doe",
    "email": "jane@example.com",
    "phone": "+1234567890",
    "avatar": "https://...",
    "status": "active",
    "company_id": 1,
    "profile": {
      "employee_code": "EMP001",
      "job_title": "Barista",
      "employment_type": "full_time",
      "pay_type": "hourly",
      "hire_date": "2024-01-15"
    },
    "created_at": "2024-01-15T00:00:00.000Z"
  }
}
```

### Get Current User
```
GET /api/auth/me
Authorization: Bearer {token}
```

### Logout
```
POST /api/auth/logout
Authorization: Bearer {token}
```
**Response `200`:** `{ "message": "Logged out successfully." }`

---

## Dashboard (My Schedule)
```
GET /api/dashboard
Authorization: Bearer {token}
```
**Response `200`:**
```json
{
  "data": {
    "today_shifts": [
      {
        "id": 12,
        "title": "Morning Shift",
        "start_datetime": "2026-05-22T08:00:00.000Z",
        "end_datetime": "2026-05-22T16:00:00.000Z",
        "break_duration_minutes": 30,
        "duration_minutes": 450,
        "status": "confirmed",
        "location": { "id": 1, "name": "Main Store" },
        "department": { "id": 2, "name": "Floor" }
      }
    ],
    "upcoming_shifts": [ /* next 10 shifts */ ],
    "announcements": [
      {
        "id": 3,
        "title": "Public Holiday Reminder",
        "body": "We are closed on Monday...",
        "is_pinned": true,
        "created_at": "2026-05-20T10:00:00.000Z"
      }
    ],
    "pending_leave": 1
  }
}
```

---

## Timesheets

### List My Timesheets
```
GET /api/timesheets
Authorization: Bearer {token}
```
Returns paginated list. Each item includes `id`, `period_start`, `period_end`, `status`, `total_hours`, `submitted_at`, `approved_at`, `location`.

**Timesheet statuses:** `draft` | `submitted` | `approved` | `rejected`

### Get Single Timesheet
```
GET /api/timesheets/{id}
Authorization: Bearer {token}
```
Same as above but includes `entries` array:
```json
"entries": [
  {
    "id": 1,
    "date": "2026-05-19",
    "start_time": "08:00:00",
    "end_time": "16:30:00",
    "break_minutes": 30,
    "total_hours": 8.0,
    "overtime_hours": 0.0,
    "is_manual": false
  }
]
```

### Submit Timesheet
```
POST /api/timesheets/{id}/submit
Authorization: Bearer {token}
```
Changes status from `draft` → `submitted`. Returns updated timesheet.

**Error `422`** if not in `draft` status.

---

## Clock In / Out

### Get Today's Clock Status
```
GET /api/time-clock/status
Authorization: Bearer {token}
```
**Response `200`:**
```json
{
  "is_clocked_in": true,
  "last_event": {
    "type": "clock_in",
    "clocked_at": "2026-05-22T08:02:34.000Z"
  }
}
```

### Record a Clock Event
```
POST /api/time-clock
Authorization: Bearer {token}
```
**Body:**
```json
{
  "type": "clock_in",          // clock_in | clock_out | break_start | break_end
  "location_id": 1,            // required
  "shift_id": 12,              // optional
  "lat": -6.2088,              // optional GPS
  "lng": 106.8456,             // optional GPS
  "accuracy_meters": 10.5,     // optional
  "device_type": "mobile"      // mobile | kiosk | web
}
```
**Response `201`:**
```json
{
  "id": 45,
  "type": "clock_in",
  "clocked_at": "2026-05-22T08:02:34.000Z"
}
```

### Clock Event History
```
GET /api/time-clock/history
Authorization: Bearer {token}
```
Returns paginated list of all clock events.

---

## Leave

### Get Leave Balances
```
GET /api/leave/balances
Authorization: Bearer {token}
```
**Response `200`:**
```json
{
  "data": [
    {
      "id": 1,
      "leave_type": { "id": 1, "name": "Annual Leave" },
      "year": 2026,
      "total_days": 14.0,
      "used_days": 3.0,
      "pending_days": 2.0,
      "remaining_days": 9.0
    }
  ]
}
```

### Get Leave Types
```
GET /api/leave/types
Authorization: Bearer {token}
```

### List My Leave Requests
```
GET /api/leave/requests
Authorization: Bearer {token}
```

**Leave request statuses:** `pending` | `approved` | `rejected` | `cancelled`

### Create Leave Request
```
POST /api/leave/requests
Authorization: Bearer {token}
```
**Body:**
```json
{
  "leave_type_id": 1,
  "start_date": "2026-06-01",
  "end_date": "2026-06-03",
  "reason": "Family vacation"
}
```
`total_days` is calculated automatically (weekdays only).

### Get Single Leave Request
```
GET /api/leave/requests/{id}
Authorization: Bearer {token}
```

### Cancel Leave Request
```
POST /api/leave/requests/{id}/cancel
Authorization: Bearer {token}
```
Only `pending` requests can be cancelled. **Error `422`** otherwise.

---

## Availability

### List My Availability
```
GET /api/availability
Authorization: Bearer {token}
```
**Response `200`:**
```json
{
  "data": [
    {
      "id": 1,
      "type": "unavailable",       // available | unavailable | preferred
      "recurrence": "weekly",       // once | weekly
      "date": null,
      "day_of_week": 0,             // 0=Sun, 1=Mon, ... 6=Sat
      "start_time": "00:00:00",
      "end_time": "23:59:00",
      "reason": "Religious observance",
      "created_at": "2026-05-01T00:00:00.000Z"
    }
  ]
}
```

### Add Availability
```
POST /api/availability
Authorization: Bearer {token}
```
**Body:**
```json
{
  "type": "unavailable",
  "recurrence": "once",
  "date": "2026-06-10",        // required when recurrence=once
  "day_of_week": null,
  "start_time": "09:00",
  "end_time": "17:00",
  "reason": "Doctor appointment"
}
```
For weekly: omit `date`, provide `day_of_week` (0–6).

### Update Availability
```
PUT /api/availability/{id}
Authorization: Bearer {token}
```
Accepts same fields as store (all optional).

### Delete Availability
```
DELETE /api/availability/{id}
Authorization: Bearer {token}
```
**Response `200`:** `{ "message": "Availability deleted." }`

---

## Shift Swaps

### List My Swap Requests
```
GET /api/shift-swaps
Authorization: Bearer {token}
```
Returns all swaps where the user is the requester **or** the target.

**Swap statuses:** `pending` | `approved` | `rejected` | `cancelled`

### Request a Shift Swap
```
POST /api/shift-swaps
Authorization: Bearer {token}
```
**Body:**
```json
{
  "requester_shift_id": 12,   // must be one of your own shifts
  "target_shift_id": 15,
  "target_id": 7,             // user you want to swap with
  "reason": "Family event"
}
```

### Get Single Swap Request
```
GET /api/shift-swaps/{id}
Authorization: Bearer {token}
```

### Cancel Swap Request
```
POST /api/shift-swaps/{id}/cancel
Authorization: Bearer {token}
```
Only the requester can cancel; only `pending` swaps. Returns updated swap.

---

## Messages / Inbox

### List Conversations
```
GET /api/conversations
Authorization: Bearer {token}
```
**Response `200`:**
```json
{
  "data": [
    {
      "id": 1,
      "type": "direct",         // direct | group
      "name": null,
      "participants": [
        { "id": 1, "name": "Jane Doe", "avatar": "https://..." },
        { "id": 4, "name": "John Smith", "avatar": null }
      ],
      "last_message": {
        "id": 99,
        "body": "See you tomorrow!",
        "type": "text",
        "sender": { "id": 4, "name": "John Smith", "avatar": null },
        "created_at": "2026-05-22T14:30:00.000Z"
      },
      "unread_count": 2
    }
  ]
}
```

### Start a Direct Conversation
```
POST /api/conversations/direct
Authorization: Bearer {token}
```
**Body:** `{ "user_id": 4 }`  
Returns existing conversation if one already exists, otherwise creates it.

### Get Messages in a Conversation
```
GET /api/conversations/{id}/messages
Authorization: Bearer {token}
```
Returns paginated messages (newest first). Also marks conversation as read.

### Send a Message
```
POST /api/conversations/{id}/messages
Authorization: Bearer {token}
```
**Body:**
```json
{
  "body": "Hello!",
  "type": "text"    // text | image | file — default: text
}
```
**Response `200`:** Single `MessageResource`.

---

## Tasks

### List My Tasks
```
GET /api/tasks
Authorization: Bearer {token}
```
Returns tasks assigned to the authenticated user, ordered: `pending` → `in_progress` → `completed`.

**Response `200`:**
```json
{
  "data": [
    {
      "id": 5,
      "title": "Restock shelves",
      "description": "Restock the dairy section.",
      "priority": "high",
      "status": "pending",
      "due_date": "2026-05-23",
      "created_by": { "id": 2, "name": "Manager Sam" },
      "assignment": {
        "status": "pending",
        "completed_at": null,
        "notes": null
      },
      "created_at": "2026-05-22T09:00:00.000Z"
    }
  ]
}
```

**Task priorities:** `low` | `medium` | `high` | `urgent`  
**Task/assignment statuses:** `pending` | `in_progress` | `completed`

### Get Single Task
```
GET /api/tasks/{id}
Authorization: Bearer {token}
```

### Update Task Status
```
PATCH /api/tasks/{id}/status
Authorization: Bearer {token}
```
**Body:**
```json
{
  "status": "in_progress",
  "notes": "Started on this."
}
```
**Response `200`:**
```json
{
  "task_id": 5,
  "status": "in_progress",
  "completed_at": null,
  "notes": "Started on this."
}
```

---

## Profile Settings

### Get Profile
```
GET /api/profile
Authorization: Bearer {token}
```
Returns full `UserResource` including `profile`.

### Update Profile
```
POST /api/profile
Authorization: Bearer {token}
Content-Type: multipart/form-data
```
**Fields (all optional):**

| Field | Type | Description |
|-------|------|-------------|
| `name` | string | Display name |
| `phone` | string | Phone number |
| `avatar` | file (image) | Max 2 MB |

### Change Password
```
POST /api/profile/change-password
Authorization: Bearer {token}
```
**Body:**
```json
{
  "current_password": "oldpassword",
  "password": "newpassword",
  "password_confirmation": "newpassword"
}
```

### Change PIN
```
POST /api/profile/change-pin
Authorization: Bearer {token}
```
**Body:** `{ "pin": "1234" }` — digits only, 4–10 characters.

---

## Error Responses

| Code | Meaning |
|------|---------|
| `401` | Unauthenticated — missing or invalid token |
| `403` | Forbidden — account inactive or insufficient permission |
| `404` | Record not found |
| `422` | Validation error |

**Validation error shape:**
```json
{
  "message": "The email field is required.",
  "errors": {
    "email": ["The email field is required."]
  }
}
```

---

## Pagination

All paginated endpoints return:
```json
{
  "data": [ /* items */ ],
  "links": {
    "first": "https://.../api/timesheets?page=1",
    "last":  "https://.../api/timesheets?page=4",
    "prev":  null,
    "next":  "https://.../api/timesheets?page=2"
  },
  "meta": {
    "current_page": 1,
    "last_page": 4,
    "per_page": 15,
    "total": 52
  }
}
```
