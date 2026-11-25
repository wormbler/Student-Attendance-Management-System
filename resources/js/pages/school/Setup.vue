<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import SchoolLayout from '@/layouts/SchoolLayout.vue';
import { useRequiredFields } from '@/composables/useRequiredFields';

defineProps<{
    sections: { id: number; section_name: string; year_level: string; school_year: string }[];
    teachers: { id: number; employee_number: string; first_name: string; last_name: string; user?: { email: string } }[];
}>();

const sectionForm = useForm({ section_name: '', year_level: '', school_year: '2026-2027' });
const sectionReq = useRequiredFields(sectionForm, {
    section_name: 'Section name',
    year_level: 'Year level',
    school_year: 'School year',
});
const addSection = () => {
    if (!sectionReq.check()) return;
    sectionForm.post('/school/setup/sections', {
        onSuccess: () => {
            sectionForm.reset();
            sectionReq.reset();
        },
    });
};

const teacherForm = useForm({ first_name: '', last_name: '', email: '' });
const teacherReq = useRequiredFields(teacherForm, {
    first_name: 'First name',
    last_name: 'Last name',
    email: 'Email',
});
const addTeacher = () => {
    if (!teacherReq.check()) return;
    teacherForm.post('/school/setup/teachers', {
        onSuccess: () => {
            teacherForm.reset();
            teacherReq.reset();
        },
    });
};
</script>

<template>
    <Head title="Sections & Teachers" />
    <SchoolLayout>
        <h1 class="text-h5 mb-4">Sections & Teachers</h1>

        <v-row>
            <v-col cols="12" lg="6">
                <v-card class="mb-4">
                    <v-card-title>Add section</v-card-title>
                    <v-card-text>
                        <v-alert v-if="sectionReq.message.value" type="warning" density="compact" class="mb-3" :text="sectionReq.message.value" />
                        <v-text-field v-model="sectionForm.section_name" label="Section name (e.g. Grade 7 - A)" :error-messages="sectionReq.err('section_name')" />
                        <v-text-field v-model="sectionForm.year_level" label="Year level (e.g. Grade 7)" :error-messages="sectionReq.err('year_level')" />
                        <v-text-field v-model="sectionForm.school_year" label="School year" :error-messages="sectionReq.err('school_year')" />
                        <v-btn color="primary" :loading="sectionForm.processing" @click="addSection">Add section</v-btn>
                    </v-card-text>
                </v-card>
                <v-card>
                    <v-table density="compact">
                        <thead><tr><th>Section</th><th>Year level</th><th>School year</th></tr></thead>
                        <tbody>
                            <tr v-for="s in sections" :key="s.id">
                                <td>{{ s.section_name }}</td><td>{{ s.year_level }}</td><td>{{ s.school_year }}</td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-card>
            </v-col>

            <v-col cols="12" lg="6">
                <v-card class="mb-4">
                    <v-card-title>Add teacher (a temporary password is generated)</v-card-title>
                    <v-card-text>
                        <v-alert v-if="teacherReq.message.value" type="warning" density="compact" class="mb-3" :text="teacherReq.message.value" />
                        <v-text-field v-model="teacherForm.first_name" label="First name" :error-messages="teacherReq.err('first_name')" />
                        <v-text-field v-model="teacherForm.last_name" label="Last name" :error-messages="teacherReq.err('last_name')" />
                        <v-text-field v-model="teacherForm.email" label="Email (login)" :error-messages="teacherReq.err('email')" />
                        <v-btn color="primary" :loading="teacherForm.processing" @click="addTeacher">Add teacher</v-btn>
                    </v-card-text>
                </v-card>
                <v-card>
                    <v-table density="compact">
                        <thead><tr><th>No.</th><th>Name</th><th>Email</th></tr></thead>
                        <tbody>
                            <tr v-for="t in teachers" :key="t.id">
                                <td>{{ t.employee_number }}</td>
                                <td>{{ t.last_name }}, {{ t.first_name }}</td>
                                <td>{{ t.user?.email }}</td>
                            </tr>
                        </tbody>
                    </v-table>
                </v-card>
            </v-col>
        </v-row>
    </SchoolLayout>
</template>