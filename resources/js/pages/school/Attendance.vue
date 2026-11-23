<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import SchoolLayout from '@/layouts/SchoolLayout.vue';

type Row = { id: number; student_number: string; name: string; status: string | null };

const props = defineProps<{
    schedules: { id: number; day: string; label: string }[];
    students: Row[];
    filters: { schedule_id: number | null; date: string };
}>();

const page = usePage();
const errors = computed(() => (page.props as any).errors ?? {});

const scheduleId = ref(props.filters.schedule_id);
const date = ref(props.filters.date);
const selectedDay = computed(() => props.schedules.find((s) => s.id === scheduleId.value)?.day ?? null);

const dayOfDate = (d: string) =>
    new Date(d + 'T00:00:00').toLocaleDateString('en-US', { weekday: 'long' });

const dateMismatch = computed(
    () => !!selectedDay.value && !!date.value && dayOfDate(date.value) !== selectedDay.value,
);

const today = new Date().toLocaleDateString('en-CA'); // YYYY-MM-DD

const reason = ref('');
const rows = ref<Row[]>(props.students.map((s) => ({ ...s })));
const saving = ref(false);
const saved = ref(false);
const triedSave = ref(false);

watch(
    () => props.students,
    (value) => {
        rows.value = value.map((s) => ({ ...s }));
        triedSave.value = false;
    },
);

const statuses = ['Present', 'Absent', 'Late', 'Excused'];
const statusColor: Record<string, string> = {
    Present: 'success',
    Absent: 'error',
    Late: 'warning',
    Excused: 'info',
};

const markedCount = computed(() => rows.value.filter((r) => r.status).length);
const unmarkedCount = computed(() => rows.value.length - markedCount.value);
const allMarked = computed(() => rows.value.length > 0 && unmarkedCount.value === 0);

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
const clearAll = () => rows.value.forEach((r) => (r.status = null));

const save = () => {
    if (dateMismatch.value) return;
    triedSave.value = true;
    if (!allMarked.value) return;

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
                triedSave.value = false;
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
                        <v-text-field v-model="date" type="date" label="Date" :max="today" @change="load" />
                    </v-col>
                </v-row>
            </v-card-text>
        </v-card>

        <v-alert v-if="errors.attendance" type="error" class="mb-4" :text="errors.attendance" />

        <v-alert
            v-if="dateMismatch"
            type="error"
            class="mb-4"
            :text="`This class is scheduled on ${selectedDay}, but ${date} is a ${dayOfDate(date)}. Pick a ${selectedDay}.`"
        />

        <v-alert
            v-if="triedSave && unmarkedCount"
            type="warning"
            class="mb-4"
            :text="`${unmarkedCount} student(s) not marked yet. Mark everyone before saving.`"
        />

        <v-card v-if="rows.length">
            <v-card-text>
                <div class="d-flex ga-2 flex-wrap align-center mb-4">
                    <v-btn size="small" variant="tonal" @click="markAll('Present')">All present</v-btn>
                    <v-btn size="small" variant="tonal" @click="markAll('Absent')">All absent</v-btn>
                    <v-btn size="small" variant="text" @click="clearAll">Clear all</v-btn>

                    <v-chip :color="allMarked ? 'success' : 'warning'" size="small" class="ml-2">
                        Marked {{ markedCount }} of {{ rows.length }}
                    </v-chip>

                    <v-spacer />
                    <v-btn color="primary" :loading="saving" :disabled="dateMismatch" @click="save">
                        Save attendance
                    </v-btn>
                </div>
                <v-progress-linear
                    :model-value="rows.length ? (markedCount / rows.length) * 100 : 0"
                    :color="allMarked ? 'success' : 'warning'"
                    height="6"
                    rounded
                    class="mb-4"
                />
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
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="r in rows"
                        :key="r.id"
                        :class="{ 'bg-red-lighten-5': triedSave && !r.status }"
                    >
                        <td>{{ r.student_number }}</td>
                        <td>{{ r.name }}</td>
                        <td class="text-no-wrap">
                            <v-btn
                                v-for="s in statuses"
                                :key="s"
                                size="small"
                                class="mr-1"
                                :variant="r.status === s ? 'flat' : 'outlined'"
                                :color="r.status === s ? statusColor[s] : undefined"
                                @click="r.status = s"
                            >
                                {{ s }}
                            </v-btn>
                        </td>
                        <td>
                            <v-chip v-if="!r.status" size="x-small" color="warning" variant="tonal">
                                Not marked
                            </v-chip>
                            <v-icon v-else icon="mdi-check-circle" color="success" size="small" />
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