# Admin Panel Diagnosis Report

**Date:** 2026-07-13  
**Project:** Laravel 12 + Inertia.js 2 + Vue 3 (Wedding Website)  
**Purpose:** Diagnose why the admin sidebar/menu and layout appear duplicated (rendered twice) when navigating to any admin page.

---

## 1. Directory Tree: `resources/js/Layouts/`

```
resources/js/Layouts/
├── AdminLayout.vue
└── AppLayout.vue
```

Two layout files. `AdminLayout` is a custom wedding-panel layout; `AppLayout` is Jetstream's default layout.

---

## 2. Directory Tree: `resources/js/Pages/Admin/`

```
resources/js/Pages/Admin/
├── Dashboard.vue/          ← ⚠️ DIRECTORY, not a .vue file! (EMPTY)
├── Faqs/
│   ├── Index.vue
│   └── Partials/           ← EMPTY
├── Gallery/
│   └── Index.vue
├── Guests/
│   ├── Index.vue
│   └── Partials/           ← EMPTY
└── Settings/
    └── Edit.vue
```

**Critical finding:** `Dashboard.vue` exists as an **empty directory**, not a `.vue` file. This means `Inertia::render('Admin/Dashboard', ...)` from the `DashboardController` will fail to resolve.

---

## 3. How Each Admin Page Applies Its Layout

### 3.1. Pattern Used: **DUAL application (BUG!)**

All 4 existing admin pages use the **same dual pattern** which causes double-rendering:

| Page | `defineOptions({ layout: AdminLayout })` | `<AdminLayout>` wrapper in template |
|------|------------------------------------------|-------------------------------------|
| `Faqs/Index.vue` | ✅ YES (line ~20) | ✅ YES (line ~94: `<AdminLayout title="Preguntas Frecuentes">`) |
| `Gallery/Index.vue` | ✅ YES (line ~13) | ✅ YES (line ~53: `<AdminLayout title="Galería">`) |
| `Guests/Index.vue` | ✅ YES (line ~18) | ✅ YES (line ~112: `<AdminLayout title="Invitados">`) |
| `Settings/Edit.vue` | ✅ YES (line ~14) | ✅ YES (line ~53: `<AdminLayout title="Configuración del Sitio">`) |

**This is the ROOT CAUSE of the double-rendering bug.** The layout is applied:

1. **Once** by Inertia's persistent layout system (via `defineOptions({ layout: AdminLayout })`) — this wraps the page component in `AdminLayout`.
2. **A second time** by the template itself (via `<AdminLayout>` tags directly in the template) — which wraps the content again inside another `AdminLayout`.

Result: Two sidebars, two topbars, two countdowns — everything in `AdminLayout` appears twice.

### 3.2. `resources/js/app.js` — Global Layout Resolution

```js
import './bootstrap';
import '../css/app.css';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {
        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
```

**Observations:**
- No `setDefaultOptions` call — no global layout default is set.
- No `resolvePageComponent` override that applies a layout globally.
- The `resolve` callback is the standard Laravel Breeze/Jetstream pattern.
- No duplicate `createApp` or `app.mount` calls.
- No `import.meta.hot` handling.

### 3.3. Global Component Registrations

**None.** `app.js` does not register any global components via `app.component(...)`. Components are only imported locally in each page/layout.

---

## 4. Full Contents of `resources/js/Layouts/AdminLayout.vue`

```vue
<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    HomeIcon, UserGroupIcon, QuestionMarkCircleIcon,
    SparklesIcon, PhotoIcon, Bars3Icon, XMarkIcon,
    ArrowRightOnRectangleIcon, ChevronDownIcon,
} from '@heroicons/vue/24/outline';
import ApplicationMark from '@/Components/ApplicationMark.vue';

defineProps({ title: String });

const sidebarOpen = ref(false);
const isMobile = ref(false);

function checkMobile() { isMobile.value = window.innerWidth < 768; }

onMounted(() => { checkMobile(); window.addEventListener('resize', checkMobile); });
onUnmounted(() => window.removeEventListener('resize', checkMobile));

const page = usePage();

const navItems = [
    { icon: HomeIcon, label: 'Dashboard', route: 'admin.dashboard', active: 'admin.dashboard' },
    { icon: UserGroupIcon, label: 'Invitados', route: 'admin.guests.index', active: 'admin.guests.*' },
    { icon: QuestionMarkCircleIcon, label: 'Preguntas Frecuentes', route: 'admin.faqs.index', active: 'admin.faqs.*' },
    { icon: SparklesIcon, label: 'Configuración', route: 'admin.settings.edit', active: 'admin.settings.*' },
    { icon: PhotoIcon, label: 'Galería', route: 'admin.gallery.index', active: 'admin.gallery.*' },
];

function isActive(item) {
    return route().current(item.active);
}

const logout = () => router.post(route('logout'));

// Countdown in sidebar footer
const countdownDays = ref(0);
let countdownTimer = null;

function updateSidebarCountdown() {
    const eventDate = page.props.adminSettings?.event_datetime
        ? new Date(page.props.adminSettings.event_datetime)
        : new Date('2026-11-14T16:00:00');
    const diff = eventDate - new Date();
    countdownDays.value = diff > 0 ? Math.floor(diff / (1000 * 60 * 60 * 24)) : 0;
}

onMounted(() => {
    updateSidebarCountdown();
    countdownTimer = setInterval(updateSidebarCountdown, 60000);
});
onUnmounted(() => { if (countdownTimer) clearInterval(countdownTimer); });
</script>

<template>
    <div class="min-h-screen bg-arena-light flex">
        <Head :title="title ?? 'Admin'" />

        <!-- ── Sidebar Overlay (mobile) ── -->
        <div v-if="sidebarOpen && isMobile" @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-cuero/50 backdrop-blur-sm md:hidden"></div>

        <!-- ── Sidebar ── -->
        <aside :class="[
            'fixed md:sticky top-0 left-0 z-50 h-screen w-64 bg-cuero flex flex-col transition-transform duration-300',
            isMobile && !sidebarOpen ? '-translate-x-full' : 'translate-x-0',
        ]">
            <!-- Logo -->
            <div class="flex items-center justify-between px-5 py-5 border-b border-white/10">
                <Link :href="route('admin.dashboard')" class="flex items-center gap-3" @click="sidebarOpen = false">
                    <ApplicationMark class="h-8 w-auto" />
                    <span class="font-slab text-arena text-sm tracking-wide">Panel Boda</span>
                </Link>
                <button v-if="isMobile" @click="sidebarOpen = false" class="text-arena/60 hover:text-arena md:hidden">
                    <XMarkIcon class="w-5 h-5" />
                </button>
            </div>

            <!-- Nav items -->
            <nav class="flex-1 py-4 px-3 space-y-1 overflow-y-auto">
                <Link v-for="item in navItems" :key="item.label"
                    :href="route(item.route)"
                    @click="isMobile && (sidebarOpen = false)"
                    :class="[
                        'flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-medium transition-all duration-200',
                        isActive(item)
                            ? 'bg-dorado/15 text-dorado border-l-4 border-dorado pl-3'
                            : 'text-arena/60 hover:text-arena hover:bg-white/5 border-l-4 border-transparent pl-3',
                    ]"
                >
                    <component :is="item.icon" class="w-5 h-5 flex-shrink-0" />
                    <span>{{ item.label }}</span>
                </Link>
            </nav>

            <!-- Sidebar footer: countdown -->
            <div class="px-4 py-4 border-t border-white/10">
                <div class="bg-white/5 rounded-xl px-4 py-3 text-center">
                    <p class="text-arena/40 text-xs uppercase tracking-wider mb-1">Faltan</p>
                    <p class="text-dorado font-slab text-2xl font-bold">{{ countdownDays }}</p>
                    <p class="text-arena/40 text-xs">días para la boda</p>
                </div>
            </div>
        </aside>

        <!-- ── Main Content Area ── -->
        <div class="flex-1 flex flex-col min-w-0">
            <!-- Topbar -->
            <header class="sticky top-0 z-30 bg-white/80 backdrop-blur-md border-b border-cuero/10">
                <div class="flex items-center justify-between px-4 md:px-8 h-16">
                    <!-- Mobile hamburger -->
                    <button @click="sidebarOpen = !sidebarOpen" class="md:hidden p-2 text-cuero hover:text-cuero-light">
                        <Bars3Icon class="w-6 h-6" />
                    </button>

                    <div class="flex-1"></div>

                    <!-- User dropdown (Jetstream style) -->
                    <div class="relative">
                        <button
                            @click="logout"
                            class="flex items-center gap-2 text-sm text-cuero/60 hover:text-cuero transition-colors px-3 py-2 rounded-lg hover:bg-arena"
                        >
                            <span class="hidden sm:inline">{{ $page.props.auth.user?.name ?? 'Admin' }}</span>
                            <ArrowRightOnRectangleIcon class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </header>

            <!-- Page content -->
            <main class="flex-1 p-4 md:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>
```

**Observations:**
- `AdminLayout` does **NOT** import `AppLayout.vue`. It is a standalone layout.
- It imports only: `ApplicationMark`, HeroIcons, and Inertia/Vue helpers.
- It has its own sidebar, topbar (with logout), and a `<slot />` for page content.
- It uses `<slot />` to render page content — this is where the page component gets placed by Inertia's persistent layout system.

---

## 5. Full Contents of `resources/js/Layouts/AppLayout.vue`

```vue
<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import ApplicationMark from '@/Components/ApplicationMark.vue';
import Banner from '@/Components/Banner.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink.vue';

defineProps({
    title: String,
});

const showingNavigationDropdown = ref(false);

const switchToTeam = (team) => {
    router.put(route('current-team.update'), {
        team_id: team.id,
    }, {
        preserveState: false,
    });
};

const logout = () => {
    router.post(route('logout'));
};
</script>

<template>
    <div>
        <Head :title="title" />

        <Banner />

        <div class="min-h-screen bg-gray-100">
            <nav class="bg-white border-b border-gray-100">
                <!-- Primary Navigation Menu -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex justify-between h-16">
                        <div class="flex">
                            <!-- Logo -->
                            <div class="shrink-0 flex items-center">
                                <Link :href="route('dashboard')">
                                    <ApplicationMark class="block h-9 w-auto" />
                                </Link>
                            </div>

                            <!-- Navigation Links -->
                            <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                                    Dashboard
                                </NavLink>

                                <!-- Admin Links (solo admin) -->
                                <template v-if="$page.props.auth.user?.is_admin">
                                    <NavLink :href="route('admin.dashboard')" :active="route().current('admin.dashboard')">
                                        Panel Admin
                                    </NavLink>
                                    <NavLink :href="route('admin.guests.index')" :active="route().current('admin.guests.*')">
                                        Invitados
                                    </NavLink>
                                    <NavLink :href="route('admin.faqs.index')" :active="route().current('admin.faqs.*')">
                                        FAQs
                                    </NavLink>
                                    <NavLink :href="route('admin.gallery.index')" :active="route().current('admin.gallery.*')">
                                        Galería
                                    </NavLink>
                                    <NavLink :href="route('admin.settings.edit')" :active="route().current('admin.settings.*')">
                                        Configuración
                                    </NavLink>
                                </template>
                            </div>
                        </div>

                        <div class="hidden sm:flex sm:items-center sm:ms-6">
                            <div class="ms-3 relative">
                                <!-- Teams Dropdown -->
                                <Dropdown v-if="$page.props.jetstream.hasTeamFeatures" align="right" width="60">
                                    <template #trigger>
                                        <span class="inline-flex rounded-md">
                                            <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none focus:bg-gray-50 active:bg-gray-50 transition ease-in-out duration-150">
                                                {{ $page.props.auth.user.current_team.name }}
                                                <svg class="ms-2 -me-0.5 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 15L12 18.75 15.75 15m-7.5-6L12 5.25 15.75 9" />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>
                                    <template #content>
                                        <div class="w-60">
                                            <div class="block px-4 py-2 text-xs text-gray-400">
                                                Manage Team
                                            </div>
                                            <DropdownLink :href="route('teams.show', $page.props.auth.user.current_team)">
                                                Team Settings
                                            </DropdownLink>
                                            <DropdownLink v-if="$page.props.jetstream.canCreateTeams" :href="route('teams.create')">
                                                Create New Team
                                            </DropdownLink>
                                            <template v-if="$page.props.auth.user.all_teams.length > 1">
                                                <div class="border-t border-gray-200" />
                                                <div class="block px-4 py-2 text-xs text-gray-400">
                                                    Switch Teams
                                                </div>
                                                <template v-for="team in $page.props.auth.user.all_teams" :key="team.id">
                                                    <form @submit.prevent="switchToTeam(team)">
                                                        <DropdownLink as="button">
                                                            <div class="flex items-center">
                                                                <svg v-if="team.id == $page.props.auth.user.current_team_id" class="me-2 size-5 text-green-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                                </svg>
                                                                <div>{{ team.name }}</div>
                                                            </div>
                                                        </DropdownLink>
                                                    </form>
                                                </template>
                                            </template>
                                        </div>
                                    </template>
                                </Dropdown>
                            </div>

                            <!-- Settings Dropdown -->
                            <div class="ms-3 relative">
                                <Dropdown align="right" width="48">
                                    <template #trigger>
                                        <button v-if="$page.props.jetstream.managesProfilePhotos" class="flex text-sm border-2 border-transparent rounded-full focus:outline-none focus:border-gray-300 transition">
                                            <img class="size-8 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                                        </button>
                                        <span v-else class="inline-flex rounded-md">
                                            <button type="button" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-white hover:text-gray-700 focus:outline-none focus:bg-gray-50 active:bg-gray-50 transition ease-in-out duration-150">
                                                {{ $page.props.auth.user.name }}
                                                <svg class="ms-2 -me-0.5 size-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>
                                    <template #content>
                                        <div class="block px-4 py-2 text-xs text-gray-400">
                                            Manage Account
                                        </div>
                                        <DropdownLink :href="route('profile.show')">
                                            Profile
                                        </DropdownLink>
                                        <DropdownLink v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')">
                                            API Tokens
                                        </DropdownLink>
                                        <div class="border-t border-gray-200" />
                                        <form @submit.prevent="logout">
                                            <DropdownLink as="button">
                                                Log Out
                                            </DropdownLink>
                                        </form>
                                    </template>
                                </Dropdown>
                            </div>
                        </div>

                        <!-- Hamburger -->
                        <div class="-me-2 flex items-center sm:hidden">
                            <button class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out" @click="showingNavigationDropdown = ! showingNavigationDropdown">
                                <svg class="size-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                                    <path :class="{'hidden': showingNavigationDropdown, 'inline-flex': ! showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                                    <path :class="{'hidden': ! showingNavigationDropdown, 'inline-flex': showingNavigationDropdown }" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Responsive Navigation Menu -->
                <div :class="{'block': showingNavigationDropdown, 'hidden': ! showingNavigationDropdown}" class="sm:hidden">
                    <div class="pt-2 pb-3 space-y-1">
                        <ResponsiveNavLink :href="route('dashboard')" :active="route().current('dashboard')">
                            Dashboard
                        </ResponsiveNavLink>
                        <template v-if="$page.props.auth.user?.is_admin">
                            <ResponsiveNavLink :href="route('admin.dashboard')" :active="route().current('admin.dashboard')">
                                Panel Admin
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('admin.guests.index')" :active="route().current('admin.guests.*')">
                                Invitados
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('admin.faqs.index')" :active="route().current('admin.faqs.*')">
                                FAQs
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('admin.gallery.index')" :active="route().current('admin.gallery.*')">
                                Galería
                            </ResponsiveNavLink>
                            <ResponsiveNavLink :href="route('admin.settings.edit')" :active="route().current('admin.settings.*')">
                                Configuración
                            </ResponsiveNavLink>
                        </template>
                    </div>

                    <!-- Responsive Settings Options -->
                    <div class="pt-4 pb-1 border-t border-gray-200">
                        <div class="flex items-center px-4">
                            <div v-if="$page.props.jetstream.managesProfilePhotos" class="shrink-0 me-3">
                                <img class="size-10 rounded-full object-cover" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                            </div>
                            <div>
                                <div class="font-medium text-base text-gray-800">
                                    {{ $page.props.auth.user.name }}
                                </div>
                                <div class="font-medium text-sm text-gray-500">
                                    {{ $page.props.auth.user.email }}
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 space-y-1">
                            <ResponsiveNavLink :href="route('profile.show')" :active="route().current('profile.show')">
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink v-if="$page.props.jetstream.hasApiFeatures" :href="route('api-tokens.index')" :active="route().current('api-tokens.index')">
                                API Tokens
                            </ResponsiveNavLink>
                            <form method="POST" @submit.prevent="logout">
                                <ResponsiveNavLink as="button">
                                    Log Out
                                </ResponsiveNavLink>
                            </form>
                            <template v-if="$page.props.jetstream.hasTeamFeatures">
                                <div class="border-t border-gray-200" />
                                <div class="block px-4 py-2 text-xs text-gray-400">
                                    Manage Team
                                </div>
                                <ResponsiveNavLink :href="route('teams.show', $page.props.auth.user.current_team)" :active="route().current('teams.show')">
                                    Team Settings
                                </ResponsiveNavLink>
                                <ResponsiveNavLink v-if="$page.props.jetstream.canCreateTeams" :href="route('teams.create')" :active="route().current('teams.create')">
                                    Create New Team
                                </ResponsiveNavLink>
                            </template>
                        </div>
                    </div>
                </div>
            </nav>

            <!-- Page Heading -->
            <header v-if="$slots.header" class="bg-white shadow">
                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                    <slot name="header" />
                </div>
            </header>

            <!-- Page Content -->
            <main>
                <slot />
            </main>
        </div>
    </div>
</template>
```

**Observations:** `AppLayout` is Jetstream's default layout with top nav bar. It does NOT import `AdminLayout`. It includes admin navigation links (added by a prior change) shown conditionally via `$page.props.auth.user?.is_admin`. It uses `<slot />` for page content.

---

## 6. Full Contents of Every Admin Page

### 6.1. `resources/js/Pages/Admin/Faqs/Index.vue`

```vue
<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import Checkbox from '@/Components/Checkbox.vue';
import DialogModal from '@/Components/DialogModal.vue';
import ActionMessage from '@/Components/ActionMessage.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import EmptyState from '@/Components/Admin/EmptyState.vue';
import ConfirmDeleteModal from '@/Components/Admin/ConfirmDeleteModal.vue';
import { ChevronUpIcon, ChevronDownIcon, PencilIcon, TrashIcon, PlusIcon, QuestionMarkCircleIcon } from '@heroicons/vue/24/outline';

defineOptions({ layout: AdminLayout });

const props = defineProps({ faqs: Array });

// ── FAQ Form Modal ──
const showFaqModal = ref(false);
const editingFaq = ref(null);

const faqForm = useForm({
    question: '',
    answer: '',
    order: 0,
    is_published: true,
});

function openCreateFaq() {
    editingFaq.value = null;
    faqForm.reset();
    faqForm.order = (props.faqs?.length ?? 0) + 1;
    faqForm.is_published = true;
    showFaqModal.value = true;
}

function openEditFaq(faq) {
    editingFaq.value = faq;
    faqForm.question = faq.question;
    faqForm.answer = faq.answer;
    faqForm.order = faq.order;
    faqForm.is_published = faq.is_published;
    showFaqModal.value = true;
}

function saveFaq() {
    if (editingFaq.value) {
        faqForm.put(route('admin.faqs.update', editingFaq.value.id), {
            preserveScroll: true,
            onSuccess: () => { showFaqModal.value = false; },
        });
    } else {
        faqForm.post(route('admin.faqs.store'), {
            preserveScroll: true,
            onSuccess: () => { showFaqModal.value = false; },
        });
    }
}

// ── Reorder ──
function moveUp(index) {
    if (index === 0) return;
    const faq = props.faqs[index];
    useForm({ order: index }).put(route('admin.faqs.update', faq.id), { preserveScroll: true });
}

function moveDown(index) {
    if (index >= (props.faqs?.length ?? 0) - 1) return;
    const faq = props.faqs[index];
    useForm({ order: index + 2 }).put(route('admin.faqs.update', faq.id), { preserveScroll: true });
}

// ── Toggle publish ──
function togglePublished(faq) {
    useForm({ is_published: !faq.is_published }).put(route('admin.faqs.update', faq.id), { preserveScroll: true });
}

// ── Delete ──
const deleteFaqId = ref(null);
function confirmDelete(faq) { deleteFaqId.value = faq; }
const deleteForm = useForm({});
function doDelete() {
    if (!deleteFaqId.value) return;
    deleteForm.delete(route('admin.faqs.destroy', deleteFaqId.value.id), {
        preserveScroll: true,
        onSuccess: () => { deleteFaqId.value = null; },
    });
}
</script>

<template>
    <AdminLayout title="Preguntas Frecuentes">
        <div class="max-w-4xl mx-auto">
            <div class="flex items-center justify-between mb-8">
                <h1 class="font-slab text-2xl text-cuero">Preguntas Frecuentes</h1>
                <PrimaryButton @click="openCreateFaq" class="flex items-center gap-2">
                    <PlusIcon class="w-4 h-4" />
                    Nueva Pregunta
                </PrimaryButton>
            </div>

            <ActionMessage :on="faqForm.recentlySuccessful" class="mb-4">Guardado.</ActionMessage>

            <!-- FAQ list -->
            <div v-if="faqs && faqs.length > 0" class="space-y-3">
                <div v-for="(faq, i) in faqs" :key="faq.id"
                    class="bg-white rounded-2xl border border-cuero/10 p-5 hover:shadow-sm transition-shadow">
                    <div class="flex items-start gap-4">
                        <!-- Reorder arrows -->
                        <div class="flex flex-col gap-0.5 pt-0.5">
                            <button @click="moveUp(i)" :disabled="i === 0"
                                class="p-0.5 text-cuero/30 hover:text-cuero disabled:opacity-20 transition-colors" :aria-label="'Subir ' + faq.question">
                                <ChevronUpIcon class="w-4 h-4" />
                            </button>
                            <button @click="moveDown(i)" :disabled="i >= faqs.length - 1"
                                class="p-0.5 text-cuero/30 hover:text-cuero disabled:opacity-20 transition-colors" :aria-label="'Bajar ' + faq.question">
                                <ChevronDownIcon class="w-4 h-4" />
                            </button>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-3 mb-1">
                                <h3 class="font-slab text-cuero truncate">{{ faq.question }}</h3>
                                <StatusBadge :status="faq.is_published ? 'approved' : 'rejected'" variant="gallery" />
                            </div>
                            <p class="text-cuero/50 text-sm line-clamp-2">{{ faq.answer }}</p>
                        </div>

                        <div class="flex items-center gap-1 flex-shrink-0">
                            <button @click="togglePublished(faq)" class="p-2 text-cuero/30 hover:text-dorado transition-colors rounded-lg hover:bg-dorado/5" :title="faq.is_published ? 'Ocultar' : 'Publicar'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="faq.is_published ? 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' : 'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21'"/></svg>
                            </button>
                            <button @click="openEditFaq(faq)" class="p-2 text-cuero/30 hover:text-mezclilla transition-colors rounded-lg hover:bg-mezclilla/5" title="Editar">
                                <PencilIcon class="w-4 h-4" />
                            </button>
                            <ConfirmDeleteModal :message="`¿Eliminar la pregunta «${faq.question.substring(0, 50)}...»?`" @confirm="doDelete">
                                <template #default="{ open: openDel }">
                                    <button @click="confirmDelete(faq); openDel()" class="p-2 text-cuero/30 hover:text-red-500 transition-colors rounded-lg hover:bg-red-50" title="Eliminar">
                                        <TrashIcon class="w-4 h-4" />
                                    </button>
                                </template>
                            </ConfirmDeleteModal>
                        </div>
                    </div>
                </div>
            </div>

            <EmptyState v-else :icon="QuestionMarkCircleIcon" title="Sin preguntas frecuentes"
                description="Agrega la primera pregunta para que aparezca en el sitio público."
                cta-label="Nueva Pregunta" @click="openCreateFaq" />

            <!-- FAQ Form Modal -->
            <DialogModal :show="showFaqModal" @close="showFaqModal = false">
                <template #title>{{ editingFaq ? 'Editar Pregunta' : 'Nueva Pregunta' }}</template>
                <template #content>
                    <div class="space-y-4">
                        <div>
                            <InputLabel value="Pregunta" />
                            <TextInput v-model="faqForm.question" class="w-full mt-1" placeholder="Ej. ¿Hay estacionamiento?" />
                            <InputError :message="faqForm.errors.question" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Respuesta" />
                            <textarea v-model="faqForm.answer" rows="4"
                                class="w-full mt-1 rounded-xl border-cuero/20 focus:border-dorado focus:ring-dorado/20"
                                placeholder="Escribe la respuesta..."></textarea>
                            <InputError :message="faqForm.errors.answer" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Orden" />
                            <TextInput v-model="faqForm.order" type="number" class="w-24 mt-1" />
                        </div>
                        <label class="flex items-center gap-3 cursor-pointer">
                            <Checkbox v-model:checked="faqForm.is_published" />
                            <span class="text-sm text-cuero/70">Mostrar en el sitio público</span>
                        </label>
                    </div>
                </template>
                <template #footer>
                    <SecondaryButton @click="showFaqModal = false">Cancelar</SecondaryButton>
                    <PrimaryButton @click="saveFaq" :disabled="faqForm.processing" class="ms-3" :class="{ 'opacity-50': faqForm.processing }">
                        {{ faqForm.processing ? 'Guardando...' : 'Guardar' }}
                    </PrimaryButton>
                </template>
            </DialogModal>
        </div>
    </AdminLayout>
</template>
```

### 6.2. `resources/js/Pages/Admin/Gallery/Index.vue`

```vue
<script setup>
import { ref, computed } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import EmptyState from '@/Components/Admin/EmptyState.vue';
import ConfirmDeleteModal from '@/Components/Admin/ConfirmDeleteModal.vue';
import { PhotoIcon, CheckCircleIcon, XCircleIcon, TrashIcon } from '@heroicons/vue/24/outline';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    photos: Object, // paginated
    counts: Object,
});

const currentTab = ref(router.page.props.ziggy?.query?.status ?? 'pending');

function setTab(status) {
    currentTab.value = status;
    router.get(route('admin.gallery.index'), { status: status || undefined }, { preserveState: true, replace: true });
}

// ── Actions ──
const approveForm = useForm({});
function approve(photo) {
    approveForm.patch(route('admin.gallery.approve', photo.id), { preserveScroll: true });
}

const rejectForm = useForm({});
function reject(photo) {
    rejectForm.patch(route('admin.gallery.reject', photo.id), { preserveScroll: true });
}

const deletePhoto = ref(null);
const deleteForm = useForm({});
function confirmDelete(photo) { deletePhoto.value = photo; }
function doDelete() {
    if (!deletePhoto.value) return;
    deleteForm.delete(route('admin.gallery.destroy', deletePhoto.value.id), {
        preserveScroll: true,
        onSuccess: () => { deletePhoto.value = null; },
    });
}

// ── Lightbox ──
const lightboxImage = ref(null);
function openLightbox(url) { lightboxImage.value = url; }
function closeLightbox() { lightboxImage.value = null; }
</script>

<template>
    <AdminLayout title="Galería">
        <div class="max-w-7xl mx-auto">
            <h1 class="font-slab text-2xl text-cuero mb-8">Galería — Moderación</h1>

            <!-- Tabs -->
            <div class="flex gap-2 mb-8 flex-wrap">
                <button @click="setTab('pending')"
                    :class="currentTab === 'pending' ? 'bg-dorado text-white' : 'bg-white text-cuero/60 hover:bg-arena'"
                    class="px-4 py-2 rounded-xl text-sm font-medium transition-colors">
                    Pendientes ({{ counts?.pending ?? 0 }})
                </button>
                <button @click="setTab('approved')"
                    :class="currentTab === 'approved' ? 'bg-olivo text-white' : 'bg-white text-cuero/60 hover:bg-arena'"
                    class="px-4 py-2 rounded-xl text-sm font-medium transition-colors">
                    Aprobadas ({{ counts?.approved ?? 0 }})
                </button>
                <button @click="setTab('rejected')"
                    :class="currentTab === 'rejected' ? 'bg-red-500 text-white' : 'bg-white text-cuero/60 hover:bg-arena'"
                    class="px-4 py-2 rounded-xl text-sm font-medium transition-colors">
                    Rechazadas ({{ counts?.rejected ?? 0 }})
                </button>
            </div>

            <!-- Photo Grid -->
            <div v-if="photos?.data?.length > 0" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3 md:gap-4">
                <div v-for="photo in photos.data" :key="photo.id"
                    class="relative aspect-square rounded-2xl overflow-hidden group shadow-sm hover:shadow-lg transition-all duration-300">
                    <img :src="photo.image_url" alt="Foto" class="w-full h-full object-cover" @click="openLightbox(photo.image_url)" />

                    <!-- Overlay con acciones -->
                    <div class="absolute inset-0 bg-cuero/0 group-hover:bg-cuero/40 transition-all flex items-center justify-center gap-2 opacity-0 group-hover:opacity-100">
                        <button v-if="photo.status !== 'approved'" @click.stop="approve(photo)"
                            class="w-9 h-9 bg-olivo hover:bg-olivo-dark text-white rounded-full flex items-center justify-center transition-colors shadow-lg" title="Aprobar">
                            <CheckCircleIcon class="w-5 h-5" />
                        </button>
                        <button v-if="photo.status !== 'rejected'" @click.stop="reject(photo)"
                            class="w-9 h-9 bg-red-500 hover:bg-red-600 text-white rounded-full flex items-center justify-center transition-colors shadow-lg" title="Rechazar">
                            <XCircleIcon class="w-5 h-5" />
                        </button>
                        <ConfirmDeleteModal :message="'¿Eliminar esta foto permanentemente? No se puede deshacer.'" @confirm="doDelete">
                            <template #default="{ open: openDel }">
                                <button @click.stop="confirmDelete(photo); openDel()"
                                    class="w-9 h-9 bg-cuero hover:bg-cuero-dark text-white rounded-full flex items-center justify-center transition-colors shadow-lg" title="Eliminar">
                                    <TrashIcon class="w-4 h-4" />
                                </button>
                            </template>
                        </ConfirmDeleteModal>
                    </div>

                    <!-- Uploader name -->
                    <div v-if="photo.uploader_name" class="absolute bottom-2 left-2">
                        <span class="bg-cuero/70 backdrop-blur-sm text-white text-xs px-2 py-1 rounded-lg">
                            {{ photo.uploader_name }}
                        </span>
                    </div>
                </div>
            </div>

            <EmptyState v-else :icon="PhotoIcon"
                :title="currentTab === 'pending' ? 'No hay fotos pendientes de revisión 🎉' : currentTab === 'approved' ? 'No hay fotos aprobadas' : 'No hay fotos rechazadas'"
                :description="currentTab === 'pending' ? '¡Todo en orden! Las fotos nuevas aparecerán aquí.' : ''" />

            <!-- Pagination -->
            <div v-if="photos?.links?.length > 3" class="mt-8 flex items-center justify-between">
                <span class="text-xs text-cuero/50">{{ photos.from }}–{{ photos.to }} de {{ photos.total }}</span>
                <div class="flex gap-1">
                    <a v-for="link in photos.links" :key="link.label"
                        :href="link.url ?? '#'" v-html="link.label"
                        :class="['px-3 py-1.5 rounded-lg text-sm transition-colors', link.active ? 'bg-cuero text-white' : link.url ? 'text-cuero/60 hover:bg-arena' : 'text-cuero/20 cursor-default']"></a>
                </div>
            </div>

            <!-- Lightbox -->
            <Teleport to="body">
                <div v-if="lightboxImage" @click="closeLightbox"
                    class="fixed inset-0 z-[100] bg-cuero/95 backdrop-blur-sm flex items-center justify-center p-4 cursor-pointer">
                    <button @click="closeLightbox" class="absolute top-6 right-6 text-white/60 hover:text-white transition-colors">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                    <img :src="lightboxImage" alt="Foto ampliada" class="max-w-full max-h-[90vh] rounded-2xl shadow-2xl" @click.stop />
                </div>
            </Teleport>
        </div>
    </AdminLayout>
</template>
```

### 6.3. `resources/js/Pages/Admin/Guests/Index.vue`

```vue
<script setup>
import { ref, watch } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import InputLabel from '@/Components/InputLabel.vue';
import InputError from '@/Components/InputError.vue';
import DialogModal from '@/Components/DialogModal.vue';
import ActionMessage from '@/Components/ActionMessage.vue';
import StatusBadge from '@/Components/Admin/StatusBadge.vue';
import EmptyState from '@/Components/Admin/EmptyState.vue';
import ConfirmDeleteModal from '@/Components/Admin/ConfirmDeleteModal.vue';
import { PlusIcon, PencilIcon, TrashIcon, UserGroupIcon, ArrowUpTrayIcon, DocumentTextIcon, MagnifyingGlassIcon } from '@heroicons/vue/24/outline';

defineOptions({ layout: AdminLayout });

const props = defineProps({
    guests: Object, // paginated
    filters: Object,
    statusCounts: Object,
});

// ── Search & Filter ──
const search = ref(props.filters?.search ?? '');
const statusFilter = ref(props.filters?.status ?? '');

watch(search, (val) => {
    router.get(route('admin.guests.index'), { search: val, status: statusFilter.value }, { preserveState: true, replace: true });
});

function setStatusFilter(status) {
    statusFilter.value = status;
    router.get(route('admin.guests.index'), { search: search.value, status }, { preserveState: true, replace: true });
}

// ── Guest Form Modal ──
const showGuestModal = ref(false);
const editingGuest = ref(null);

const guestForm = useForm({
    full_name: '',
    allowed_passes: 1,
    table_group: '',
});

function openCreate() {
    editingGuest.value = null;
    guestForm.reset();
    guestForm.allowed_passes = 1;
    showGuestModal.value = true;
}

function openEdit(guest) {
    editingGuest.value = guest;
    guestForm.full_name = guest.full_name;
    guestForm.allowed_passes = guest.allowed_passes;
    guestForm.table_group = guest.table_group ?? '';
    showGuestModal.value = true;
}

function saveGuest() {
    if (editingGuest.value) {
        guestForm.put(route('admin.guests.update', editingGuest.value.id), {
            preserveScroll: true,
            onSuccess: () => { showGuestModal.value = false; },
        });
    } else {
        guestForm.post(route('admin.guests.store'), {
            preserveScroll: true,
            onSuccess: () => { showGuestModal.value = false; },
        });
    }
}

// ── Import CSV Modal ──
const showImportModal = ref(false);
const importForm = useForm({ csv_file: null });
const importResult = ref(null);

function onCsvFile(e) {
    importForm.csv_file = e.target.files[0];
}

function doImport() {
    importForm.post(route('admin.guests.import'), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: (page) => {
            importResult.value = page.props.flash?.success ?? 'Importación completada.';
        },
    });
}

// ── Delete ──
const deleteGuest = ref(null);
const deleteForm = useForm({});
function confirmDelete(guest) { deleteGuest.value = guest; }
function doDelete() {
    if (!deleteGuest.value) return;
    deleteForm.delete(route('admin.guests.destroy', deleteGuest.value.id), {
        preserveScroll: true,
        onSuccess: () => { deleteGuest.value = null; },
    });
}

// ── Format date ──
function formatDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('es-MX', { day: 'numeric', month: 'short', year: 'numeric' });
}
</script>

<template>
    <AdminLayout title="Invitados">
        <div class="max-w-6xl mx-auto">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
                <h1 class="font-slab text-2xl text-cuero">Invitados</h1>
                <div class="flex gap-2">
                    <SecondaryButton @click="showImportModal = true" class="flex items-center gap-2">
                        <ArrowUpTrayIcon class="w-4 h-4" />
                        Importar CSV
                    </SecondaryButton>
                    <PrimaryButton @click="openCreate" class="flex items-center gap-2">
                        <PlusIcon class="w-4 h-4" />
                        Agregar Invitado
                    </PrimaryButton>
                </div>
            </div>

            <ActionMessage :on="guestForm.recentlySuccessful" class="mb-4">Guardado.</ActionMessage>

            <!-- Status tabs + search -->
            <div class="flex flex-col sm:flex-row gap-4 mb-6">
                <div class="flex gap-2 flex-wrap">
                    <button @click="setStatusFilter('')"
                        :class="!statusFilter ? 'bg-cuero text-white' : 'bg-white text-cuero/60 hover:bg-arena'"
                        class="px-4 py-2 rounded-xl text-sm font-medium transition-colors">
                        Todos ({{ statusCounts?.total ?? 0 }})
                    </button>
                    <button @click="setStatusFilter('pending')"
                        :class="statusFilter === 'pending' ? 'bg-dorado text-white' : 'bg-white text-cuero/60 hover:bg-arena'"
                        class="px-4 py-2 rounded-xl text-sm font-medium transition-colors">
                        Pendientes ({{ statusCounts?.pending ?? 0 }})
                    </button>
                    <button @click="setStatusFilter('confirmed')"
                        :class="statusFilter === 'confirmed' ? 'bg-olivo text-white' : 'bg-white text-cuero/60 hover:bg-arena'"
                        class="px-4 py-2 rounded-xl text-sm font-medium transition-colors">
                        Confirmados ({{ statusCounts?.confirmed ?? 0 }})
                    </button>
                    <button @click="setStatusFilter('declined')"
                        :class="statusFilter === 'declined' ? 'bg-red-500 text-white' : 'bg-white text-cuero/60 hover:bg-arena'"
                        class="px-4 py-2 rounded-xl text-sm font-medium transition-colors">
                        No Asistirán ({{ statusCounts?.declined ?? 0 }})
                    </button>
                </div>
                <div class="relative flex-1 max-w-sm">
                    <MagnifyingGlassIcon class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-cuero/30" />
                    <input v-model="search" type="text" placeholder="Buscar invitado..."
                        class="w-full pl-9 pr-4 py-2.5 rounded-xl border border-cuero/20 text-sm text-cuero placeholder-cuero/30 focus:border-dorado focus:ring-dorado/20" />
                </div>
            </div>

            <!-- Guests table -->
            <div v-if="guests?.data?.length > 0" class="bg-white rounded-2xl border border-cuero/10 overflow-hidden shadow-sm">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-cuero/10 bg-arena/50">
                                <th class="text-left px-5 py-3 text-xs uppercase tracking-wide text-cuero/60 font-medium">Nombre</th>
                                <th class="text-center px-3 py-3 text-xs uppercase tracking-wide text-cuero/60 font-medium">Pases</th>
                                <th class="text-left px-3 py-3 text-xs uppercase tracking-wide text-cuero/60 font-medium">Estado</th>
                                <th class="text-center px-3 py-3 text-xs uppercase tracking-wide text-cuero/60 font-medium">Confirmados</th>
                                <th class="text-left px-3 py-3 text-xs uppercase tracking-wide text-cuero/60 font-medium hidden md:table-cell">Grupo</th>
                                <th class="text-right px-5 py-3 text-xs uppercase tracking-wide text-cuero/60 font-medium">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-cuero/5">
                            <tr v-for="guest in guests.data" :key="guest.id" class="hover:bg-arena/30 transition-colors">
                                <td class="px-5 py-3.5 font-medium text-cuero">{{ guest.full_name }}</td>
                                <td class="px-3 py-3.5 text-center text-cuero/70">{{ guest.allowed_passes }}</td>
                                <td class="px-3 py-3.5"><StatusBadge :status="guest.rsvp_status" variant="guest" /></td>
                                <td class="px-3 py-3.5 text-center text-cuero/70">
                                    {{ guest.rsvp_status === 'confirmed' ? guest.confirmed_passes : '—' }}
                                </td>
                                <td class="px-3 py-3.5 text-cuero/50 hidden md:table-cell">{{ guest.table_group || '—' }}</td>
                                <td class="px-5 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1">
                                        <button @click="openEdit(guest)" class="p-2 text-cuero/30 hover:text-mezclilla transition-colors rounded-lg hover:bg-mezclilla/5" title="Editar">
                                            <PencilIcon class="w-4 h-4" />
                                        </button>
                                        <ConfirmDeleteModal :message="`¿Eliminar a «${guest.full_name}»? Esta acción no se puede deshacer y perderá su confirmación de asistencia si ya respondió.`" @confirm="doDelete">
                                            <template #default="{ open: openDel }">
                                                <button @click="confirmDelete(guest); openDel()" class="p-2 text-cuero/30 hover:text-red-500 transition-colors rounded-lg hover:bg-red-50" title="Eliminar">
                                                    <TrashIcon class="w-4 h-4" />
                                                </button>
                                            </template>
                                        </ConfirmDeleteModal>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="guests.links?.length > 3" class="px-5 py-3 border-t border-cuero/10 flex items-center justify-between">
                    <span class="text-xs text-cuero/50">
                        {{ guests.from }}–{{ guests.to }} de {{ guests.total }}
                    </span>
                    <div class="flex gap-1">
                        <a v-for="link in guests.links" :key="link.label"
                            :href="link.url ?? '#'"
                            v-html="link.label"
                            :class="[
                                'px-3 py-1.5 rounded-lg text-sm transition-colors',
                                link.active ? 'bg-cuero text-white' : link.url ? 'text-cuero/60 hover:bg-arena' : 'text-cuero/20 cursor-default',
                            ]"
                        ></a>
                    </div>
                </div>
            </div>

            <EmptyState v-else :icon="UserGroupIcon" title="Sin invitados"
                description="Aún no agregas invitados. Empieza agregando uno o importando tu lista desde un archivo CSV."
                :cta-label="'Agregar Invitado'" @click="openCreate" />

            <!-- ── Guest Form Modal ── -->
            <DialogModal :show="showGuestModal" @close="showGuestModal = false">
                <template #title>{{ editingGuest ? 'Editar Invitado' : 'Agregar Invitado' }}</template>
                <template #content>
                    <div class="space-y-4">
                        <div>
                            <InputLabel value="Nombre Completo" />
                            <TextInput v-model="guestForm.full_name" class="w-full mt-1" placeholder="Ej. María García López" />
                            <InputError :message="guestForm.errors.full_name" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Pases Asignados" />
                            <TextInput v-model="guestForm.allowed_passes" type="number" min="1" class="w-24 mt-1" />
                            <InputError :message="guestForm.errors.allowed_passes" class="mt-1" />
                        </div>
                        <div>
                            <InputLabel value="Grupo / Mesa (opcional)" />
                            <TextInput v-model="guestForm.table_group" class="w-full mt-1" placeholder="Ej. Familia López" />
                        </div>

                        <div v-if="editingGuest" class="mt-4 pt-4 border-t border-cuero/10">
                            <p class="text-xs text-cuero/50 mb-2">Estado de confirmación (no editable aquí):</p>
                            <StatusBadge :status="editingGuest.rsvp_status" variant="guest" />
                            <p v-if="editingGuest.rsvp_status !== 'pending'" class="text-xs text-cuero/50 mt-1">
                                {{ editingGuest.rsvp_status === 'confirmed' ? `Confirmó ${editingGuest.confirmed_passes} pase(s)` : 'Declinó la invitación' }}
                                — {{ new Date(editingGuest.rsvp_responded_at).toLocaleDateString('es-MX') }}
                            </p>
                        </div>
                    </div>
                </template>
                <template #footer>
                    <SecondaryButton @click="showGuestModal = false">Cancelar</SecondaryButton>
                    <PrimaryButton @click="saveGuest" :disabled="guestForm.processing" class="ms-3" :class="{ 'opacity-50': guestForm.processing }">
                        {{ guestForm.processing ? 'Guardando...' : 'Guardar' }}
                    </PrimaryButton>
                </template>
            </DialogModal>

            <!-- ── Import CSV Modal ── -->
            <DialogModal :show="showImportModal" @close="showImportModal = false; importResult = null;">
                <template #title>Importar Invitados (CSV)</template>
                <template #content>
                    <div class="space-y-4">
                        <p class="text-sm text-cuero/60">
                            El archivo debe tener las columnas <code class="bg-arena px-1.5 py-0.5 rounded text-cuero">full_name</code> y <code class="bg-arena px-1.5 py-0.5 rounded text-cuero">allowed_passes</code> (opcional, default 1).
                        </p>
                        <div class="border-2 border-dashed border-cuero/20 rounded-xl p-6 text-center">
                            <DocumentTextIcon class="w-8 h-8 text-cuero/30 mx-auto mb-2" />
                            <label class="cursor-pointer text-mezclilla hover:text-mezclilla-light text-sm font-medium">
                                Seleccionar archivo CSV
                                <input type="file" accept=".csv,.txt" class="hidden" @change="onCsvFile" />
                            </label>
                            <p v-if="importForm.csv_file" class="text-cuero/50 text-xs mt-2">{{ importForm.csv_file.name }}</p>
                        </div>
                        <InputError :message="importForm.errors.csv_file" />

                        <div v-if="importResult" class="bg-olivo/10 border border-olivo/20 rounded-xl p-4 text-sm text-olivo">
                            {{ importResult }}
                        </div>
                    </div>
                </template>
                <template #footer>
                    <SecondaryButton @click="showImportModal = false; importResult = null;">Cerrar</SecondaryButton>
                    <PrimaryButton v-if="!importResult" @click="doImport" :disabled="!importForm.csv_file || importForm.processing" class="ms-3" :class="{ 'opacity-50': !importForm.csv_file || importForm.processing }">
                        {{ importForm.processing ? 'Importando...' : 'Importar' }}
                    </PrimaryButton>
                </template>
            </DialogModal>
        </div>
    </AdminLayout>
</template>
```

### 6.4. `resources/js/Pages/Admin/Settings/Edit.vue`

```vue
<script setup>
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import SectionTitle from '@/Components/SectionTitle.vue';
import FormSection from '@/Components/FormSection.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import ActionMessage from '@/Components/ActionMessage.vue';

defineOptions({ layout: AdminLayout });

const props = defineProps({ settings: Object });

const form = useForm({
    _method: 'PUT',
    cover_photo: null,
    our_story: props.settings?.our_story ?? '',
    event_datetime: props.settings?.event_datetime ?? '',
    venue_name: props.settings?.venue_name ?? '',
    venue_address: props.settings?.venue_address ?? '',
    venue_lat: props.settings?.venue_lat ?? '',
    venue_lng: props.settings?.venue_lng ?? '',
    dress_code_description: props.settings?.dress_code_description ?? '',
    dress_code_image: null,
    rsvp_deadline: props.settings?.rsvp_deadline ?? '',
});

const coverPreview = ref(props.settings?.cover_photo_url ?? null);
const dressCodePreview = ref(props.settings?.dress_code_image_url ?? null);

function onCoverChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    form.cover_photo = file;
    coverPreview.value = URL.createObjectURL(file);
}

function onDressCodeImageChange(e) {
    const file = e.target.files[0];
    if (!file) return;
    form.dress_code_image = file;
    dressCodePreview.value = URL.createObjectURL(file);
}

function submit() {
    form.post(route('admin.settings.update'), {
        preserveScroll: true,
        forceFormData: true,
    });
}
</script>

<template>
    <AdminLayout title="Configuración del Sitio">
        <div class="max-w-4xl mx-auto">
            <h1 class="font-slab text-2xl text-cuero mb-8">Configuración del Sitio</h1>

            <div class="space-y-8">
                <!-- Foto de Portada -->
                <FormSection @submitted="submit">
                    <template #title>Foto de Portada</template>
                    <template #description>La imagen principal que aparece en el Hero de la landing.</template>
                    <template #form>
                        <div class="col-span-6">
                            <div class="relative group rounded-2xl overflow-hidden bg-arena-dark/50 border border-cuero/10 mb-4"
                                :class="coverPreview ? 'h-56' : 'h-40'">
                                <img v-if="coverPreview" :src="coverPreview" class="w-full h-full object-cover" />
                                <div v-else class="flex items-center justify-center h-full text-cuero/30">
                                    <span class="text-sm">Sin foto de portada</span>
                                </div>
                                <div class="absolute inset-0 bg-cuero/0 group-hover:bg-cuero/30 transition-all flex items-center justify-center">
                                    <label class="cursor-pointer bg-white/90 hover:bg-white text-cuero px-4 py-2 rounded-xl text-sm font-medium opacity-0 group-hover:opacity-100 transition-all shadow-lg">
                                        Cambiar foto
                                        <input type="file" accept="image/*" class="hidden" @change="onCoverChange" />
                                    </label>
                                </div>
                            </div>
                            <InputError :message="form.errors.cover_photo" class="mt-2" />
                        </div>
                    </template>
                </FormSection>

                <!-- Información General -->
                <FormSection @submitted="submit">
                    <template #title>Información General</template>
                    <template #description>Fecha, lugar e historia de los novios.</template>
                    <template #form>
                        <!-- Historia -->
                        <div class="col-span-6">
                            <InputLabel value="Nuestra Historia" />
                            <textarea v-model="form.our_story" rows="6"
                                class="w-full mt-1 rounded-xl border-cuero/20 focus:border-dorado focus:ring-dorado/20"
                                placeholder="Escribe la historia de cómo se conocieron..."></textarea>
                            <InputError :message="form.errors.our_story" class="mt-1" />
                        </div>

                        <!-- Fecha y hora -->
                        <div class="col-span-6 sm:col-span-3">
                            <InputLabel value="Fecha y Hora del Evento" />
                            <input type="datetime-local" v-model="form.event_datetime"
                                class="w-full mt-1 rounded-xl border-cuero/20 focus:border-dorado focus:ring-dorado/20 text-cuero" />
                            <InputError :message="form.errors.event_datetime" class="mt-1" />
                        </div>

                        <!-- RSVP deadline -->
                        <div class="col-span-6 sm:col-span-3">
                            <InputLabel value="Fecha Límite RSVP" />
                            <input type="date" v-model="form.rsvp_deadline"
                                class="w-full mt-1 rounded-xl border-cuero/20 focus:border-dorado focus:ring-dorado/20 text-cuero" />
                            <InputError :message="form.errors.rsvp_deadline" class="mt-1" />
                        </div>

                        <!-- Venue name -->
                        <div class="col-span-6 sm:col-span-3">
                            <InputLabel value="Nombre del Lugar" />
                            <TextInput v-model="form.venue_name" class="w-full mt-1" />
                            <InputError :message="form.errors.venue_name" class="mt-1" />
                        </div>

                        <!-- Venue address -->
                        <div class="col-span-6 sm:col-span-3">
                            <InputLabel value="Dirección" />
                            <TextInput v-model="form.venue_address" class="w-full mt-1" />
                            <InputError :message="form.errors.venue_address" class="mt-1" />
                        </div>

                        <!-- Lat -->
                        <div class="col-span-6 sm:col-span-3">
                            <InputLabel value="Latitud" />
                            <TextInput v-model="form.venue_lat" class="w-full mt-1" placeholder="Ej. 20.6597" />
                            <InputError :message="form.errors.venue_lat" class="mt-1" />
                        </div>

                        <!-- Lng -->
                        <div class="col-span-6 sm:col-span-3">
                            <InputLabel value="Longitud" />
                            <TextInput v-model="form.venue_lng" class="w-full mt-1" placeholder="Ej. -103.3496" />
                            <InputError :message="form.errors.venue_lng" class="mt-1" />
                        </div>
                    </template>
                </FormSection>

                <!-- Código de Vestimenta -->
                <FormSection @submitted="submit">
                    <template #title>Código de Vestimenta</template>
                    <template #description>Texto e imagen de referencia para los invitados.</template>
                    <template #form>
                        <div class="col-span-6">
                            <InputLabel value="Descripción" />
                            <textarea v-model="form.dress_code_description" rows="4"
                                class="w-full mt-1 rounded-xl border-cuero/20 focus:border-dorado focus:ring-dorado/20"
                                placeholder="Describe el código de vestimenta..."></textarea>
                            <InputError :message="form.errors.dress_code_description" class="mt-1" />
                        </div>
                        <div class="col-span-6">
                            <InputLabel value="Imagen de referencia (opcional)" class="mb-2" />
                            <div class="flex items-center gap-4">
                                <img v-if="dressCodePreview" :src="dressCodePreview" class="h-24 w-24 rounded-xl object-cover border border-cuero/10" />
                                <label class="cursor-pointer bg-arena hover:bg-arena-dark text-cuero px-4 py-2 rounded-xl text-sm font-medium border border-cuero/20 transition-colors">
                                    {{ dressCodePreview ? 'Cambiar imagen' : 'Subir imagen' }}
                                    <input type="file" accept="image/*" class="hidden" @change="onDressCodeImageChange" />
                                </label>
                            </div>
                            <InputError :message="form.errors.dress_code_image" class="mt-1" />
                        </div>
                    </template>
                </FormSection>
            </div>

            <!-- Save button -->
            <div class="flex items-center gap-4 mt-8 pb-12">
                <PrimaryButton @click="submit" :disabled="form.processing" :class="{ 'opacity-50': form.processing }">
                    {{ form.processing ? 'Guardando...' : 'Guardar Cambios' }}
                </PrimaryButton>
                <ActionMessage :on="form.recentlySuccessful" class="ms-3">¡Guardado!</ActionMessage>
            </div>
        </div>
    </AdminLayout>
</template>
```

### 6.5. `resources/js/Pages/Admin/Dashboard.vue/` — ⚠️ EMPTY DIRECTORY

The path `resources/js/Pages/Admin/Dashboard.vue` is an **empty directory** (not a `.vue` file). The `DashboardController` calls `Inertia::render('Admin/Dashboard', ...)`, which Inertia's `resolvePageComponent` will try to resolve as `./Pages/Admin/Dashboard.vue`. Since this is a directory, the resolution will fail silently or fall back to an Index.vue inside it (which doesn't exist either).

---

## 7. Routes Analysis: `routes/web.php`

```php
<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\FaqController as AdminFaqController;
use App\Http\Controllers\Admin\GalleryController as AdminGalleryController;
use App\Http\Controllers\Admin\GuestController as AdminGuestController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\RsvpController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ─── Rutas públicas ───────────────────────────────────────────────

Route::get('/', [PageController::class, 'home'])->name('home');

// RSVP (confirmación de asistencia) — público, sin login
Route::get('/rsvp/buscar', [RsvpController::class, 'search'])->name('rsvp.search');
Route::post('/rsvp/{guest}/confirmar', [RsvpController::class, 'confirm'])
    ->name('rsvp.confirm')
    ->middleware('throttle:10,1');

// Galería — subida pública de fotos
Route::post('/galeria', [GalleryController::class, 'store'])
    ->name('gallery.store')
    ->middleware('throttle:5,1');

// ─── Rutas autenticadas (Jetstream) ───────────────────────────────

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {

    // Dashboard (redirige o sirve como entrada al panel admin si es admin)
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    // ─── Panel Admin (solo admin) ─────────────────────────────────
    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {

        // Dashboard
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        // Configuración del sitio
        Route::get('/configuracion', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('/configuracion', [SettingController::class, 'update'])->name('settings.update');

        // Invitados
        Route::get('/invitados', [AdminGuestController::class, 'index'])->name('guests.index');
        Route::post('/invitados', [AdminGuestController::class, 'store'])->name('guests.store');
        Route::post('/invitados/importar', [AdminGuestController::class, 'import'])->name('guests.import');
        Route::put('/invitados/{guest}', [AdminGuestController::class, 'update'])->name('guests.update');
        Route::delete('/invitados/{guest}', [AdminGuestController::class, 'destroy'])->name('guests.destroy');

        // FAQs
        Route::get('/faqs', [AdminFaqController::class, 'index'])->name('faqs.index');
        Route::post('/faqs', [AdminFaqController::class, 'store'])->name('faqs.store');
        Route::put('/faqs/{faq}', [AdminFaqController::class, 'update'])->name('faqs.update');
        Route::delete('/faqs/{faq}', [AdminFaqController::class, 'destroy'])->name('faqs.destroy');

        // Galería (moderación)
        Route::get('/galeria', [AdminGalleryController::class, 'index'])->name('gallery.index');
        Route::patch('/galeria/{photo}/aprobar', [AdminGalleryController::class, 'approve'])->name('gallery.approve');
        Route::patch('/galeria/{photo}/rechazar', [AdminGalleryController::class, 'reject'])->name('gallery.reject');
        Route::delete('/galeria/{photo}', [AdminGalleryController::class, 'destroy'])->name('gallery.destroy');
    });
});
```

**Route findings:**
- No duplicate route definitions.
- No controller method registered twice.
- No `Inertia::render()` called twice for the same route.
- The `/admin` route hits `DashboardController@index` which renders `Inertia::render('Admin/Dashboard', ...)` — this is the page whose Vue file is MISSING (empty directory).

---

## 8. Versions & HMR / Double-Mount Analysis

### 8.1. Versions from `package.json`

| Package | Version |
|---------|---------|
| `@inertiajs/vue3` | `^2.0` |
| `vue` | `^3.3.13` |
| `vite` | `^7.0.7` |
| `@vitejs/plugin-vue` | `^6.0.4` |
| `laravel-vite-plugin` | `^2.0.0` |
| `tailwindcss` | `^3.4.0` |
| `@heroicons/vue` | `^2.2.0` |

### 8.2. `vite.config.js`

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';

export default defineConfig({
    plugins: [
        laravel({
            input: 'resources/js/app.js',
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
});
```

### 8.3. `resources/views/app.blade.php`

```html
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title inertia>{{ config('app.name', 'Laravel') }}</title>
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Arvo:ital,wght@0,400;0,700;1,400&display=swap" rel="stylesheet" />
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
```

### 8.4. HMR / Double Mount Analysis

| Check | Result |
|-------|--------|
| `createApp` called more than once? | ❌ No — only one `createApp` in `app.js` |
| `app.mount` called more than once? | ❌ No — only one `.mount(el)` |
| `import.meta.hot` handling? | ❌ Not present — no HMR accept handlers |
| `@vite` dual entry (`app.js` + page component)? | ⚠️ YES — `app.blade.php` includes BOTH `app.js` AND the page component as Vite entries. In **development mode**, this can cause the page component to be loaded as an additional module. Combined with `import.meta.glob('./Pages/**/*.vue')` in `app.js`, the same `.vue` file could theoretically be instantiated twice — once through the glob/dynamic import chain and once as a direct Vite entry point. However, in practice, ES modules are cached after the first import, so this typically does NOT cause double-rendering. In production builds, Vite tree-shakes and bundles everything into a single entry, so this is safe. |

---

## 9. Summary of Findings

### 🔴 CRITICAL — Root Cause of Double Rendering

**Every admin page has BOTH `defineOptions({ layout: AdminLayout })` AND a `<AdminLayout>` wrapper in the template.** This means `AdminLayout` is applied twice:

```
Request → Inertia resolves page component
         → Inertia wraps it in AdminLayout (via defineOptions)
            → AdminLayout renders: sidebar + topbar + <slot/>
               → <slot/> contains the page's own template
                  → Template starts with <AdminLayout>
                     → AdminLayout renders AGAIN: sidebar + topbar + <slot/>
                        → <slot/> contains the actual content
```

**Fix:** Choose ONE approach per page:
- **Option A (recommended):** Keep `defineOptions({ layout: AdminLayout })` and remove the `<AdminLayout>` wrapper from the template. Use a plain `<div>` (or fragment) as the root element.
- **Option B:** Remove `defineOptions({ layout: AdminLayout })` and keep the `<AdminLayout>` wrapper in the template.

### 🟡 CRITICAL — Missing Dashboard Page

`resources/js/Pages/Admin/Dashboard.vue` is an **empty directory**, not a file. The `DashboardController` renders `Admin/Dashboard` but there is no corresponding `.vue` file. This page will fail to load.

**Fix:** Delete the empty `Dashboard.vue` directory and create a proper `Dashboard.vue` file.

### 🟢 Minor — Dual Vite Entry in `app.blade.php`

The `@vite` directive lists both `app.js` and the page component. In modern Laravel + Inertia setups, `app.js` alone (with `import.meta.glob`) is sufficient. This is not directly causing the double-render bug but is worth cleaning up.

### 🟢 Minor — Empty `Partials/` Directories

Both `Faqs/Partials/` and `Guests/Partials/` are empty directories with no files. They don't cause issues but are unnecessary.

---

## 10. Recommended Fix Plan

1. **Fix double rendering on all 4 admin pages** by choosing ONE layout application method (remove either `defineOptions` or the `<AdminLayout>` wrapper).
2. **Delete `Dashboard.vue/` directory** and create a proper `Dashboard.vue` file with the admin dashboard page.
3. **(Optional)** Simplify `app.blade.php` to only include `resources/js/app.js` in the `@vite` directive.
4. **Rebuild assets** with `npm run build`.
