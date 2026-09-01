{{--
    Recursive sidebar menu using the .nm / .nm-link / .nm-children
    class convention. Renders ul > li > ul > li > ... to whatever
    depth "children" goes.

    The SAME .nm-children element serves two purposes depending on
    sidebar state, matching the CSS's design:
      - Expanded sidebar: a normal Bootstrap-collapse accordion
        (data-bs-toggle on the link, .collapse on this ul).
      - Collapsed sidebar (desktop): sidebar.js temporarily moves
        this exact element to <body> as a hover flyout (the "portal"
        technique) rather than rendering separate flyout markup.

    Props:
      items     - array of ['label','icon'?,'route'?,'params'?,'children'?]
      level     - current nesting depth (1 = top level)
      id        - this list's element id (set by the parent link's
                  aria-controls / data-bs-target; only used for level > 1)
      startOpen - pre-expand this list because it contains the
                  currently active page (keeps the active branch
                  visible on load instead of the user having to find it)
--}}
@props(['items' => [], 'level' => 1, 'id' => null, 'startOpen' => false, 'defaultParams' => []])

@once
@php
if (! function_exists('acasMenuItemIsActive')) {
function acasMenuItemIsActive(array $item): bool
{
if (! empty($item['route'])
&& \Illuminate\Support\Facades\Route::has($item['route'])
&& request()->routeIs($item['route'])) {
return true;
}

foreach ($item['children'] ?? [] as $child) {
if (acasMenuItemIsActive($child)) {
return true;
}
}

return false;
}
}
@endphp
@endonce

<ul class="nm {{ $level > 1 ? 'collapse' : '' }}{{ $startOpen ? ' show' : '' }}"
    data-level="{{ $level }}"
    @if ($level> 1) id="{{ $id }}" @endif>
    @foreach ($items as $index => $item)
    @php
    $hasChildren = ! empty($item['children']);
    $childId = ($id ?? 'menu').'-'.$level.'-'.$index;
    $hasRoute = ! empty($item['route']) && \Illuminate\Support\Facades\Route::has($item['route']);

    // Only merge in the defaultParams keys the target route
    // actually declares (e.g. 'company') — blindly merging all
    // of them broke any menu item pointing at a parameter-less
    // route like super-admin.dashboard, which doesn't accept a
    // 'company' value and got it tacked on as a stray query
    // string instead.
    $routeParams = [];
    if ($hasRoute) {
    $routeDef = \Illuminate\Support\Facades\Route::getRoutes()->getByName($item['route']);
    $neededKeys = $routeDef ? $routeDef->parameterNames() : [];
    $routeParams = array_merge(
    array_intersect_key($defaultParams, array_flip($neededKeys)),
    $item['params'] ?? []
    );
    }
    $href = $hasRoute ? route($item['route'], $routeParams) : '#';
    $isActive = $hasRoute && request()->routeIs($item['route']);
    $isActiveParent = ! $isActive && $hasChildren
    && collect($item['children'])->contains(fn ($child) => acasMenuItemIsActive($child));
    @endphp
    <li class="{{ $hasChildren ? 'has-children' : '' }}">
        <a
            href="{{ $hasChildren ? '#'.$childId : $href }}"
            class="nm-link{{ $isActive ? ' active' : '' }}{{ $isActiveParent ? ' active-parent' : '' }}"
            @if ($hasChildren)
            data-bs-toggle="collapse" role="button"
            aria-expanded="{{ $isActiveParent ? 'true' : 'false' }}"
            aria-controls="{{ $childId }}"
            @elseif (! $hasRoute)
            data-coming-soon="1"
            @endif>
            @if (! empty($item['icon']))
            <i class="bi {{ $item['icon'] }} nm-icon"></i>
            @endif
            <span class="nm-title">{{ $item['label'] }}</span>
            @if ($hasChildren)
            <i class="bi bi-chevron-right nm-caret"></i>
            @endif
        </a>

        @if ($hasChildren)
        <x-sidebar-menu
            :items="$item['children']"
            :level="$level + 1"
            :id="$childId"
            :start-open="$isActiveParent"
            :default-params="$defaultParams" />
        @endif
    </li>
    @endforeach
</ul>