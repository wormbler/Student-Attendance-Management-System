<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import SchoolLayout from '@/layouts/SchoolLayout.vue';

defineProps<{
    records: {
        id: number; date: string; student: string; subject: string; status: string;
        edited: boolean; edited_by: string | null; edited_at: string | null;
    }[];
}>();

const color = (status: string) =>
    ({ Present: 'success', Absent: 'error', Late: 'warning', Excused: 'info' })[status] ?? 'default';
</script>

<template>
    <Head title="Records" />
    <SchoolLayout>
        <h1 class="text-h5 mb-4">Attendance Records (latest 200)</h1>
        <v-card>
            <v-table density="comfortable">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Student</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in records" :key="r.id">
                        <td>{{ r.date }}</td>
                        <td>{{ r.student }}</td>
                        <td>{{ r.subject }}</td>
                        <td><v-chip :color="color(r.status)" size="small">{{ r.status }}</v-chip></td>
                        <td>
                            <v-tooltip v-if="r.edited" location="top">
                                <template #activator="{ props }">
                                    <v-chip v-bind="props" size="x-small" color="warning" variant="tonal" prepend-icon="mdi-pencil">
                                        Edited
                                    </v-chip>
                                </template>
                                Edited by {{ r.edited_by }} on {{ r.edited_at }}
                            </v-tooltip>
                        </td>
                    </tr>
                    <tr v-if="!records.length">
                        <td colspan="5" class="text-center text-medium-emphasis pa-6">No attendance records yet.</td>
                    </tr>
                </tbody>
            </v-table>
        </v-card>
    </SchoolLayout>
</template>