"""Run with python3 tests/http-tests.py. Uses a disposable database and server."""
import datetime
import http.cookiejar
import os
from pathlib import Path
import re
import shutil
import socket
import sqlite3
import subprocess
import tempfile
import time
import urllib.error
import urllib.parse
import urllib.request


class NoRedirect(urllib.request.HTTPRedirectHandler):
    def redirect_request(self, *args, **kwargs):
        return None


class Client:
    def __init__(self, base):
        self.base = base
        self.cookies = http.cookiejar.CookieJar()
        self.opener = urllib.request.build_opener(NoRedirect(), urllib.request.HTTPCookieProcessor(self.cookies))
        self.token = None

    def request(self, path, status=200, data=None, csrf=True):
        encoded = None
        if data is not None:
            if csrf:
                assert self.token, "Load the form before submitting it"
                data = {**data, "csrf_token": self.token}
            encoded = urllib.parse.urlencode(data, doseq=True).encode()
        try:
            response = self.opener.open(self.base + path, encoded, timeout=5)
        except urllib.error.HTTPError as error:
            response = error
        with response:
            body = response.read().decode()
            assert response.code == status, (path, status, response.code, body[:500])
            match = re.search(r'name="csrf_token" value="([a-f0-9]{64})"', body)
            if match:
                self.token = match[1]
            return body, response.headers

    def session_id(self):
        return next(cookie.value for cookie in self.cookies if cookie.name == "LAB7SESSION")


def run_tests(base, root, session_dir, admin_password):
    guest, student, tutor, admin = (Client(base) for _ in range(4))
    password = "testing-only password"
    student_data = {
        "full_name": "Alex Test",
        "email": "alex@example.edu",
        "role": "Student",
        "password": password,
        "confirmation": password,
    }
    tutor_data = {**student_data, "full_name": "Terry Tutor", "email": "tutor@example.edu", "role": "Tutor"}

    for client, data in [(student, student_data), (tutor, tutor_data)]:
        client.request("register.php")
        client.request("register.php", 403, data, csrf=False)
        client.request("register.php", 422, {**data, "role": "Administrator"})
        client.request("register.php", 303, data)
        client.request("register.php", 422, data)
        client.request("login.php")
        before = client.session_id()
        client.request("login.php", 401, {"email": data["email"], "password": "incorrect"})
        client.request("login.php", 303, {"email": data["email"], "password": password})
        assert before != client.session_id()
    print("PASS UM-01 and UM-09 registration, CSRF, credential rejection and session rotation")

    guest.request("profile.php", 303)
    body, _ = student.request("profile.php?user_id=2")
    assert "Alex Test" in body and "Terry Tutor" not in body
    body, _ = student.request("edit-profile.php")
    assert "alex@example.edu" in body
    student.request("edit-profile.php", 303, {
        "full_name": "Alex Updated", "email": "alex.updated@example.edu", "role": "Administrator", "user_id": 2,
    })
    body, _ = student.request("profile.php")
    assert "Alex Updated" in body and "alex.updated@example.edu" in body and "Manage users" not in body
    print("PASS UM-02 and UM-04 session-owned profile and permitted fields")

    student.request("users.php", 403)
    student.request("manage-user.php?user_id=2&action=role", 403, {"value": "Administrator"})
    tutor.request("requests.php", 403)
    tutor.request("request-form.php", 403)
    tutor.request("process-request.php", 403, tutor_data)
    admin.request("login.php")
    admin.request("login.php", 303, {"email": "admin@example.edu", "password": admin_password})
    body, _ = admin.request("users.php?q=alex&status=Active&role=Student")
    with sqlite3.connect(root / "database/tutoring.db") as db:
        student_id = db.execute("SELECT user_id FROM users WHERE email='alex.updated@example.edu'").fetchone()[0]
        tutor_id = db.execute("SELECT user_id FROM users WHERE email='tutor@example.edu'").fetchone()[0]
    assert "Alex Updated" in body and "Terry Tutor" not in body and "password_hash" not in body
    body, _ = admin.request("users.php?q=no-such-account")
    assert "No accounts matched" in body
    detail = f"manage-user.php?user_id={tutor_id}"
    admin.request(detail + "&action=role")
    admin.request(detail + "&action=role", 303, {"value": "Student"})
    tutor.request("requests.php")
    admin.request(detail + "&action=status")
    admin.request(detail + "&action=status", 303, {"value": "Inactive"})
    tutor.request("profile.php", 303)
    tutor.request("login.php")
    tutor.request("login.php", 401, {"email": tutor_data["email"], "password": password})
    admin.request(detail + "&action=status")
    admin.request(detail + "&action=status", 303, {"value": "Active"})
    tutor.request("login.php", 303, {"email": tutor_data["email"], "password": password})
    tutor.request("requests.php")
    print("PASS UM-03, UM-06 and UM-07 admin search, role refresh and suspension/reactivation")

    student.request("request-form.php")
    admin.request("request-form.php", 403)
    payload = {
        "form_name": "tutoring_request",
        "course": "IS 6465",
        "reason": "Please help with testing the form.",
        "topics[]": ["Exam review", "Study plan"],
        "preferreddate": (datetime.date.today() + datetime.timedelta(days=7)).isoformat(),
        "preferredtime": "Morning",
        "student_email": tutor_data["email"],
    }
    student.request("process-request.php", 403, payload, csrf=False)
    _, headers = student.request("process-request.php", 303, payload)
    details = headers["Location"]
    request_id = details.split("=")[-1]
    tutor.request(details, 404)
    tutor.request(f"edit-request.php?request_id={request_id}", 404)
    tutor.request("update-request.php", 404, {**payload, "request_id": request_id})
    tutor.request("delete-request.php", 404, {"request_id": request_id})
    body, _ = student.request(details)
    assert "Alex Updated" in body and "Study plan" in body
    body, _ = tutor.request("requests.php?course=IS+6465&status=Open")
    assert "Please help with testing the form." not in body
    admin.request(details)
    admin.request("update-request.php", 303, {**payload, "request_id": request_id, "reason": "Edited by an administrator."})
    admin.request(f"delete-request.php?request_id={request_id}")
    body, _ = student.request(details)
    assert "Edited by an administrator." in body and "<dd>Open</dd>" in body
    student.request("delete-request.php", 303, {"request_id": request_id})
    student.request("delete-request.php", 409, {"request_id": request_id})
    body, _ = student.request(details)
    assert "<dd>Cancelled</dd>" in body
    print("PASS UC01/UC02 request submission, filters, ownership, admin access and retained cancellation")

    admin.request(detail + "&action=delete")
    admin.request(detail)
    admin.request(detail + "&action=delete", 422, {})
    blocked, _ = admin.request(f"manage-user.php?user_id={student_id}&action=delete", 422, {})
    assert "has tutoring records" in blocked
    print("PASS UM-08 confirmation GET leaves account intact and POST honors policy and retention")

    student.request("change-password.php")
    updated = "updated testing password"
    student.request("change-password.php", 303, {"current_password": password, "password": updated, "confirmation": updated})
    student.request("profile.php", 303)
    student.request("login.php")
    student.request("login.php", 401, {"email": "alex.updated@example.edu", "password": password})
    student.request("login.php", 303, {"email": "alex.updated@example.edu", "password": updated})
    student.request("profile.php")
    student.request("logout.php", 405)
    student.request("logout.php", 403, {}, csrf=False)
    _, headers = student.request("logout.php", 303, {})
    assert "Max-Age=0" in headers["Set-Cookie"]
    assert not any(cookie.name == "LAB7SESSION" for cookie in student.cookies)
    student.request("profile.php", 303)

    session_file = session_dir / ("sess_" + admin.session_id())
    saved = session_file.read_text()
    assert "last_activity|i:" in saved
    session_file.write_text(re.sub(r"last_activity\|i:\d+;", f"last_activity|i:{int(time.time()) - 1201};", saved))
    _, headers = admin.request("profile.php", 303)
    assert headers["Location"] == "login.php?expired=1"
    guest.request("../database/tutoring.db", 404)
    guest.request("../tests/validation-tests.php", 404)
    print("PASS UM-05 and UM-10 password change, POST-only logout, session timeout and private files")


def main():
    source = Path(__file__).resolve().parents[1]
    php = os.environ.get("PHP_BIN", "php")
    with tempfile.TemporaryDirectory(prefix="lab7-http-") as directory:
        root = Path(directory) / "app"
        shutil.copytree(source, root, ignore=shutil.ignore_patterns("tutoring.db*"))
        subprocess.run([php, "bin/setup.php"], cwd=root, check=True, capture_output=True)
        setup = subprocess.check_output([php, "bin/create-admin.php", "Test Admin", "admin@example.edu"], cwd=root, text=True)
        admin_password = setup.strip().splitlines()[-1]
        session_dir = Path(directory) / "sessions"
        session_dir.mkdir()
        with socket.socket() as candidate:
            candidate.bind(("127.0.0.1", 0))
            port = candidate.getsockname()[1]
        with (Path(directory) / "server.log").open("w+") as log:
            env = {**os.environ, "LAB7_ALLOW_ACCOUNT_DELETION": "0", "LAB7_SECURE_COOKIE": "0"}
            server = subprocess.Popen(
                [php, "-d", f"session.save_path={session_dir}", "-S", f"127.0.0.1:{port}", "php/router.php"],
                cwd=root, stdout=log, stderr=log, env=env,
            )
            try:
                for _ in range(50):
                    try:
                        with socket.create_connection(("127.0.0.1", port), timeout=0.1):
                            break
                    except OSError:
                        time.sleep(0.05)
                else:
                    raise RuntimeError("PHP test server did not start")
                run_tests(f"http://127.0.0.1:{port}/php/", root, session_dir, admin_password)
            except Exception:
                log.seek(0)
                print(log.read())
                raise
            finally:
                server.terminate()
                server.wait(timeout=5)


if __name__ == "__main__":
    main()
