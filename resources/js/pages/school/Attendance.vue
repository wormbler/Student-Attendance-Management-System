<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import SchoolLayout from '@/layouts/SchoolLayout.vue';

const props = defineProps<{
    schedules: { id: number; label: string }[];
    students: { id: number; student_number: string; name: string; status: string }[];
    filters: { schedule_id: number | null; date: string };
}>();

const page = usePage();
const errors = computed(() => (page.props as any).errors ?? {});

const scheduleId = ref(props.filters.schedule_id);
const date = ref(props.filters.date);
const reason = ref('');
const rows = ref(props.students.map((s) => ({ ...s })));
const saving = ref(false);
const saved = ref(false);

watch(
    () => props.students,
    (value) => {
        rows.value = value.map((s) => ({ ...s }));
    },
);

const statuses = ['Present', 'Absent', 'Late', 'Excused'];

const load = () => {
    if (!scheduleId.value) return;
    reason.value = '';
    router.get(
        '/school/attendance',
        { schedule_id: scheduleId.value, date: date.value },
        { preserveState: true, preserveScroll: true },
    );
};

const markAll = (status: string) => rows.value.forEach((r) => (r.status = status));

const save = () => {
    saving.value = true;
    router.post(
        '/school/attendance',
        {
            schedule_id: scheduleId.value,
            date: date.value,
            reason: reason.value,
            records: rows.value.map((r) => ({ student_id: r.id, status: r.status })),
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                saved.value = true;
                reason.value = '';
            },
            onFinish: () => (saving.value = false),
        },
    );
};
</script>

<template>
    <Head title="Take Attendance" />
    <SchoolLayout>
        <h1 class="text-h5 mb-4">Take Attendance</h1>

        <v-card class="mb-6">
            <v-card-text>
                <v-row>
                    <v-col cols="12" md="7">
                        <v-select
                            v-model="scheduleId"
                            :items="schedules"
                            item-title="label"
                            item-value="id"
                            label="Class schedule"
                            @update:model-value="load"
                        />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-text-field v-model="date" type="date" label="Date" @change="load" />
                    </v-col>
                </v-row>
            </v-card-text>
        </v-card>

        <v-alert v-if="errors.attendance" type="error" class="mb-4" :text="errors.attendance" />

        <v-card v-if="rows.length">
            <v-card-text>
                <div class="d-flex ga-2 flex-wrap mb-4">
                    <v-btn size="small" variant="tonal" @click="markAll('Present')">All present</v-btn>
                    <v-btn size="small" variant="tonal" @click="markAll('Absent')">All absent</v-btn>
                    <v-spacer />
                    <v-btn color="primary" :loading="saving" @click="save">Save attendance</v-btn>
                </div>
                <v-text-field
                    v-model="reason"
                    label="Reason for changes (required only when editing already-saved attendance)"
                    :error-messages="errors.reason"
                    density="compact"
                />
            </v-card-text>
            <v-table density="comfortable">
                <thead>
                    <tr>
                        <th>Student no.</th>
                        <th>Name</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in rows" :key="r.id">
                        <td>{{ r.student_number }}</td>
                        <td>{{ r.name }}</td>
                        <td>
                            <v-btn-toggle v-model="r.status" mandatory density="compact" color="primary" divided>
                                <v-btn v-for="s in statuses" :key="s" :value="s" size="small">{{ s }}</v-btn>
                            </v-btn-toggle>
                        </td>
                    </tr>
                </tbody>
            </v-table>
        </v-card>

        <v-alert v-else-if="scheduleId" type="info" text="No students in this section." />
        <v-alert v-else type="info" text="Pick a class schedule and a date to begin." />

        <v-snackbar v-model="saved" color="success" :timeout="2500">Attendance saved</v-snackbar>
    </SchoolLayout>
</template>