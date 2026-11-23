<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import SchoolLayout from '@/layouts/SchoolLayout.vue';

defineProps<{
    linked: boolean;
    student: string | null;
    section: string | null;
    summary: Record<string, number>;
    classes: {
        id: number; subject: string; code: string; teacher: string;
        day: string; time: string; room: string | null;
        present: number; absent: number; late: number; excused: number;
    }[];
    records: { id: number; date: string; subject: string; status: string }[];
}>();

const statuses = ['Present', 'Absent', 'Late', 'Excused'];
const color = (s: string) =>
    ({ Present: 'success', Absent: 'error', Late: 'warning', Excused: 'info' })[s] ?? 'default';
</script>

<template>
    <Head title="My Attendance" />
    <SchoolLayout>
        <h1 class="text-h5 mb-1">
            My Attendance
            <span v-if="student" class="text-medium-emphasis">({{ student }})</span>
        </h1>
        <div v-if="section" class="text-medium-emphasis mb-4">Section: {{ section }}</div>

        <v-alert
            v-if="!linked"
            type="warning"
            text="Your account is not linked to a student record yet. Please contact the admin."
        />

        <template v-else>
            <v-row class="mb-2">
                <v-col v-for="s in statuses" :key="s" cols="6" md="3">
                    <v-card>
                        <v-card-text>
                            <div class="text-medium-emphasis">{{ s }}</div>
                            <div class="text-h4">{{ summary[s] ?? 0 }}</div>
                        </v-card-text>
                    </v-card>
                </v-col>
            </v-row>

            <h2 class="text-h6 mt-4 mb-2">My Classes</h2>
            <v-card class="mb-6">
                <v-table density="comfortable">
                    <thead>
                        <tr>
                            <th>Subject</th><th>Teacher</th><th>Day</th><th>Time</th><th>Room</th>
                            <th>Present</th><th>Absent</th><th>Late</th><th>Excused</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="c in classes" :key="c.id">
                            <td>{{ c.subject }} <span class="text-medium-emphasis">({{ c.code }})</span></td>
                            <td>{{ c.teacher }}</td>
                            <td>{{ c.day }}</td>
                            <td>{{ c.time }}</td>
                            <td>{{ c.room }}</td>
                            <td>{{ c.present }}</td>
                            <td>{{ c.absent }}</td>
                            <td>{{ c.late }}</td>
                            <td>{{ c.excused }}</td>
                        </tr>
                        <tr v-if="!classes.length">
                            <td colspan="9" class="text-center text-medium-emphasis pa-6">
                                No classes are scheduled for your section yet.
                            </td>
                        </tr>
                    </tbody>
                </v-table>
            </v-card>

            <h2 class="text-h6 mb-2">Attendance History</h2>
            <v-card>
                <v-table density="comfortable">
                    <thead><tr><th>Date</th><th>Subject</th><th>Status</th></tr></thead>
                    <tbody>
                        <tr v-for="r in records" :key="r.id">
                            <td>{{ r.date }}</td>
                            <td>{{ r.subject }}</td>
                            <td><v-chip :color="color(r.status)" size="small">{{ r.status }}</v-chip></td>
                        </tr>
                        <tr v-if="!records.length">
                            <td colspan="3" class="text-center text-medium-emphasis pa-6">No attendance records yet.</td>
                        </tr>
                    </tbody>
                </v-table>
            </v-card>
        </template>
    </SchoolLayout>
</template>