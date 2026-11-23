<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import SchoolLayout from '@/layouts/SchoolLayout.vue';

defineProps<{ forced: boolean }>();

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const save = () => form.put('/change-password', { onSuccess: () => form.reset() });
</script>

<template>
    <Head title="Change Password" />
    <SchoolLayout>
        <h1 class="text-h5 mb-4">Change Password</h1>

        <v-alert
            v-if="forced"
            type="warning"
            class="mb-4"
            text="You are using a temporary password. Please set your own password to continue."
        />

        <v-card max-width="480">
            <v-card-text>
                <v-text-field v-model="form.current_password" type="password" label="Current password" :error-messages="form.errors.current_password" />
                <v-text-field v-model="form.password" type="password" label="New password (min 8)" :error-messages="form.errors.password" />
                <v-text-field v-model="form.password_confirmation" type="password" label="Confirm new password" />
                <v-btn color="primary" :loading="form.processing" @click="save">Save password</v-btn>
            </v-card-text>
        </v-card>
    </SchoolLayout>
</template>