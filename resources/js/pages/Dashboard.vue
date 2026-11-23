<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import SchoolLayout from '@/layouts/SchoolLayout.vue';

defineProps<{
    counts: Record<string, number>;
    today: Record<string, number>;
    sessionsToday: number;
    rate30: number | null;
    trend: { date: string; total: number; rate: number }[];
    low: { id: number; student_number: string; name: string; total: number; rate: number }[];
}>();

const statuses = ['Present', 'Absent', 'Late', 'Excused'];
const rateColor = (r: number) => (r >= 90 ? 'success' : r >= 75 ? 'warning' : 'error');
</script>

<template>
    <Head title="Dashboard" />
    <SchoolLayout>
        <h1 class="text-h5 mb-4">Dashboard</h1>

        <v-row>
            <v-col v-for="(value, label) in counts" :key="label" cols="6" md="4" lg="2">
                <v-card>
                    <v-card-text>
                        <div class="text-medium-emphasis">{{ label }}</div>
                        <div class="text-h4">{{ value }}</div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <v-row class="mt-2">
            <v-col cols="12" md="6">
                <v-card>
                    <v-card-title>Today</v-card-title>
                    <v-card-text>
                        <div class="text-medium-emphasis mb-2">Sessions taken: {{ sessionsToday }}</div>
                        <v-row>
                            <v-col v-for="s in statuses" :key="s" cols="6">
                                <div class="text-medium-emphasis">{{ s }}</div>
                                <div class="text-h5">{{ today[s] ?? 0 }}</div>
                            </v-col>
                        </v-row>
                    </v-card-text>
                </v-card>
            </v-col>

            <v-col cols="12" md="6">
                <v-card>
                    <v-card-title>Attendance rate (last 30 days)</v-card-title>
                    <v-card-text>
                        <div v-if="rate30 !== null" class="text-h3" :class="`text-${rateColor(rate30)}`">{{ rate30 }}%</div>
                        <div v-else class="text-medium-emphasis">No records yet.</div>
                        <div class="text-caption text-medium-emphasis mt-2">Present and Late count as attended.</div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>

        <v-row class="mt-2">
            <v-col cols="12" md="6">
                <v-card>
                    <v-card-title>Last 7 days</v-card-title>
                    <v-card-text>
                        <div v-if="!trend.length" class="text-medium-emphasis">No attendance in the last 7 days.</div>
                        <div v-for="t in trend" :key="t.date" class="mb-3">
                            <div class="d-flex justify-space-between text-body-2">
                                <span>{{ t.date }}</span>
                                <span>{{ t.rate }}% ({{ t.total }} records)</span>
                            </div>
                            <v-progress-linear :model-value="t.rate" :color="rateColor(t.rate)" height="10" rounded />
                        </div>
                    </v-card-text>
                </v-card>
            </v-col>

            <v-col cols="12" md="6">
                <v-card>
                    <v-card-title>Low attendance (below 75%)</v-card-title>
                    <v-table density="compact">
                        <thead><tr><th>Student</th><th>Records</th><th>Rate</th></tr></thead>
                        <tbody>
                            <tr v-for="s in low" :key="s.id">
                                <td>{{ s.name }} <span class="text-medium-emphasis">({{ s.student_number }})</span></td>
                                <td>{{ s.total }}</td>
                                <td><v-chip :color="rateColor(s.rate)" size="small">{{ s.rate }}%</v-chip></td>
                            </tr>
                            <tr v-if="!low.length">
                                <td colspan="3" class="text-center text-medium-emphasis pa-4">
                                    No students below 75% (needs at least 3 records).
                                </td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-card>
            </v-col>
        </v-row>
    </SchoolLayout>
</template>