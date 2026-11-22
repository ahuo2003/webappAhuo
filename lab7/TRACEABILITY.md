# Lab 7 requirements traceability

Scope includes the User Management Design Document, Authentication Reading Handout, Lab 6 application and the supplied Application Builder, Test Harness Generator and Traceability Auditor guidance.

| ID | Requirement | Implementation evidence | Test evidence |
| --- | --- | --- | --- |
| UM-01 | Create User Account | `register.php` → `registerUser()` → `insertUser()`; `users.email` unique, status Active by default | PHP and HTTP check Student/Tutor registration, duplicates and prohibited Administrator role. PHP checks password mismatch. |
| UM-02 | View Own Account | `profile.php` → `requireLogin()` → `findUserById()`; session ID selects safe profile fields | HTTP checks guest redirect and that a supplied user ID does not change whose profile is displayed. |
| UM-03 | View and Search User Accounts | `users.php` and `manage-user.php` require Administrator; `searchUsers()` filters prepared SQL against `users` | PHP and HTTP check combined search and empty result. HTTP checks denial for a Student. |
| UM-04 | Update Own Account | `edit-profile.php` uses current identity → `updateOwnProfile()` → `saveUserProfile()`; only `full_name` and `email` are writable | PHP and HTTP check updates and rejection of changes to role. PHP checks duplicate email. |
| UM-05 | Change Password | `change-password.php` → `changeOwnPassword()` → `replacePasswordHash()`; changes hash and session version | PHP checks current password, confirmation and hash replacement. HTTP checks reauthentication with the new password. |
| UM-06 | Update User Role | Confirmed POST in `manage-user.php` → `manageUser()` → `saveUserRole()`; role allowlist and last-active-admin rule | PHP checks denial, last-admin safeguard and successful reassignment. HTTP checks direct endpoint access and immediate role refresh. |
| UM-07 | Activate or Deactivate Account | Confirmed POST in `manage-user.php` → `manageUser()` → `saveUserStatus()`; updates status and session version | PHP checks invalid transition and last-admin safeguard. HTTP checks current session denial, inactive login denial and reactivation. |
| UM-08 | Delete User Account | Confirmation in `manage-user.php` → `manageUser()` → `userHasRequests()` and conditional `deleteUser()` | HTTP checks that confirmation alone preserves the account, and POST honors the default policy and the dependency check. PHP checks explicitly enabled deletion and last-admin protection. |
| UM-09 | Log In | `login.php` → `authenticateUser()` → `findLoginUser()`, then `startLogin()`; prepared lookup, generic failure, regenerated session ID | PHP checks credential verification. HTTP checks invalid credentials and session ID rotation. |
| UM-10 | Log Out | POST in `logout.php` → `requireLogin()`, `verifyCsrfToken()`, `logoutSession()`; clears session and expires cookie | HTTP checks GET and missing-token denial, expired cookie, protected-page redirect and 20-minute idle expiry. |
| UC01 | Request Tutoring | `request-form.php` and action pages → `submitRequest()`, `reviseRequest()`, `cancelOpenRequest()`; own-account mapping and Open-only writes | Legacy PHP regressions and HTTP checks cover request creation, identity, editing, cancellation and retention. |
| UC02 | View and Filter Requests | `requests.php` → `listRequests()`; filters and SQL predicate on `students.user_id` for Student | Legacy PHP regressions check filter combinations. HTTP checks cross-account exclusion and Tutor denial. |

## Verification and scope

- Executed with PHP 8.4.27. All PHP files passed lint. All 10 validation, 13 Lab 6 integration and 17 user-management checks passed.
- Six HTTP test groups passed. They exercise the normal controller entry points, cookie-based sessions and router. The HTTPS cookie flag is configuration-driven. The automated HTTP suite runs over localhost HTTP.
- The migration and verified-identity linkage support the handout's account-to-student relationship. The last-administrator and deletion checks run inside one write transaction so an overlapping admin change cannot invalidate their counts.
- Account deletion is conditional on operator approval. Linked tutoring records block deletion even when the policy switch is enabled.
