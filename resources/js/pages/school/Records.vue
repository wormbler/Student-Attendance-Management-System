<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import SchoolLayout from '@/layouts/SchoolLayout.vue';

defineProps<{
    records: { id: number; date: string; student: string; subject: string; status: string }[];
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
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in records" :key="r.id">
                        <td>{{ r.date }}</td>
                        <td>{{ r.student }}</td>
                        <td>{{ r.subject }}</td>
                        <td><v-chip :color="color(r.status)" size="small">{{ r.status }}</v-chip></td>
                    </tr>
                </tbody>
            </v-table>
        </v-card>
    </SchoolLayout>
</template>