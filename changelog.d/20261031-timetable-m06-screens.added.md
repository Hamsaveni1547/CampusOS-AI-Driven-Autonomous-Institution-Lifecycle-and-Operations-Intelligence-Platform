Add the M06 Timetable screens.

- Periods (`/periods`): bell-schedule templates with periods, breaks and working days.
- Timetables (`/timetables`): one per section per term; builder grid (days × periods) where clicking a cell places a course with its allocated teacher, room and part; clashes are refused with a clear message; periods-per-week vs allocated hours; publish, or take back to draft to edit.
- Timetable view (`/timetable-view`): published week by section, teacher or room, and any day with its changes; staff cancel a class, assign a substitute, add an extra class, or undo a change.
- My timetable (`/my-timetable`): students and teachers see the day (with changes) and the week.
- Day names come from the locale (Carbon), not hard-coded.
