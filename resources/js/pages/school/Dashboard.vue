<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import SchoolLayout from '@/layouts/SchoolLayout.vue';

defineProps<{ counts: Record<string, number> }>();
</script>

<template>
    <Head title="Dashboard" />
    <SchoolLayout>
        <h1 class="text-h5 mb-4">Dashboard</h1>
        <v-row>
            <v-col v-for="(value, label) in counts" :key="label" cols="12" sm="6" md="4">
                <v-card>
                    <v-card-text>
                        <div class="text-medium-emphasis">{{ label }}</div>
                        <div class="text-h4">{{ value }}</div>
                    </v-card-text>
                </v-card>
            </v-col>
        </v-row>
    </SchoolLayout>
</template>