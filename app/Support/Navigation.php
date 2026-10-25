<?php

namespace App\Support;

use App\Auth\CurrentUser;
use Illuminate\Support\Facades\Route;

/**
 * Builds the sidebar and command palette from config/navigation.php for the current user.
 * Items need a defined route and must pass the role/permission/platform checks.
 */
class Navigation
{
    /**
     * @return array<int, array{label: string, items: array<int, array{label: string, url: string, icon: string, active: bool, keywords: string}>}>
     */
    public function sections(?CurrentUser $user): array
    {
        if ($user === null) {
            return [];
        }

        $sections = [];

        foreach ((array) config('navigation') as $section) {
            if (($section['platform'] ?? false) && ! $user->isPlatformAdmin()) {
                continue;
            }

            $items = [];

            foreach ($section['items'] as $item) {
                if (! Route::has($item['route']) || ! $this->visible($item, $user)) {
                    continue;
                }

                $items[] = [
                    'label' => __($item['label']),
                    'url' => route($item['route']),
                    'icon' => $item['icon'],
                    'active' => $this->isActive($item['route']),
                    'keywords' => (string) ($item['keywords'] ?? ''),
                ];
            }

            if ($items !== []) {
                $sections[] = ['label' => __($section['label']), 'items' => $items];
            }
        }

        return $sections;
    }

    /**
     * Flat list for the command palette.
     *
     * @return array<int, array{label: string, section: string, url: string, keywords: string}>
     */
    public function paletteItems(?CurrentUser $user): array
    {
        $items = [];

        foreach ($this->sections($user) as $section) {
            foreach ($section['items'] as $item) {
                $items[] = ['label' => $item['label'], 'section' => $section['label'], 'url' => $item['url'], 'keywords' => $item['keywords']];
            }
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function visible(array $item, CurrentUser $user): bool
    {
        if ($item['platform'] ?? false) {
            return $user->isPlatformAdmin();
        }

        $roles = (array) ($item['roles'] ?? []);
        $permissions = (array) ($item['permissions'] ?? []);

        if ($roles === [] && $permissions === []) {
            return true;
        }

        return ($roles !== [] && $user->hasAnyRole($roles)) || ($permissions !== [] && $user->canAny($permissions));
    }

    private function isActive(string $routeName): bool
    {
        $prefix = str_contains($routeName, '.') ? substr($routeName, 0, (int) strrpos($routeName, '.')) : $routeName;

        return request()->routeIs($routeName) || ($prefix !== $routeName && request()->routeIs($prefix.'.*'));
    }
}
