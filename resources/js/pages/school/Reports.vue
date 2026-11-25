<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';
import SchoolLayout from '@/layouts/SchoolLayout.vue';

const props = defineProps<{
    rows: {
        id: number;
        student_number: string;
        name: string;
        present: number;
        absent: number;
        late: number;
        excused: number;
        total: number;
        rate: number;
    }[];
    sections: { id: number; section_name: string }[];
    subjects: { id: number; subject_name: string }[];
    filters: {
        section_id: number | null;
        subject_id: number | null;
        from: string | null;
        to: string | null;
    };
}>();

const f = reactive({ ...props.filters });

const params = () => {
    const p: Record<string, string> = {};
    Object.entries(f).forEach(([k, v]) => {
        if (v !== null && v !== '' && v !== undefined) {
            p[k] = String(v);
        }
    });
    return p;
};

const apply = () => router.get('/school/reports', params(), { preserveState: true, preserveScroll: true });

// Filters apply automatically whenever one changes
watch(f, apply, { deep: true });

const reset = () => {
    f.section_id = null;
    f.subject_id = null;
    f.from = null;
    f.to = null;
};

const exportCsv = () => {
    window.location.href = '/school/reports/export?' + new URLSearchParams(params()).toString();
};

const print = () => window.print();

const rateColor = (r: number) => (r >= 90 ? 'success' : r >= 75 ? 'warning' : 'error');
</script>

<template>
    <Head title="Reports" />
    <SchoolLayout>
        <h1 class="text-h5 mb-4 d-print-none">Attendance Report</h1>

        <v-card class="mb-6 d-print-none">
            <v-card-text>
                <v-row>
                    <v-col cols="12" md="3">
                        <v-select
                            v-model="f.section_id"
                            :items="sections"
                            item-title="section_name"
                            item-value="id"
                            label="Section"
                            clearable
                        />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-select
                            v-model="f.subject_id"
                            :items="subjects"
                            item-title="subject_name"
                            item-value="id"
                            label="Subject"
                            clearable
                        />
                    </v-col>
                    <v-col cols="6" md="2">
                        <v-text-field v-model="f.from" type="date" label="From" />
                    </v-col>
                    <v-col cols="6" md="2">
                        <v-text-field v-model="f.to" type="date" label="To" />
                    </v-col>
                    <v-col cols="12" md="2" class="d-flex ga-2">
                        <v-btn color="primary" @click="apply">Apply</v-btn>
                        <v-btn variant="tonal" @click="reset">Reset</v-btn>
                    </v-col>
                </v-row>
                <div class="d-flex ga-2">
                    <v-btn prepend-icon="mdi-file-delimited" variant="tonal" @click="exportCsv">
                        Export CSV
                    </v-btn>
                    <v-btn prepend-icon="mdi-printer" variant="tonal" @click="print">
                        Print
                    </v-btn>
                </div>
            </v-card-text>
        </v-card>

        <v-card>
            <v-table density="comfortable">
                <thead>
                    <tr>
                        <th>Student no.</th>
                        <th>Name</th>
                        <th>Present</th>
                        <th>Absent</th>
                        <th>Late</th>
                        <th>Excused</th>
                        <th>Total</th>
                        <th>Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="r in rows" :key="r.id">
                        <td>{{ r.student_number }}</td>
                        <td>{{ r.name }}</td>
                        <td>{{ r.present }}</td>
                        <td>{{ r.absent }}</td>
                        <td>{{ r.late }}</td>
                        <td>{{ r.excused }}</td>
                        <td>{{ r.total }}</td>
                        <td>
                            <v-chip :color="rateColor(r.rate)" size="small">{{ r.rate }}%</v-chip>
                        </td>
                    </tr>
                    <tr v-if="!rows.length">
                        <td colspan="8" class="text-center text-medium-emphasis pa-6">
                            No attendance records match these filters.
                        </td>
                    </tr>
                </tbody>
            </v-table>
        </v-card>
    </SchoolLayout>
</template>