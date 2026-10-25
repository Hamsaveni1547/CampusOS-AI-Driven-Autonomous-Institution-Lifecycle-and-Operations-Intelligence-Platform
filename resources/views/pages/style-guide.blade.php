{{-- Component catalogue: every UI component and its states. Local only (config campusos.style_guide). --}}
@php
    $departments = ['cse' => 'Computer Science and Engineering', 'ece' => 'Electronics and Communication', 'mech' => 'Mechanical Engineering', 'civil' => 'Civil Engineering', 'adm' => 'Administration'];
    $roles = [
        ['value' => 'faculty', 'label' => 'Faculty', 'description' => 'Teaching staff'],
        ['value' => 'hod', 'label' => 'Head of Department', 'description' => 'Runs one department'],
        ['value' => 'hr_officer', 'label' => 'HR Officer', 'description' => 'Staff records and leave'],
    ];
    $sampleRows = [
        ['name' => 'Dr. Anita Rao', 'email' => 'anita.rao@example.edu', 'code' => 'RVCEEMP0001', 'dept' => 'CSE', 'status' => 'active', 'joined' => '2019-07-15'],
        ['name' => 'Kiran Kumar', 'email' => 'kiran.k@example.edu', 'code' => 'RVCEEMP0002', 'dept' => 'ECE', 'status' => 'on_leave', 'joined' => '2021-01-04'],
        ['name' => 'Leela Menon', 'email' => 'leela.m@example.edu', 'code' => 'RVCEEMP0003', 'dept' => 'ADM', 'status' => 'invited', 'joined' => '2026-10-01'],
    ];
    $page = new \App\Api\Page(items: $sampleRows, currentPage: 3, lastPage: 12, perPage: 25, total: 289);
    $sections = ['tokens' => 'Colour tokens', 'buttons' => 'Buttons', 'badges' => 'Badges', 'forms' => 'Form controls', 'feedback' => 'Feedback', 'data' => 'Data display', 'table' => 'Table', 'overlays' => 'Overlays', 'navigation' => 'Navigation', 'layout' => 'Layout'];
@endphp
<x-layout.app :title="__('UI components')" :breadcrumbs="['Style guide' => null]">
    <x-ui.page-header title="CampusOS UI" description="Every component of the design system. Use these in pages; never style pages directly.">
        <x-slot:actions>
            <x-ui.button variant="secondary" icon="sun-moon" x-on:click="$store.theme.cycle()">Toggle theme</x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <nav class="mb-8 flex flex-wrap gap-2">
        @foreach ($sections as $id => $label)
            <a href="#{{ $id }}" class="rounded-full border border-line bg-surface px-3 py-1 text-xs font-medium text-ink-muted hover:text-ink">{{ $label }}</a>
        @endforeach
    </nav>

    <div class="space-y-12">
        {{-- Tokens --}}
        <section id="tokens" class="space-y-4">
            <h2 class="text-lg font-semibold">Colour tokens</h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6">
                @foreach ([
                    'canvas' => 'bg-canvas', 'surface' => 'bg-surface', 'surface-muted' => 'bg-surface-muted', 'line' => 'bg-line',
                    'ink' => 'bg-ink', 'ink-muted' => 'bg-ink-muted', 'primary' => 'bg-primary', 'primary-soft' => 'bg-primary-soft',
                    'success' => 'bg-success', 'warning' => 'bg-warning', 'danger' => 'bg-danger', 'info' => 'bg-info',
                ] as $token => $swatch)
                    <div class="overflow-hidden rounded-lg border border-line">
                        <div class="h-14 {{ $swatch }}"></div>
                        <p class="px-3 py-2 font-mono text-xs">{{ $token }}</p>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Buttons --}}
        <section id="buttons" class="space-y-4">
            <h2 class="text-lg font-semibold">Buttons</h2>
            <x-ui.card>
                <div class="space-y-4">
                    <div class="flex flex-wrap items-center gap-3">
                        <x-ui.button>Primary</x-ui.button>
                        <x-ui.button variant="secondary">Secondary</x-ui.button>
                        <x-ui.button variant="soft">Soft</x-ui.button>
                        <x-ui.button variant="ghost">Ghost</x-ui.button>
                        <x-ui.button variant="danger">Danger</x-ui.button>
                        <x-ui.button variant="link">Link</x-ui.button>
                        <x-ui.button disabled>Disabled</x-ui.button>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <x-ui.button size="sm" icon="plus">Small</x-ui.button>
                        <x-ui.button icon="plus">Medium</x-ui.button>
                        <x-ui.button size="lg" icon="plus">Large</x-ui.button>
                        <x-ui.button variant="secondary" icon-right="chevron-down">Actions</x-ui.button>
                        <x-ui.button variant="ghost" square icon="pencil" label="Edit" />
                        <x-ui.button variant="secondary" square icon="trash-2" label="Delete" />
                        <x-ui.button-group>
                            <x-ui.button variant="secondary" size="sm">Day</x-ui.button>
                            <x-ui.button variant="secondary" size="sm">Week</x-ui.button>
                            <x-ui.button variant="secondary" size="sm">Month</x-ui.button>
                        </x-ui.button-group>
                    </div>
                </div>
            </x-ui.card>
        </section>

        {{-- Badges --}}
        <section id="badges" class="space-y-4">
            <h2 class="text-lg font-semibold">Badges &amp; status</h2>
            <x-ui.card>
                <div class="flex flex-wrap gap-2">
                    @foreach (['neutral', 'primary', 'success', 'warning', 'danger', 'info', 'muted'] as $tone)
                        <x-ui.badge :tone="$tone">{{ ucfirst($tone) }}</x-ui.badge>
                    @endforeach
                </div>
                <div class="mt-4 flex flex-wrap gap-2">
                    @foreach (['active', 'invited', 'pending_verification', 'inactive', 'locked', 'planned', 'closed', 'on_leave', 'resigned', 'completed_with_errors', 'failed'] as $status)
                        <x-ui.status-badge :status="$status" />
                    @endforeach
                </div>
            </x-ui.card>
        </section>

        {{-- Forms --}}
        <section id="forms" class="space-y-4">
            <h2 class="text-lg font-semibold">Form controls</h2>
            <x-ui.card>
                <div class="grid gap-5 md:grid-cols-2">
                    <x-ui.input label="Full name" name="demo_name" placeholder="Dr. Anita Rao" required />
                    <x-ui.input label="Email" type="email" name="demo_email" icon="mail" hint="We send the invitation here." />
                    <x-ui.input label="Mobile" name="demo_mobile" prefix="+91" type="tel" optional />
                    <x-ui.input label="With error" name="demo_error" error="This email is already used by another account." value="anita@example.edu" />
                    <x-ui.password label="Password" name="demo_password" strength="10" autocomplete="new-password" />
                    <x-ui.select label="Programme level" name="demo_level" :options="['ug' => 'Undergraduate', 'pg' => 'Postgraduate', 'diploma' => 'Diploma']" placeholder="Choose a level" />
                    <x-ui.combobox label="Department" name="demo_department" :options="$departments" placeholder="Search departments" />
                    <x-ui.combobox label="Roles" name="demo_roles" :options="$roles" multiple :value="['faculty']" />
                    <x-ui.date-picker label="Joined on" name="demo_joined" />
                    <x-ui.input label="Disabled" name="demo_disabled" value="Read only value" disabled />
                    <x-ui.textarea label="Description" name="demo_description" maxlength="500" class="md:col-span-2">Department of Computer Science.</x-ui.textarea>
                    <x-ui.radio-group label="Staff type" name="demo_type" :options="['teaching' => 'Teaching', 'non_teaching' => 'Non-teaching']" inline />
                    <x-ui.radio-group label="Employment" name="demo_employment" cards :options="[
                        'permanent' => ['label' => 'Permanent', 'description' => 'On the regular payroll'],
                        'contract' => ['label' => 'Contract', 'description' => 'Fixed-term agreement'],
                    ]" />
                    <div class="space-y-4">
                        <x-ui.checkbox name="demo_remember" label="Remember this device" description="Skip two-step verification here for 30 days." />
                        <x-ui.switch name="demo_switch" label="Alert on new devices" description="Email users when their account signs in from a new device." checked />
                    </div>
                    <x-ui.otp-input name="demo_code" label="Verification code" :autofocus="false" />
                    <x-ui.file-upload label="Staff list (CSV)" name="demo_file" accept=".csv" hint="CSV up to 10 MB" class="md:col-span-2" />
                </div>
            </x-ui.card>
        </section>

        {{-- Feedback --}}
        <section id="feedback" class="space-y-4">
            <h2 class="text-lg font-semibold">Feedback</h2>
            <div class="grid gap-4 lg:grid-cols-2">
                <x-ui.alert tone="info" title="Heads up">The odd semester starts on 1 August.</x-ui.alert>
                <x-ui.alert tone="success" title="Saved">The department was updated.</x-ui.alert>
                <x-ui.alert tone="warning" title="Two-step verification is off" dismissible>Turn it on to protect your account.
                    <x-slot:actions><x-ui.button size="sm" variant="secondary">Turn on</x-ui.button></x-slot:actions>
                </x-ui.alert>
                <x-ui.alert tone="danger" title="Could not save">This code is already in use.</x-ui.alert>
            </div>
            <x-ui.card>
                <div class="flex flex-wrap items-center gap-3">
                    @foreach (['success' => 'Department saved', 'error' => 'Could not delete', 'warning' => 'Contract ends soon', 'info' => 'Import started'] as $type => $title)
                        <x-ui.button variant="secondary" size="sm" x-on:click="$store.toasts.push({ type: '{{ $type }}', title: '{{ $title }}', message: 'Example toast message.' })">Toast: {{ $type }}</x-ui.button>
                    @endforeach
                    <x-ui.spinner label="Loading" class="size-5 text-primary" />
                </div>
                <div class="mt-5 grid gap-5 md:grid-cols-2">
                    <x-ui.progress :value="64" label="Import progress" show-value />
                    <x-ui.skeleton lines="3" />
                </div>
            </x-ui.card>
            <div class="grid gap-4 lg:grid-cols-2">
                <x-ui.card :padding="false">
                    <x-ui.empty-state icon="building-2" title="No departments yet" description="Add the departments of your institution to get started.">
                        <x-ui.button icon="plus">Add department</x-ui.button>
                    </x-ui.empty-state>
                </x-ui.card>
                <x-ui.card :padding="false">
                    <x-ui.error-state title="Couldn't load staff" trace-id="9f1c2d3e-4b5a-6789-0abc-def123456789">
                        <x-ui.button variant="secondary" icon="refresh-cw">Try again</x-ui.button>
                    </x-ui.error-state>
                </x-ui.card>
            </div>
        </section>

        {{-- Data display --}}
        <section id="data" class="space-y-4">
            <h2 class="text-lg font-semibold">Data display</h2>
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <x-ui.stat label="Students" :value="4218" icon="graduation-cap" hint="2026-27" />
                <x-ui.stat label="Active staff" :value="312" icon="briefcase" tone="success" />
                <x-ui.stat label="Pending invites" :value="7" icon="mail" tone="warning" />
                <x-ui.stat label="Departments" :value="12" icon="building-2" tone="info" />
            </div>
            <div class="grid gap-4 lg:grid-cols-2">
                <x-ui.card title="Profile">
                    <div class="mb-5 flex items-center gap-3">
                        <x-ui.avatar name="Anita Rao" size="lg" />
                        <div>
                            <p class="font-medium text-ink">Dr. Anita Rao</p>
                            <p class="text-sm text-ink-muted">Professor · Computer Science</p>
                        </div>
                    </div>
                    <x-ui.description-list :items="['Employee code' => 'RVCEEMP0001', 'Joined' => '15 Jul 2019', 'Email' => 'anita.rao@example.edu', 'Mobile' => '+91 98765 43210']" />
                    <div class="mt-4 text-sm">ID: <x-ui.code value="01JBX6Q3K8Y2ZP5M7N9QRSTUVW" /></div>
                </x-ui.card>
                <x-ui.card title="Recent activity">
                    <x-ui.timeline :items="[
                        ['title' => 'Signed in', 'description' => 'Chrome on Windows · 10.0.0.4', 'time' => now()->subMinutes(5)->toIso8601String(), 'icon' => 'log-in', 'tone' => 'success'],
                        ['title' => 'Two-step verification turned on', 'description' => 'Authenticator app', 'time' => now()->subHours(3)->toIso8601String(), 'icon' => 'shield-check', 'tone' => 'primary'],
                        ['title' => 'Password changed', 'time' => now()->subDays(2)->toIso8601String(), 'icon' => 'key-round', 'tone' => 'warning'],
                    ]" />
                </x-ui.card>
            </div>
        </section>

        {{-- Table --}}
        <section id="table" class="space-y-4">
            <h2 class="text-lg font-semibold">Table</h2>
            <x-ui.filters search="search" :active="['Status: Active' => 'status', 'Department: CSE' => 'department']">
                <x-ui.select name="demo_filter_status" :options="['active' => 'Active', 'on_leave' => 'On leave']" placeholder="All statuses" class="sm:w-44" />
                <x-slot:actions>
                    <x-ui.button variant="secondary" icon="download">Export</x-ui.button>
                    <x-ui.button icon="plus">Add staff</x-ui.button>
                </x-slot:actions>
            </x-ui.filters>
            <x-ui.table>
                <x-slot:head>
                    <x-ui.th sortable="name" sort="name">Name</x-ui.th>
                    <x-ui.th sortable="code" sort="name">Code</x-ui.th>
                    <x-ui.th>Department</x-ui.th>
                    <x-ui.th>Status</x-ui.th>
                    <x-ui.th sortable="joined_on" sort="name">Joined</x-ui.th>
                    <x-ui.th align="right"><span class="sr-only">Actions</span></x-ui.th>
                </x-slot:head>
                @foreach ($sampleRows as $row)
                    <x-ui.tr href="#table">
                        <x-ui.td primary>
                            <div class="flex items-center gap-3">
                                <x-ui.avatar :name="$row['name']" size="sm" />
                                <div class="min-w-0">
                                    <p class="truncate font-medium text-ink">{{ $row['name'] }}</p>
                                    <p class="truncate text-xs text-ink-muted">{{ $row['email'] }}</p>
                                </div>
                            </div>
                        </x-ui.td>
                        <x-ui.td label="Code" class="tabular">{{ $row['code'] }}</x-ui.td>
                        <x-ui.td label="Department">{{ $row['dept'] }}</x-ui.td>
                        <x-ui.td label="Status"><x-ui.status-badge :status="$row['status']" /></x-ui.td>
                        <x-ui.td label="Joined">{{ \App\Support\Format::date($row['joined']) }}</x-ui.td>
                        <x-ui.td align="right">
                            <x-ui.dropdown>
                                <x-slot:trigger><x-ui.button variant="ghost" size="sm" square icon="ellipsis" label="Row actions" /></x-slot:trigger>
                                <x-ui.dropdown-item icon="eye">View</x-ui.dropdown-item>
                                <x-ui.dropdown-item icon="pencil">Edit</x-ui.dropdown-item>
                                <x-ui.dropdown-item icon="user-x" tone="danger">Record exit</x-ui.dropdown-item>
                            </x-ui.dropdown>
                        </x-ui.td>
                    </x-ui.tr>
                @endforeach
                <x-slot:footer><x-ui.pagination :page="$page" /></x-slot:footer>
            </x-ui.table>
            <x-ui.table :empty="true">
                <x-slot:head><x-ui.th>Name</x-ui.th><x-ui.th>Status</x-ui.th></x-slot:head>
                <x-slot:emptyState>
                    <x-ui.empty-state compact icon="search" title="No staff match these filters" description="Try a different search or clear the filters." />
                </x-slot:emptyState>
            </x-ui.table>
            <x-ui.card :padding="false"><x-ui.skeleton type="table" rows="3" /></x-ui.card>
        </section>

        {{-- Overlays --}}
        <section id="overlays" class="space-y-4">
            <h2 class="text-lg font-semibold">Overlays</h2>
            <x-ui.card>
                <div class="flex flex-wrap items-center gap-3">
                    <x-ui.button variant="secondary" x-on:click="$dispatch('open-dialog', 'demo-modal')">Open modal</x-ui.button>
                    <x-ui.button variant="secondary" x-on:click="$dispatch('open-dialog', 'demo-drawer')">Open drawer</x-ui.button>
                    <x-ui.button variant="danger" x-on:click="$dispatch('open-dialog', 'demo-confirm')">Confirm dialog</x-ui.button>
                    <x-ui.tooltip text="Copies the record id"><x-ui.button variant="ghost" square icon="copy" label="Copy id" /></x-ui.tooltip>
                    <x-ui.popover>
                        <x-slot:trigger><x-ui.button variant="secondary" icon="filter">Popover</x-ui.button></x-slot:trigger>
                        <p class="text-sm text-ink">Any content, e.g. extra filters.</p>
                    </x-ui.popover>
                    <x-ui.dropdown align="left">
                        <x-slot:trigger><x-ui.button variant="secondary" icon-right="chevron-down">Dropdown</x-ui.button></x-slot:trigger>
                        <x-ui.dropdown-item icon="pencil" shortcut="E">Edit</x-ui.dropdown-item>
                        <x-ui.dropdown-item icon="copy">Duplicate</x-ui.dropdown-item>
                        <x-ui.dropdown-item icon="trash-2" tone="danger">Delete</x-ui.dropdown-item>
                    </x-ui.dropdown>
                </div>
            </x-ui.card>

            <x-ui.modal name="demo-modal" title="Edit room" description="Room details used by timetables and exams.">
                <div class="space-y-4">
                    <x-ui.input label="Code" name="demo_room_code" value="LH-101" />
                    <x-ui.input label="Capacity" name="demo_room_capacity" type="number" value="120" />
                </div>
                <x-slot:footer>
                    <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'demo-modal')">Cancel</x-ui.button>
                    <x-ui.button>Save changes</x-ui.button>
                </x-slot:footer>
            </x-ui.modal>

            <x-ui.drawer name="demo-drawer" title="Add department" description="Academic or administrative unit.">
                <x-ui.input label="Code" name="demo_dept_code" placeholder="CSE" />
                <x-ui.input label="Name" name="demo_dept_name" placeholder="Computer Science and Engineering" />
                <x-ui.select label="Type" name="demo_dept_type" :options="['academic' => 'Academic', 'administrative' => 'Administrative']" />
                <x-slot:footer>
                    <x-ui.button variant="secondary" x-on:click="$dispatch('close-dialog', 'demo-drawer')">Cancel</x-ui.button>
                    <x-ui.button>Create</x-ui.button>
                </x-slot:footer>
            </x-ui.drawer>

            <x-ui.confirm-dialog name="demo-confirm" title="Deactivate Anita Rao?" confirm-label="Deactivate" confirm-text="Anita Rao">
                She will be signed out everywhere and cannot sign in until re-activated. Her records stay.
            </x-ui.confirm-dialog>
        </section>

        {{-- Navigation --}}
        <section id="navigation" class="space-y-4">
            <h2 class="text-lg font-semibold">Navigation</h2>
            <x-ui.card>
                <div class="space-y-6">
                    <x-ui.breadcrumbs :items="['People' => '#', 'Staff' => '#', 'Dr. Anita Rao' => null]" />
                    <x-ui.stepper :steps="['Account', 'Employment', 'Qualifications', 'Review']" :current="2" />
                    <x-ui.tabs :tabs="['overview' => 'Overview', 'qualifications' => ['label' => 'Qualifications', 'count' => 3], 'activity' => 'Activity']">
                        <x-ui.tab-panel name="overview"><p class="text-sm text-ink-muted">Overview panel.</p></x-ui.tab-panel>
                        <x-ui.tab-panel name="qualifications"><p class="text-sm text-ink-muted">Qualifications panel.</p></x-ui.tab-panel>
                        <x-ui.tab-panel name="activity"><p class="text-sm text-ink-muted">Activity panel.</p></x-ui.tab-panel>
                    </x-ui.tabs>
                    <p class="text-sm text-ink-muted">Press <kbd class="rounded border border-line px-1.5 text-xs">Ctrl K</kbd> for the command palette.</p>
                </div>
            </x-ui.card>
        </section>

        {{-- Layout --}}
        <section id="layout" class="space-y-4">
            <h2 class="text-lg font-semibold">Layout</h2>
            <x-ui.card title="Card with header" description="Optional description." >
                <x-slot:actions><x-ui.button size="sm" variant="secondary">Action</x-ui.button></x-slot:actions>
                <x-ui.section title="Password rules" description="Applies to every account in the institution.">
                    <x-ui.input label="Minimum length" name="demo_min" type="number" value="10" />
                </x-ui.section>
                <x-ui.divider />
                <x-ui.section title="Sign-in" description="Lockout after failed attempts.">
                    <x-ui.switch name="demo_lockout" label="Lock accounts after 5 failed attempts" checked />
                </x-ui.section>
                <x-slot:footer>
                    <x-ui.button variant="secondary">Cancel</x-ui.button>
                    <x-ui.button>Save</x-ui.button>
                </x-slot:footer>
            </x-ui.card>
            <x-ui.divider>or</x-ui.divider>
        </section>
    </div>
</x-layout.app>
