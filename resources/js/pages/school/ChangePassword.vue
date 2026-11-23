<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import SchoolLayout from '@/layouts/SchoolLayout.vue';

defineProps<{ forced: boolean }>();

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const showCurrent = ref(false);
const showNew = ref(false);
const showConfirm = ref(false);

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
                <v-text-field
                    v-model="form.current_password"
                    :type="showCurrent ? 'text' : 'password'"
                    :append-inner-icon="showCurrent ? 'mdi-eye-off' : 'mdi-eye'"
                    label="Current password"
                    :error-messages="form.errors.current_password"
                    @click:append-inner="showCurrent = !showCurrent"
                />
                <v-text-field
                    v-model="form.password"
                    :type="showNew ? 'text' : 'password'"
                    :append-inner-icon="showNew ? 'mdi-eye-off' : 'mdi-eye'"
                    label="New password (min 8)"
                    :error-messages="form.errors.password"
                    @click:append-inner="showNew = !showNew"
                />
                <v-text-field
                    v-model="form.password_confirmation"
                    :type="showConfirm ? 'text' : 'password'"
                    :append-inner-icon="showConfirm ? 'mdi-eye-off' : 'mdi-eye'"
                    label="Confirm new password"
                    @click:append-inner="showConfirm = !showConfirm"
                />
                <v-btn color="primary" :loading="form.processing" @click="save">Save password</v-btn>
            </v-card-text>
        </v-card>
    </SchoolLayout>
</template>