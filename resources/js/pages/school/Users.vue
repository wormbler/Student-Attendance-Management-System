<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import SchoolLayout from '@/layouts/SchoolLayout.vue';

const props = defineProps<{
    users: { id: number; name: string; email: string; role: string; is_active: boolean; linked: string | null }[];
}>();

const page = usePage();
const me = computed(() => (page.props as any).auth?.user?.id);
const error = computed(() => (page.props as any).errors?.user);

const search = ref('');
const filtered = computed(() => {
    const q = search.value.toLowerCase();
    return props.users.filter((u) => !q || u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q));
});

const changeRole = (id: number, role: string) =>
    router.put(`/school/users/${id}/role`, { role }, { preserveScroll: true });

const toggle = (id: number) => router.put(`/school/users/${id}/status`, {}, { preserveScroll: true });

const resetPassword = (u: { id: number; name: string }) => {
    if (confirm(`Generate a new temporary password for ${u.name}?`)) {
        router.put(`/school/users/${u.id}/password`, {}, { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Users" />
    <SchoolLayout>
        <h1 class="text-h5 mb-4">User Accounts</h1>

        <v-alert v-if="error" type="error" class="mb-4" :text="error" />

        <v-text-field v-model="search" label="Search name or email" prepend-inner-icon="mdi-magnify" class="mb-2" style="max-width: 360px" />

        <v-card>
            <v-table density="comfortable">
                <thead>
                    <tr><th>Name</th><th>Email</th><th>Role</th><th>Linked to</th><th>Active</th><th></th></tr>
                </thead>
                <tbody>
                    <tr v-for="u in filtered" :key="u.id">
                        <td>{{ u.name }}</td>
                        <td>{{ u.email }}</td>
                        <td style="width: 160px">
                            <v-select
                                :model-value="u.role"
                                :items="['admin', 'teacher', 'student']"
                                density="compact"
                                hide-details
                                :disabled="u.id === me"
                                @update:model-value="(v: string) => changeRole(u.id, v)"
                            />
                        </td>
                        <td>{{ u.linked ?? '—' }}</td>
                        <td>
                            <v-switch
                                :model-value="u.is_active"
                                color="success"
                                density="compact"
                                hide-details
                                :disabled="u.id === me"
                                @update:model-value="toggle(u.id)"
                            />
                        </td>
                        <td class="text-right">
                            <v-btn icon="mdi-lock-reset" size="small" variant="text" title="Generate temporary password" @click="resetPassword(u)" />
                        </td>
                    </tr>
                </tbody>
            </v-table>
        </v-card>
    </SchoolLayout>
</template>