{{--
    Renders the contents of a collapsed-sidebar hover flyout: the
    full subtree of one top-level item, flattened into a single
    panel with indentation rather than further nested flyouts.

    Deliberately NOT chaining flyout-off-a-flyout — that pattern
    loses :hover the instant the cursor crosses a pixel gap between
    panels, which is exactly the "mouseover does nothing" problem
    this replaces. One panel, everything reachable in it, no gaps
    to lose the mouse over.
--}}
@props(['items' => [], 'level' => 1])

@foreach ($items as $item)
    @php
        $hasChildren = ! empty($item['children']);
        $hasRoute = ! empty($item['route']) && \Illuminate\Support\Facades\Route::has($item['route']);
        $href = $hasRoute ? route($item['route'], $item['params'] ?? []) : '#';
    @endphp

    @if ($hasChildren)
        {{-- A parent is a header here, not a link — its children below
             are the actual navigation targets, same as in the
             expanded-sidebar accordion. --}}
        <div class="sidebar-flyout__subtitle" data-level="{{ $level }}">{{ $item['label'] }}</div>
        <div class="sidebar-flyout__group" data-level="{{ $level + 1 }}">
            <x-sidebar-flyout-items :items="$item['children']" :level="$level + 1" />
        </div>
    @else
        <a href="{{ $href }}"
           class="sidebar-flyout__link"
           data-level="{{ $level }}"
           @if (! $hasRoute) data-coming-soon="1" @endif>
            {{ $item['label'] }}
        </a>
    @endif
@endforeach
