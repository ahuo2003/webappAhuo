# Peer Tutoring Hub · Lab 7

PHP 8.0+ with PDO SQLite and mbstring. The HTTP test harness also uses Python 3.

## Start in GitHub Codespaces

From the extracted `lab7` directory, run:

```sh
php bin/setup.php
php bin/create-admin.php "Lab Administrator" admin@example.edu
LAB7_SECURE_COOKIE=1 php -S 0.0.0.0:8000 php/router.php
```

Open forwarded port 8000 using HTTPS. For plain HTTP on localhost, omit `LAB7_SECURE_COOKIE=1` and bind to `127.0.0.1:8000`. Use the included router so only public pages and CSS are served. The admin command prints a generated password. Use it to log in and change it through My Profile. For Student and Tutor test accounts, use Create account.

## Application and data

| Tier | Files | Responsibility |
| --- | --- | --- |
| Presentation | `php/`, `includes/` | Forms, output encoding, session and CSRF checks, redirects |
| Business logic | `services/` | Validation, account rules, credential verification, tutoring workflows, transactions |
| Data access | `repositories/`, `config/db.php`, `database/` | PDO, prepared SQL, schema and foreign keys |

`bin/setup.php` works on a fresh database and on the included Lab 6 database. The migration keeps the existing `student_id` request relationship and adds a unique, nullable `students.user_id` foreign key. New submissions resolve the student row from the session account. Profile details come from the linked account when displaying requests.

The included database preserves the three Lab 6 requests. Administrators can review legacy requests immediately. Legacy student rows start unlinked because an email match alone does not establish ownership. After verifying identity, an operator can link an unlinked legacy row to an unlinked active Student account using `php bin/link-legacy-student.php STUDENT_ID USER_ID`. Do this before the student submits a new request and gets a separate student row. A legacy email collision on submission prompts the student to contact an administrator. Existing plain-text topics continue to display. New submissions preserve checkbox topics and the written reason together as JSON.

| Access | Tutoring workflow |
| --- | --- |
| Student | Submit requests, see their own requests, and edit or cancel their own Open requests |
| Tutor | Manage their own account |
| Administrator | Review all requests and edit or cancel Open requests |

### Design decisions

- Passwords must be 12–72 bytes. The upper bound avoids bcrypt truncation. PHP stores password hashes using `PASSWORD_DEFAULT`.
- Sessions expire after 20 minutes of inactivity. Password changes invalidate existing sessions and require a fresh login. Suspending an account also invalidates its sessions.
- The last active Administrator is protected against demotion, deactivation and deletion.
- Permanent deletion is disabled by default. If a retention policy is approved, the server operator can set `LAB7_ALLOW_ACCOUNT_DELETION=1` when starting the server. Confirmed deletion still requires Administrator access, preserves the last active Administrator and blocks accounts with any related tutoring requests, including cancelled requests.

## Run the tests

```sh
php tests/validation-tests.php
php tests/integration-tests.php
php tests/user-management-tests.php
python3 tests/http-tests.py
```

The PHP integration harnesses use in-memory SQLite. The HTTP harness starts a disposable localhost server and seeds its own Administrator in a temporary database. It uses `php` from PATH by default and accepts a `PHP_BIN` override. See [TRACEABILITY.md](TRACEABILITY.md) for requirements and test evidence.
