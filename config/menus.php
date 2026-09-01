<?php

// Sidebar menu trees. One file so both roles' navigation is visible
// at a glance and easy to reorder — the recursive sidebar-menu
// component renders whatever depth of "children" it finds, so a
// third or fourth level just works without touching the component.
//
// 'route' => null means "not built yet" — the component links these
// to '#' and fires a "coming soon" notice on click (see app.js)
// rather than a dead link with no feedback.
//
// 'types' => [...] restricts an item to specific company `type`
// values (see the Company model's themeKey()/type column) — used on
// the "Operations" branch below so a pharmacy doesn't see "Vehicles
// & bookings" and a farm doesn't see "Pharmacy stock". Items with no
// 'types' key are shown to every company. Filtering happens in
// layouts/app.blade.php via acasFilterMenuForCompany(), not here —
// this file stays pure data.
//
// IMPORTANT: only put routes here that every viewer of this menu is
// actually allowed to visit. The 'company' menu is shared by staff,
// company admins, AND a super admin who's browsing into a company —
// a route restricted to super admins (like super-admin.dashboard)
// does not belong in it, since a regular staff member clicking it
// would hit a 403. See layouts/app.blade.php for how a super-admin-
// only "back to overview" link gets added conditionally instead.

return [

    'super_admin' => [
        ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'route' => 'super-admin.dashboard'],
        ['label' => 'Companies', 'icon' => 'bi-buildings', 'route' => 'super-admin.companies.index'],
        [
            'label' => 'Team',
            'icon' => 'bi-people',
            'route' => null,
            'children' => [
                ['label' => 'Super admins', 'route' => 'super-admin.super-admins.index'],
                ['label' => 'Company admins', 'route' => 'super-admin.company-admins.index'],
            ],
        ],
        ['label' => 'Reports', 'icon' => 'bi-graph-up', 'route' => null],
    ],

    'company' => [
        ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'route' => 'company.dashboard'],
        ['label' => 'Contacts', 'icon' => 'bi-people', 'route' => 'company.contacts.index'],
        [
            'label' => 'Sales',
            'icon' => 'bi-cash-stack',
            'route' => null,
            'children' => [
                ['label' => 'Pipelines', 'route' => null],
                ['label' => 'Deals', 'route' => null],
                [
                    'label' => 'Orders',
                    'route' => null,
                    'children' => [
                        ['label' => 'All orders', 'route' => null],
                        ['label' => 'Payments', 'route' => null],
                        ['label' => 'Receipts', 'route' => null],
                    ],
                ],
            ],
        ],
        ['label' => 'Products', 'icon' => 'bi-box-seam', 'route' => 'company.products.index'],
        [
            'label' => 'Operations',
            'icon' => 'bi-diagram-3',
            'route' => null,
            'children' => [
                ['label' => 'Properties & leases', 'route' => null, 'types' => ['properties']],
                ['label' => 'Vehicles & bookings', 'route' => null, 'types' => ['automotive']],
                ['label' => 'Pharmacy stock', 'route' => 'company.pharmacy-batches.index', 'types' => ['pharmacy']],
                ['label' => 'Crop & livestock', 'route' => null, 'types' => ['farms']],
                ['label' => 'Solar projects', 'route' => null, 'types' => ['energy']],
                ['label' => 'Engagements', 'route' => null, 'types' => ['agric_services', 'farms']],
            ],
        ],
        [
            'label' => 'Team',
            'icon' => 'bi-person-badge',
            'route' => null,
            'children' => [
                ['label' => 'Staff', 'route' => 'company.staff.index'],
                ['label' => 'Company settings', 'route' => 'company.settings.edit'],
            ],
        ],
    ],

];

