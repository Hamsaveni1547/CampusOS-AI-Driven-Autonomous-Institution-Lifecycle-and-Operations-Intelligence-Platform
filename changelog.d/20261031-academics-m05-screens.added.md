Add the M05 Curriculum & teaching screens.

- Courses (`/courses`): catalogue with filters, add/edit (credits, L-T-P hours, type, regulation); course page with units and topics and a syllabus editor (one topic per line), textbooks.
- Curricula (`/curricula`): versions per programme and regulation, new version as a copy, courses grouped by term with credits, add/remove courses, send for approval, approve or return with remarks, and a shortcut to offer an approved curriculum in a term.
- Course offerings (`/course-offerings`): per term, offer a course to sections, offer a whole curriculum term at once, allocate teachers (theory/lab/tutorial, hours) with a workload warning, remove teachers, syllabus progress per class, filter classes without a teacher.
- Faculty workload (`/faculty-workload`): hours per teacher with over-limit flags.
- My classes (`/my-classes`): teachers see their classes and progress; students see their courses and teachers.
- Class page (`/classes/{id}`): teachers tick topics as taught and share files or links (visible or hidden); students see progress and published materials.
- Option lists come from `GET academics/options`.
