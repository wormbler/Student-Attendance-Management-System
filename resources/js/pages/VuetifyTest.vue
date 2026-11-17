<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Vuetify Test" />
    <v-app>
        <v-main class="d-flex align-center justify-center">
            <v-card class="pa-8 text-center" max-width="400">
                <v-card-title>Vuetify works</v-card-title>
                <v-card-text>Student Attendance System</v-card-text>
                <v-btn color="primary" prepend-icon="mdi-check">It works</v-btn>
            </v-card>
        </v-main>
    </v-app>
</template>