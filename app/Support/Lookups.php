<?php

namespace App\Support;

use App\Api\CampusApi;

/**
 * Option lists for selects/comboboxes, fetched once per request.
 */
class Lookups
{
    public function __construct(private CampusApi $api) {}

    /**
     * @return array<string, string> id => "CODE · Name"
     */
    public function departments(bool $activeOnly = true): array
    {
        return collect($this->api->endpoint('departments')->all($activeOnly ? ['status' => 'active'] : []))
            ->mapWithKeys(fn (array $d) => [$d['id'] => $d['code'].' · '.$d['name']])->all();
    }

    /**
     * @return array<string, string>
     */
    public function programmes(bool $activeOnly = true): array
    {
        return collect($this->api->endpoint('programmes')->all($activeOnly ? ['status' => 'active'] : []))
            ->mapWithKeys(fn (array $p) => [$p['id'] => $p['code'].' · '.$p['name']])->all();
    }

    /**
     * @return array<int, array{value: string, label: string, description: string|null}>
     */
    public function roles(): array
    {
        return collect($this->api->endpoint('roles')->all())
            ->filter(fn (array $r) => $r['is_active'])
            ->map(fn (array $r) => ['value' => $r['id'], 'label' => $r['display_name'], 'description' => $r['is_system'] ? __('Built-in') : __('Custom')])
            ->values()->all();
    }

    /**
     * Role name => label (used by settings such as MFA-required roles).
     *
     * @return array<string, string>
     */
    public function roleNames(): array
    {
        return collect($this->api->endpoint('roles')->all())->mapWithKeys(fn (array $r) => [$r['name'] => $r['display_name']])->all();
    }

    /**
     * @return array<string, string>
     */
    public function designations(?string $category = null): array
    {
        return collect($this->api->endpoint('designations')->all(array_filter(['status' => 'active', 'category' => $category])))
            ->mapWithKeys(fn (array $d) => [$d['id'] => $d['name']])->all();
    }

    /**
     * Active staff as user options (e.g. HOD, class advisor).
     *
     * @param  array<string, mixed>  $filters
     * @return array<int, array{value: string, label: string, description: string|null}>
     */
    public function staffUsers(array $filters = []): array
    {
        return collect($this->api->endpoint('employees')->page(array_merge(['status' => 'active'], $filters), ['per_page' => 100])->items)
            ->map(fn (array $e) => ['value' => $e['user_id'], 'label' => $e['name'], 'description' => trim(($e['designation']['name'] ?? '').' · '.$e['employee_code'], ' ·')])
            ->values()->all();
    }

    /**
     * Staff as employee options (reporting manager).
     *
     * @return array<int, array{value: string, label: string, description: string|null}>
     */
    public function staff(): array
    {
        return collect($this->api->endpoint('employees')->page(['status' => 'active'], ['per_page' => 100])->items)
            ->map(fn (array $e) => ['value' => $e['id'], 'label' => $e['name'], 'description' => $e['employee_code']])
            ->values()->all();
    }

    /**
     * Admission option lists from the API as value => label: quotas, statuses, categories.
     *
     * @return array<string, string>
     */
    public function admissionOptions(string $list): array
    {
        $options = (array) $this->api->get('admissions/options', memoize: true)->data($list, []);

        return collect($options)->mapWithKeys(fn (array $option) => [$option['value'] => $option['label']])->all();
    }

    /**
     * Student option lists from the API as value => label: statuses, guardian_relations, id_proof_types, blood_groups, genders.
     *
     * @return array<string, string>
     */
    public function studentOptions(string $list): array
    {
        $options = (array) $this->api->get('students/options', memoize: true)->data($list, []);

        return collect($options)->mapWithKeys(fn (array $option) => [$option['value'] => $option['label']])->all();
    }

    /**
     * Batches (with their sections) as id => name, optionally for one programme.
     *
     * @return array<string, string>
     */
    public function batches(?string $programmeId = null): array
    {
        return collect($this->api->endpoint('batches')->all(array_filter(['programme' => $programmeId])))
            ->mapWithKeys(fn (array $batch) => [$batch['id'] => $batch['name']])->all();
    }

    /**
     * Sections of one batch as id => name.
     *
     * @return array<string, string>
     */
    public function sections(?string $batchId): array
    {
        if ($batchId === null || $batchId === '') {
            return [];
        }

        $batch = collect($this->api->endpoint('batches')->all())->firstWhere('id', $batchId);

        return collect($batch['sections'] ?? [])->mapWithKeys(fn (array $section) => [$section['id'] => $section['name']])->all();
    }

    /**
     * Academics option lists from the API as value => label: course_types, components, curriculum_statuses, categories.
     *
     * @return array<string, string>
     */
    public function academicsOptions(string $list): array
    {
        $options = (array) $this->api->get('academics/options', memoize: true)->data($list, []);

        return collect($options)->mapWithKeys(fn (array $option) => [$option['value'] => $option['label']])->all();
    }

    /**
     * Active courses as id => "CODE · Title".
     *
     * @return array<string, string>
     */
    public function courses(): array
    {
        return collect($this->api->endpoint('courses')->page(['status' => 'active'], ['per_page' => 200])->items)
            ->mapWithKeys(fn (array $course) => [$course['id'] => $course['code'].' · '.$course['title']])->all();
    }

    /**
     * Terms as id => "Name (year)", current first.
     *
     * @return array<string, string>
     */
    public function terms(): array
    {
        return collect($this->api->endpoint('terms')->all())
            ->sortByDesc(fn (array $term) => [$term['is_current'], $term['starts_on']])
            ->mapWithKeys(fn (array $term) => [$term['id'] => $term['name'].($term['is_current'] ? ' · '.__('current') : '')])->all();
    }

    /**
     * Active teaching staff as employee options.
     *
     * @return array<int, array{value: string, label: string, description: string|null}>
     */
    public function teachers(): array
    {
        return collect($this->api->endpoint('employees')->page(['status' => 'active', 'staff_type' => 'teaching'], ['per_page' => 200])->items)
            ->map(fn (array $e) => ['value' => $e['id'], 'label' => $e['name'], 'description' => trim(($e['department']['name'] ?? '').' · '.$e['employee_code'], ' ·')])
            ->values()->all();
    }

    /**
     * Academic years as id => name (newest first).
     *
     * @return array<string, string>
     */
    public function academicYears(): array
    {
        return collect($this->api->endpoint('academic-years')->all())->mapWithKeys(fn (array $year) => [$year['id'] => $year['name']])->all();
    }
}
