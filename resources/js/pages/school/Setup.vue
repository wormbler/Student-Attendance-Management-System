<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import SchoolLayout from '@/layouts/SchoolLayout.vue';

defineProps<{
    sections: { id: number; section_name: string; year_level: string; school_year: string }[];
    teachers: { id: number; employee_number: string; first_name: string; last_name: string; user?: { email: string } }[];
}>();

const sectionForm = useForm({ section_name: '', year_level: '', school_year: '2026-2027' });
const addSection = () => sectionForm.post('/school/setup/sections', { onSuccess: () => sectionForm.reset() });

const teacherForm = useForm({ employee_number: '', first_name: '', last_name: '', email: '', password: '' });
const addTeacher = () => teacherForm.post('/school/setup/teachers', { onSuccess: () => teacherForm.reset() });
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
                        <v-text-field v-model="sectionForm.section_name" label="Section name (e.g. Grade 7 - A)" :error-messages="sectionForm.errors.section_name" />
                        <v-text-field v-model="sectionForm.year_level" label="Year level (e.g. Grade 7)" :error-messages="sectionForm.errors.year_level" />
                        <v-text-field v-model="sectionForm.school_year" label="School year" :error-messages="sectionForm.errors.school_year" />
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
                    <v-card-title>Add teacher (creates a login)</v-card-title>
                    <v-card-text>
                        <v-text-field v-model="teacherForm.employee_number" label="Employee no." :error-messages="teacherForm.errors.employee_number" />
                        <v-text-field v-model="teacherForm.first_name" label="First name" :error-messages="teacherForm.errors.first_name" />
                        <v-text-field v-model="teacherForm.last_name" label="Last name" :error-messages="teacherForm.errors.last_name" />
                        <v-text-field v-model="teacherForm.email" label="Email (login)" :error-messages="teacherForm.errors.email" />
                        <v-text-field v-model="teacherForm.password" type="password" label="Password (min 8)" :error-messages="teacherForm.errors.password" />
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