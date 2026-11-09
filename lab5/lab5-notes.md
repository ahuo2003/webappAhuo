Part 1:


1.1) 

sqlite> .tables
students           tutoring_requests

sqlite> .schema
CREATE TABLE students (
  student_id INTEGER PRIMARY KEY,
  student_name TEXT NOT NULL,
  student_email TEXT NOT NULL UNIQUE
);
CREATE TABLE tutoring_requests (
  request_id INTEGER PRIMARY KEY,
  student_id INTEGER NOT NULL,
  course TEXT NOT NULL,
  topic TEXT NOT NULL,
  preferred_date TEXT NOT NULL,
  preferred_time TEXT NOT NULL,
  status TEXT NOT NULL DEFAULT 'Open',
  created_at TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (student_id) REFERENCES students(student_id)
);

1.2)

student_id is the primary key in students, and request_id is the primary key in tutoring_requests.

1.3)

student_id in tutoring_requests is the foreign key, and it references the studet_id primary key in the table "students."

1.4) 

student_email is marked "unique" because it wouldn't require uniqueness otherwise, and a system where multiple students share the same email makes no sense.

2.1)

INSERT OR IGNORE INTO students (student_name, student_email)
VALUES ('Jordan Lee', 'jordan.lee@example.com');

INSERT OR IGNORE INTO students (student_name, student_email)
VALUES ('Avery Patel', 'avery.patel@example.com');

INSERT OR IGNORE INTO tutoring_requests
  (student_id, course, topic, preferred_date, preferred_time)
VALUES
  (2, 'IS 6465', 'Forms', '2026-11-09', '14:00');

INSERT OR IGNORE INTO tutoring_requests
  (student_id, course, topic, preferred_date, preferred_time)
VALUES
  (2, 'IS 6465', 'SQL SELECT', '2026-11-09', '15:00');

INSERT OR IGNORE INTO tutoring_requests
  (student_id, course, topic, preferred_date, preferred_time)
VALUES
  (3, 'IS 6465', 'SQLite CRUD', '2026-11-10', '14:00');

2.2)

SELECT * FROM STUDENTS;
1|Maria Lopez|maria.lopez@example.com //I kept this one in to make it feel more populated
2|Jordan Lee|jordan.lee@example.com
3|Avery Patel|avery.patel@example.com

SELECT * FROM TUTORING_REQUESTS;
1|1|IS 6465|PHP arrays and forms|2026-11-03|14:00|Open|2026-11-09 02:21:47 //Request from Video Example
2|2|IS 6465|Forms|2026-11-09|14:00|Open|2026-11-09 02:21:47
3|2|IS 6465|SQL SELECT|2026-11-09|15:00|Open|2026-11-09 02:21:47
4|3|IS 6465|SQLite CRUD|2026-11-10|14:00|Open|2026-11-09 02:21:47

2.3)

Jordan has one student row because he is only one student, however, he has two request rows because he made multiple requests.

3)

SELECT * FROM STUDENTS;

SELECT * FROM TUTORING_REQUESTS;

SELECT * FROM TUTORING_REQUESTS WHERE status = 'Open';

SELECT * FROM TUTORING_REQUESTS WHERE request_id = 1;

select * from students;
 SELECT
  r.request_id,
  s.student_name,
  s.student_email,
  r.course, r.topic,
  r.preferred_date, r.preferred_time, r.status
FROM tutoring_requests AS r
JOIN students AS s ON r.student_id = s.student_id
ORDER BY r.preferred_date;

1|Maria Lopez|maria.lopez@example.com
2|Jordan Lee|jordan.lee@example.com
3|Avery Patel|avery.patel@example.com
1|Maria Lopez|maria.lopez@example.com|IS 6465|PHP arrays and forms|2026-11-03|14:00|Open
2|Jordan Lee|jordan.lee@example.com|IS 6465|Forms|2026-11-09|14:00|Open
3|Jordan Lee|jordan.lee@example.com|IS 6465|SQL SELECT|2026-11-09|15:00|Open
4|Avery Patel|avery.patel@example.com|IS 6465|SQLite CRUD|2026-11-10|14:00|Open

3.1)

The join condition connects the two tables by matching rows using the foreign key relationship between the student_id column in tutoring_requests and the student_id column in students.

3.2)

Join allows one to retrieve related information from two (or more) tables, while Where allows you to filter the rows based on conditions.

3.3)

Because multiple students might need tutoring for the same topic; request_id is guaranteed to be unique.

4.1)

UPDATE tutoring_requests
SET topic = 'PHP arrays and forms - updated'
WHERE request_id = 2;

UPDATE tutoring_requests
SET status = 'cancelled'
WHERE request_id = 4;

4.2)

<Before>
SELECT * FROM TUTORING_REQUESTS;
1|1|IS 6465|PHP arrays and forms|2026-11-03|14:00|Open|2026-11-09 02:21:47
2|2|IS 6465|Forms|2026-11-09|14:00|Open|2026-11-09 02:21:47
3|2|IS 6465|SQL SELECT|2026-11-09|15:00|Open|2026-11-09 02:21:47
4|3|IS 6465|SQLite CRUD|2026-11-10|14:00|Open|2026-11-09 02:21:47

<After>
SELECT * FROM TUTORING_REQUESTS;
1|1|IS 6465|PHP arrays and forms|2026-11-03|14:00|Open|2026-11-09 02:21:47
2|2|IS 6465|PHP arrays and forms - updated|2026-11-09|14:00|Open|2026-11-09 02:21:47
3|2|IS 6465|SQL SELECT|2026-11-09|15:00|Open|2026-11-09 02:21:47
4|3|IS 6465|SQLite CRUD|2026-11-10|14:00|cancelled|2026-11-09 02:21:47

4.3)

SELECT
  r.request_id,
  s.student_name,
  s.student_email,
  r.course, r.topic,
  r.preferred_date, r.preferred_time, r.status
FROM tutoring_requests AS r
JOIN students AS s ON r.student_id = s.student_id WHERE s.student_id = 3
ORDER BY r.preferred_date;

4|Avery Patel|avery.patel@example.com|IS 6465|SQLite CRUD|2026-11-10|14:00|cancelled

4.4) 

The "where" clause allows you to set a condition for which rows are being updated, so we aren't updating all of them for no reason.

4.5)

As a safety feature, to prevent completely removing any requests from the table.  That way, we can keep track of any previous requests and recreate them if need be.

5.1)

INSERT INTO TUTORING_REQUESTS (student_id, course, topic, preferred_date, preferred_time)VALUES (3, 'IS 6465', 'testing delete', '2026-11-10', '15:00');

5.2)
DELETE FROM TUTORING_REQUESTS WHERE request_id = 5;

5.3)

<Before>
select * from tutoring_requests;
1|1|IS 6465|PHP arrays and forms|2026-11-03|14:00|Open|2026-11-09 02:21:47
2|2|IS 6465|PHP arrays and forms - updated|2026-11-09|14:00|Open|2026-11-09 02:21:47
3|2|IS 6465|SQL SELECT|2026-11-09|15:00|Open|2026-11-09 02:21:47
4|3|IS 6465|SQLite CRUD|2026-11-10|14:00|cancelled|2026-11-09 02:21:47
5|3|IS 6465|testing delete|2026-11-10|15:00|Open|2026-11-09 02:45:24

<After>
select * from tutoring_requests;
1|1|IS 6465|PHP arrays and forms|2026-11-03|14:00|Open|2026-11-09 02:21:47
2|2|IS 6465|PHP arrays and forms - updated|2026-11-09|14:00|Open|2026-11-09 02:21:47
3|2|IS 6465|SQL SELECT|2026-11-09|15:00|Open|2026-11-09 02:21:47
4|3|IS 6465|SQLite CRUD|2026-11-10|14:00|cancelled|2026-11-09 02:21:47

5.4)

To set a condition to delete the row we intend to delete.

6)

CRUD ACTION         SQL statement used in this lab      Peer Tutoring Hub Example
Create              INSERT                              Creating a tutoring request
Read                SELECT                              Selecting a singular request to view
Update              UPDATE                              Updating a previous request
Delete              DELETE                              Deleting (cancelling) a request

6.1)

By using the foreign key connection between tutoring_requests student_id and the student_id column in students

6.2)

UNIQUE

6.3)

May accidentally delete information that wasn't meant to be deleted.

6.4)

Otherwise the application would be left vulnerable to SQL injection attacks.