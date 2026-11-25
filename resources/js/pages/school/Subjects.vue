<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import SchoolLayout from '@/layouts/SchoolLayout.vue';
import { useRequiredFields } from '@/composables/useRequiredFields';

defineProps<{
    subjects: { id: number; subject_code: string; subject_name: string; units: string; schedules_count: number }[];
}>();

const page = usePage();
const deleteError = computed(() => (page.props as any).errors?.delete);

const form = useForm({ subject_code: '', subject_name: '', units: 3 });

const req = useRequiredFields(form, {
    subject_code: 'Code',
    subject_name: 'Subject name',
    units: 'Units',
});

const add = () => {
    if (!req.check()) return;
    form.post('/school/subjects', {
        onSuccess: () => {
            form.reset();
            req.reset();
        },
    });
};

const remove = (id: number) => {
    if (confirm('Delete this subject?')) {
        router.delete(`/school/subjects/${id}`, { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Subjects" />
    <SchoolLayout>
        <h1 class="text-h5 mb-4">Subjects</h1>

        <v-card class="mb-6">
            <v-card-title>Add subject</v-card-title>
            <v-card-text>
                <v-alert v-if="req.message.value" type="warning" density="compact" class="mb-3" :text="req.message.value" />
                <v-row>
                    <v-col cols="12" md="3">
                        <v-text-field v-model="form.subject_code" label="Code" :error-messages="req.err('subject_code')" />
                    </v-col>
                    <v-col cols="12" md="5">
                        <v-text-field v-model="form.subject_name" label="Subject name" :error-messages="req.err('subject_name')" />
                    </v-col>
                    <v-col cols="12" md="2">
                        <v-text-field v-model="form.units" type="number" label="Units" :error-messages="req.err('units')" />
                    </v-col>
                    <v-col cols="12" md="2">
                        <v-btn color="primary" :loading="form.processing" @click="add">Add</v-btn>
                    </v-col>
                </v-row>
            </v-card-text>
        </v-card>

        <v-alert v-if="deleteError" type="error" class="mb-4" :text="deleteError" />

        <v-card>
            <v-table density="comfortable">
                <thead>
                    <tr><th>Code</th><th>Name</th><th>Units</th><th>Schedules</th><th></th></tr>
                </thead>
                <tbody>
                    <tr v-for="s in subjects" :key="s.id">
                        <td>{{ s.subject_code }}</td>
                        <td>{{ s.subject_name }}</td>
                        <td>{{ s.units }}</td>
                        <td>{{ s.schedules_count }}</td>
                        <td class="text-right">
                            <v-btn icon="mdi-delete" size="small" variant="text" color="error" @click="remove(s.id)" />
                        </td>
                    </tr>
                </tbody>
            </v-table>
        </v-card>
    </SchoolLayout>
</template>