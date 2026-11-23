<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import SchoolLayout from '@/layouts/SchoolLayout.vue';

defineProps<{
    logs: {
        id: number; when: string; who: string | null; action: string;
        student: string; subject: string; date: string;
        old_status: string | null; new_status: string; reason: string | null;
    }[];
}>();

const color = (s: string) =>
    ({ Present: 'success', Absent: 'error', Late: 'warning', Excused: 'info' })[s] ?? 'default';
</script>

<template>
    <Head title="Audit Log" />
    <SchoolLayout>
        <h1 class="text-h5 mb-4">Attendance Audit Log (latest 300)</h1>
        <v-card>
            <v-table density="comfortable">
                <thead>
                    <tr>
                        <th>When</th><th>By</th><th>Action</th><th>Student</th>
                        <th>Subject</th><th>Class date</th><th>Change</th><th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="l in logs" :key="l.id">
                        <td class="text-no-wrap">{{ l.when }}</td>
                        <td>{{ l.who }}</td>
                        <td>{{ l.action }}</td>
                        <td>{{ l.student }}</td>
                        <td>{{ l.subject }}</td>
                        <td>{{ l.date }}</td>
                        <td class="text-no-wrap">
                            <v-chip v-if="l.old_status" :color="color(l.old_status)" size="x-small" class="mr-1">{{ l.old_status }}</v-chip>
                            <span v-if="l.old_status">→</span>
                            <v-chip :color="color(l.new_status)" size="x-small" class="ml-1">{{ l.new_status }}</v-chip>
                        </td>
                        <td>{{ l.reason }}</td>
                    </tr>
                    <tr v-if="!logs.length">
                        <td colspan="8" class="text-center text-medium-emphasis pa-6">No changes logged yet.</td>
                    </tr>
                </tbody>
            </v-table>
        </v-card>
    </SchoolLayout>
</template>