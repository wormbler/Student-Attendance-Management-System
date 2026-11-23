<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const user = computed(() => (page.props as any).auth?.user);
const role = computed(() => user.value?.role);

const items = computed(() => {
    if (role.value === 'student') {
        return [{ title: 'My Attendance', icon: 'mdi-clipboard-check', href: '/my-attendance' }];
    }

    return [
        { title: 'Dashboard', icon: 'mdi-view-dashboard', href: '/school' },
        { title: 'Students', icon: 'mdi-account-school', href: '/school/students' },
        { title: 'Schedules', icon: 'mdi-calendar-clock', href: '/school/schedules' },
        { title: 'Take Attendance', icon: 'mdi-clipboard-check', href: '/school/attendance' },
        { title: 'Records', icon: 'mdi-table', href: '/school/records' },
        { title: 'Reports', icon: 'mdi-chart-box', href: '/school/reports' },
        ...(role.value === 'admin'
            ? [
                  { title: 'Subjects', icon: 'mdi-book-open-variant', href: '/school/subjects' },
                  { title: 'Sections & Teachers', icon: 'mdi-cog', href: '/school/setup' },
                  { title: 'Users', icon: 'mdi-account-key', href: '/school/users' },
                  { title: 'Audit Log', icon: 'mdi-history', href: '/school/audit' },
              ]
            : []),
    ];
});

const isActive = (href: string) =>
    href === '/school' ? page.url === '/school' : page.url.startsWith(href);

const logout = () => router.post('/logout');
</script>

<template>
    <v-app>
        <v-navigation-drawer permanent>
            <v-list-item :title="user?.name" :subtitle="user?.role" class="py-4" />
            <v-divider />
            <v-list nav>
                <v-list-item
                    v-for="item in items"
                    :key="item.href"
                    :title="item.title"
                    :prepend-icon="item.icon"
                    :active="isActive(item.href)"
                    @click="router.visit(item.href)"
                />
            </v-list>
            <template #append>
                <div class="pa-3">
                    <v-btn block variant="tonal" prepend-icon="mdi-logout" @click="logout">Log out</v-btn>
                </div>
            </template>
        </v-navigation-drawer>

        <v-main>
            <v-container fluid class="pa-6">
                <slot />
            </v-container>
        </v-main>
    </v-app>
</template>