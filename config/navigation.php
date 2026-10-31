<?php

/*
| Sidebar and command palette. Each item:
|   label, route (named route), icon (any Lucide name), and optional:
|   permissions (any of), platform (platform admins only), roles (any of), keywords (palette search).
| Items whose route does not exist yet are hidden automatically, so future screens
| can be listed here before they are built.
*/

return [

    [
        'label' => 'Overview',
        'items' => [
            ['label' => 'Home', 'route' => 'home', 'icon' => 'house'],
            ['label' => 'Notifications', 'route' => 'notifications.index', 'icon' => 'bell'],
            ['label' => 'Calendar', 'route' => 'calendar.index', 'icon' => 'calendar-days', 'keywords' => 'holidays events'],
        ],
    ],

    [
        'label' => 'Academics',
        'items' => [
            ['label' => 'Departments', 'route' => 'departments.index', 'icon' => 'building-2', 'roles' => ['institution_admin', 'institution_head', 'hod', 'faculty', 'admin_staff', 'hr_officer', 'examiner'], 'permissions' => ['institution.departments.manage']],
            ['label' => 'Programmes', 'route' => 'programmes.index', 'icon' => 'graduation-cap', 'roles' => ['institution_admin', 'institution_head', 'hod', 'faculty', 'admin_staff', 'examiner'], 'permissions' => ['institution.programmes.manage']],
            ['label' => 'Batches & sections', 'route' => 'batches.index', 'icon' => 'layers', 'roles' => ['institution_admin', 'institution_head', 'hod', 'faculty', 'admin_staff'], 'permissions' => ['institution.batches.manage']],
            ['label' => 'My classes', 'route' => 'my-classes.index', 'icon' => 'book-open', 'roles' => ['faculty', 'hod', 'student'], 'keywords' => 'teaching courses syllabus materials'],
            ['label' => 'Term registration', 'route' => 'my-registration.show', 'icon' => 'clipboard-check', 'roles' => ['student'], 'keywords' => 'register electives semester'],
            ['label' => 'Term registrations', 'route' => 'term-registrations.index', 'icon' => 'clipboard-check', 'permissions' => ['academics.registrations.approve'], 'keywords' => 'approve registration electives'],
            ['label' => 'Courses', 'route' => 'courses.index', 'icon' => 'library', 'permissions' => ['academics.catalogue.view'], 'keywords' => 'subjects syllabus'],
            ['label' => 'Curricula', 'route' => 'curricula.index', 'icon' => 'scroll-text', 'permissions' => ['academics.catalogue.view'], 'keywords' => 'scheme regulation'],
            ['label' => 'Course offerings', 'route' => 'course-offerings.index', 'icon' => 'clipboard-list', 'permissions' => ['academics.catalogue.view'], 'keywords' => 'allocation faculty workload'],
            ['label' => 'Academic years', 'route' => 'academic-years.index', 'icon' => 'calendar-check', 'permissions' => ['institution.calendar.manage'], 'keywords' => 'terms semesters'],
        ],
    ],

    [
        'label' => 'Admissions',
        'items' => [
            ['label' => 'My applications', 'route' => 'my-applications.index', 'icon' => 'file-pen', 'roles' => ['applicant'], 'keywords' => 'apply admission'],
            ['label' => 'Applications', 'route' => 'applications.index', 'icon' => 'inbox', 'permissions' => ['admissions.applications.view'], 'keywords' => 'admissions desk verification'],
            ['label' => 'Admission cycles', 'route' => 'admission-cycles.index', 'icon' => 'calendar-range', 'permissions' => ['admissions.cycles.manage'], 'keywords' => 'seats quota intake'],
        ],
    ],

    [
        'label' => 'Students',
        'items' => [
            ['label' => 'My student record', 'route' => 'my-record.show', 'icon' => 'id-card', 'roles' => ['student'], 'keywords' => 'profile register number documents'],
            ['label' => 'Students', 'route' => 'students.index', 'icon' => 'graduation-cap', 'permissions' => ['students.records.view'], 'keywords' => 'usn register roll section'],
            ['label' => 'Correction requests', 'route' => 'student-change-requests.index', 'icon' => 'file-pen', 'permissions' => ['students.changes.approve'], 'keywords' => 'profile changes'],
        ],
    ],

    [
        'label' => 'People',
        'items' => [
            ['label' => 'Staff', 'route' => 'staff.index', 'icon' => 'briefcase', 'permissions' => ['hr.employees.view'], 'keywords' => 'employees faculty'],
            ['label' => 'Designations', 'route' => 'designations.index', 'icon' => 'award', 'permissions' => ['hr.designations.manage']],
            ['label' => 'Users', 'route' => 'users.index', 'icon' => 'users', 'permissions' => ['identity.users.view'], 'keywords' => 'accounts'],
            ['label' => 'Roles & permissions', 'route' => 'roles.index', 'icon' => 'shield', 'permissions' => ['identity.roles.view']],
        ],
    ],

    [
        'label' => 'Campus',
        'items' => [
            ['label' => 'Rooms', 'route' => 'rooms.index', 'icon' => 'door-open', 'roles' => ['institution_admin', 'institution_head', 'hod', 'admin_staff'], 'permissions' => ['institution.rooms.manage'], 'keywords' => 'labs halls'],
        ],
    ],

    [
        'label' => 'Administration',
        'items' => [
            ['label' => 'Institution profile', 'route' => 'institution.edit', 'icon' => 'school', 'permissions' => ['institution.profile.manage']],
            ['label' => 'Settings', 'route' => 'settings.index', 'icon' => 'settings', 'permissions' => ['admin.settings.view']],
            ['label' => 'Notification templates', 'route' => 'templates.index', 'icon' => 'mail', 'permissions' => ['communication.templates.manage']],
            ['label' => 'Audit log', 'route' => 'audit.index', 'icon' => 'scroll-text', 'permissions' => ['admin.audit.view', 'identity.security_events.view']],
            ['label' => 'Imports', 'route' => 'imports.index', 'icon' => 'file-up', 'permissions' => ['identity.users.import', 'hr.employees.import', 'students.records.import']],
            ['label' => 'System health', 'route' => 'health.index', 'icon' => 'activity', 'permissions' => ['admin.monitoring.view']],
        ],
    ],

    [
        'label' => 'Platform',
        'platform' => true,
        'items' => [
            ['label' => 'Institutions', 'route' => 'platform.institutions.index', 'icon' => 'building', 'platform' => true],
            ['label' => 'Platform settings', 'route' => 'platform.settings.index', 'icon' => 'settings-2', 'platform' => true],
            ['label' => 'Monitoring', 'route' => 'platform.monitoring.index', 'icon' => 'gauge', 'platform' => true, 'keywords' => 'queues failed jobs'],
        ],
    ],

];
