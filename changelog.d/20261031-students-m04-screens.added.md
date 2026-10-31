Add the M04 Students screens.

- Students (`/students`): search, filters (department, programme, batch, section, status including former students, quota), add a student (programme, quota, batch, section, roll number, invite).
- Student page: academic and personal details, edit, batch/section/roll/term placement, enrollment status change with reason, parents/guardians editor, documents (upload, verify, reject), correction requests from the student (apply or reject), enrollment history. ID number masked unless you may edit.
- Correction requests (`/student-change-requests`): waiting, applied and rejected requests.
- My student record (`/my-record`, students): own details, contact edits, photo, document upload, correction requests with status.
- Imports now include students.
- Option lists (statuses, relations, ID types, blood groups, genders) come from `GET students/options`.
