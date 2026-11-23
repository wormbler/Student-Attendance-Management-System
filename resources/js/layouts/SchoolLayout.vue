<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const page = usePage();
const user = computed(() => (page.props as any).auth?.user);
const role = computed(() => user.value?.role);

const items = computed(() => {
    if (role.value === 'student') {
        return [
            { title: 'My Attendance', icon: 'mdi-clipboard-check', href: '/my-attendance' },
            { title: 'Change Password', icon: 'mdi-lock', href: '/change-password' },
        ];
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
        { title: 'Change Password', icon: 'mdi-lock', href: '/change-password' },
    ];
});

const isActive = (href: string) =>
    href === '/school' ? page.url === '/school' : page.url.startsWith(href);

const logout = () => router.post('/logout');

// One-time generated password popup
const generated = computed(() => (page.props as any).generated);
const dialog = ref(false);
const copied = ref(false);

watch(
    generated,
    (v) => {
        if (v) {
            dialog.value = true;
            copied.value = false;
        }
    },
    { immediate: true },
);

const copy = async () => {
    await navigator.clipboard.writeText(`${generated.value.email} / ${generated.value.password}`);
    copied.value = true;
};
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

        <v-dialog v-model="dialog" max-width="460" persistent>
            <v-card v-if="generated" title="Temporary password">
                <v-card-text>
                    <div class="mb-3">Give these login details to <strong>{{ generated.name }}</strong>.</div>
                    <div><span class="text-medium-emphasis">Email:</span> {{ generated.email }}</div>
                    <div class="text-h6 my-2">
                        <span class="text-body-2 text-medium-emphasis">Password:</span> {{ generated.password }}
                    </div>
                    <v-alert type="warning" density="compact" class="mt-3">
                        This is shown only once. The user must change it at first login.
                    </v-alert>
                </v-card-text>
                <v-card-actions>
                    <v-btn prepend-icon="mdi-content-copy" @click="copy">{{ copied ? 'Copied' : 'Copy' }}</v-btn>
                    <v-spacer />
                    <v-btn color="primary" @click="dialog = false">Done</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </v-app>
</template>