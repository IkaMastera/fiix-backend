Fiix.ge API Response Contract (v1)
Purpose

This document defines the official JSON response format for all /api/v1/* endpoints.

Goals:

Every endpoint returns a predictable structure.

Clients (Mobile, Technician, Admin) can handle success and error uniformly.

No raw Eloquent models are returned.

Error handling is stable and machine-readable.

This contract is enforced centrally in Laravel (bootstrap/app.php) and via API Resources.

1. General Rules

All responses are JSON.

All keys use snake_case.

Dates use ISO-8601 format with timezone.

No internal exception messages or stack traces are exposed in production.

No endpoint returns raw models (return $model; is forbidden).

2. Success Response Format
2.1 Standard Success Envelope
{
  "data": {},
  "meta": {
    "request_id": "uuid-or-null",
    "timestamp": "2026-02-25T11:12:00+04:00"
  }
}
Rules

data is always present on success.

meta is always present.

meta.request_id may be null until request tracing is implemented.

meta.timestamp must be server-generated.

2.2 Collection Example
{
  "data": [
    { "id": 1 },
    { "id": 2 }
  ],
  "meta": {
    "request_id": null,
    "timestamp": "2026-02-25T11:12:00+04:00"
  }
}
2.3 Paginated Collection Example
{
  "data": [
    { "id": 1 }
  ],
  "meta": {
    "request_id": null,
    "timestamp": "2026-02-25T11:12:00+04:00",
    "pagination": {
      "page": 1,
      "per_page": 15,
      "total": 120,
      "total_pages": 8
    }
  }
}
3. Error Response Format
3.1 Standard Error Envelope
{
  "error": {
    "code": "stable_error_code",
    "message": "Human readable message.",
    "details": {}
  },
  "meta": {
    "request_id": null,
    "timestamp": "2026-02-25T11:12:00+04:00"
  }
}
Rules

error.code is stable and machine-readable.

error.message is safe for end users.

error.details is optional and may include structured information.

Internal stack traces must never be exposed in production.

4. HTTP Status Code Mapping (v1)
HTTP	code	Meaning
401	unauthenticated	User not logged in
403	forbidden	User lacks permission
404	not_found	Resource does not exist
409	conflict	State conflict or invariant violation
422	validation_failed	Request validation failed
429	rate_limited	Too many requests
500	server_error	Unexpected system error
5. Conflict Cases (409)

Used for:

Invalid job state transition

Active assignment already exists

Concurrency conflicts

Business invariant violations

Example:

{
  "error": {
    "code": "conflict",
    "message": "Transition not allowed.",
    "details": {
      "from": "done",
      "to": "in_progress"
    }
  },
  "meta": {
    "request_id": null,
    "timestamp": "2026-02-25T11:12:00+04:00"
  }
}
6. Validation Errors (422)
{
  "error": {
    "code": "validation_failed",
    "message": "Validation failed.",
    "details": {
      "service_id": ["The service_id field is required."]
    }
  },
  "meta": {
    "request_id": null,
    "timestamp": "2026-02-25T11:12:00+04:00"
  }
}
7. Versioning

This contract applies to /api/v1/*.

Breaking changes require a new version.

8. Enforcement Rules

Controllers must return API Resources.

Exception formatting must be centralized in bootstrap/app.php.

No manual JSON shaping inside controllers.

No leaking raw exceptions.

End of contract.