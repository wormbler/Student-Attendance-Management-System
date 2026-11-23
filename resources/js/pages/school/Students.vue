<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import SchoolLayout from '@/layouts/SchoolLayout.vue';

defineProps<{
    students: any[];
    sections: { id: number; section_name: string }[];
}>();

const page = usePage();
const errors = computed(() => (page.props as any).errors ?? {});
const alertError = computed(() => errors.value.delete || errors.value.login);

const form = useForm({
    student_number: '',
    first_name: '',
    last_name: '',
    gender: 'Male',
    section_id: null as number | null,
    email: '',
    password: '',
});

const add = () => form.post('/school/students', { onSuccess: () => form.reset() });

const remove = (id: number) => {
    if (confirm('Delete this student?')) {
        router.delete(`/school/students/${id}`, { preserveScroll: true });
    }
};

const createLogin = (id: number) => {
    const email = prompt('Student login email:');
    if (!email) return;
    const password = prompt('Password (min 8 characters):');
    if (!password) return;
    router.post(`/school/students/${id}/login`, { email, password }, { preserveScroll: true });
};
</script>

<template>
    <Head title="Students" />
    <SchoolLayout>
        <h1 class="text-h5 mb-4">Students</h1>

        <v-card v-if="$page.props.auth?.user?.role === 'admin'" class="mb-6">
            <v-card-title>Add student</v-card-title>
            <v-card-text>
                <v-row>
                    <v-col cols="12" md="2">
                        <v-text-field v-model="form.student_number" label="Student no." :error-messages="form.errors.student_number" />
                    </v-col>
                    <v-col cols="12" md="2">
                        <v-text-field v-model="form.first_name" label="First name" :error-messages="form.errors.first_name" />
                    </v-col>
                    <v-col cols="12" md="2">
                        <v-text-field v-model="form.last_name" label="Last name" :error-messages="form.errors.last_name" />
                    </v-col>
                    <v-col cols="12" md="2">
                        <v-select v-model="form.gender" :items="['Male', 'Female', 'Other']" label="Gender" />
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-select
                            v-model="form.section_id"
                            :items="sections"
                            item-title="section_name"
                            item-value="id"
                            label="Section"
                            :error-messages="form.errors.section_id"
                        />
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-text-field v-model="form.email" label="Login email (optional)" :error-messages="form.errors.email" />
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-text-field v-model="form.password" type="password" label="Password (min 8)" :error-messages="form.errors.password" />
                    </v-col>
                    <v-col cols="12" md="2">
                        <v-btn color="primary" :loading="form.processing" @click="add">Add</v-btn>
                    </v-col>
                </v-row>
            </v-card-text>
        </v-card>

        <v-alert v-if="alertError" type="error" class="mb-4" :text="alertError" />

        <v-card>
            <v-table density="comfortable">
                <thead>
                    <tr>
                        <th>Student no.</th>
                        <th>Name</th>
                        <th>Gender</th>
                        <th>Section</th>
                        <th>Login</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="s in students" :key="s.id">
                        <td>{{ s.student_number }}</td>
                        <td>{{ s.last_name }}, {{ s.first_name }}</td>
                        <td>{{ s.gender }}</td>
                        <td>{{ s.section?.section_name }}</td>
                        <td>
                            <v-icon v-if="s.user_id" color="success" icon="mdi-check-circle" />
                            <span v-else class="text-medium-emphasis">none</span>
                        </td>
                        <td class="text-right text-no-wrap">
                            <template v-if="$page.props.auth?.user?.role === 'admin'">
                                <v-btn v-if="!s.user_id" icon="mdi-key" size="small" variant="text" title="Create login" @click="createLogin(s.id)" />
                                <v-btn icon="mdi-delete" size="small" variant="text" color="error" @click="remove(s.id)" />
                            </template>
                        </td>
                    </tr>
                </tbody>
            </v-table>
        </v-card>
    </SchoolLayout>
</template>