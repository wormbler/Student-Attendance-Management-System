<script setup lang="ts">
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import SchoolLayout from '@/layouts/SchoolLayout.vue';
import { useRequiredFields } from '@/composables/useRequiredFields';

type Schedule = {
    id: number; section: string; subject: string; teacher: string;
    day: string; start_time: string; end_time: string; room: string | null;
};

defineProps<{
    schedules: Schedule[];
    isAdmin: boolean;
    subjects: { id: number; subject_name: string }[];
    teachers: { id: number; name: string }[];
    sections: { id: number; section_name: string }[];
}>();

const days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

const page = usePage();
const deleteError = computed(() => (page.props as any).errors?.delete);

// Admin: create
const form = useForm({
    subject_id: null as number | null,
    teacher_id: null as number | null,
    section_id: null as number | null,
    day: 'Monday',
    start_time: '08:00',
    end_time: '09:00',
    room: '',
});

const req = useRequiredFields(form, {
    subject_id: 'Subject',
    teacher_id: 'Teacher',
    section_id: 'Section',
    day: 'Day',
    start_time: 'Start time',
    end_time: 'End time',
});

const add = () => {
    if (!req.check()) return;
    form.post('/school/schedules', {
        preserveScroll: true,
        onSuccess: () => req.reset(),
    });
};

// Admin and teacher: edit day, time, room
const dialog = ref(false);
const editingId = ref<number | null>(null);
const editForm = useForm({ day: 'Monday', start_time: '08:00', end_time: '09:00', room: '' });

const editReq = useRequiredFields(editForm, {
    day: 'Day',
    start_time: 'Start time',
    end_time: 'End time',
});

const edit = (s: Schedule) => {
    editingId.value = s.id;
    editForm.day = s.day;
    editForm.start_time = s.start_time;
    editForm.end_time = s.end_time;
    editForm.room = s.room ?? '';
    editForm.clearErrors();
    editReq.reset();
    dialog.value = true;
};

const saveEdit = () => {
    if (!editReq.check()) return;
    editForm.put(`/school/schedules/${editingId.value}`, {
        preserveScroll: true,
        onSuccess: () => {
            dialog.value = false;
            editReq.reset();
        },
    });
};

const remove = (id: number) => {
    if (confirm('Delete this schedule?')) {
        router.delete(`/school/schedules/${id}`, { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Schedules" />
    <SchoolLayout>
        <h1 class="text-h5 mb-4">{{ isAdmin ? 'Class Schedules' : 'My Schedules' }}</h1>

        <v-card v-if="isAdmin" class="mb-6">
            <v-card-title>Add schedule</v-card-title>
            <v-card-text>
                <v-alert v-if="req.message.value" type="warning" density="compact" class="mb-3" :text="req.message.value" />
                <v-row>
                    <v-col cols="12" md="4">
                        <v-select v-model="form.subject_id" :items="subjects" item-title="subject_name" item-value="id" label="Subject" :error-messages="req.err('subject_id')" />
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-select v-model="form.teacher_id" :items="teachers" item-title="name" item-value="id" label="Teacher" :error-messages="req.err('teacher_id')" />
                    </v-col>
                    <v-col cols="12" md="4">
                        <v-select v-model="form.section_id" :items="sections" item-title="section_name" item-value="id" label="Section" :error-messages="req.err('section_id')" />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-select v-model="form.day" :items="days" label="Day" :error-messages="req.err('day')" />
                    </v-col>
                    <v-col cols="6" md="2">
                        <v-text-field v-model="form.start_time" type="time" label="Start" :error-messages="req.err('start_time')" />
                    </v-col>
                    <v-col cols="6" md="2">
                        <v-text-field v-model="form.end_time" type="time" label="End" :error-messages="req.err('end_time')" />
                    </v-col>
                    <v-col cols="12" md="3">
                        <v-text-field v-model="form.room" label="Room (optional)" />
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
                    <tr>
                        <th>Section</th><th>Subject</th><th>Teacher</th><th>Day</th><th>Time</th><th>Room</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="s in schedules" :key="s.id">
                        <td>{{ s.section }}</td>
                        <td>{{ s.subject }}</td>
                        <td>{{ s.teacher }}</td>
                        <td>{{ s.day }}</td>
                        <td>{{ s.start_time }} - {{ s.end_time }}</td>
                        <td>{{ s.room }}</td>
                        <td class="text-right text-no-wrap">
                            <v-btn icon="mdi-pencil" size="small" variant="text" @click="edit(s)" />
                            <v-btn v-if="isAdmin" icon="mdi-delete" size="small" variant="text" color="error" @click="remove(s.id)" />
                        </td>
                    </tr>
                    <tr v-if="!schedules.length">
                        <td colspan="7" class="text-center text-medium-emphasis pa-6">No schedules yet.</td>
                    </tr>
                </tbody>
            </v-table>
        </v-card>

        <v-dialog v-model="dialog" max-width="480">
            <v-card title="Edit schedule">
                <v-card-text>
                    <v-alert v-if="editReq.message.value" type="warning" density="compact" class="mb-3" :text="editReq.message.value" />
                    <v-select v-model="editForm.day" :items="days" label="Day" :error-messages="editReq.err('day')" />
                    <v-row>
                        <v-col cols="6">
                            <v-text-field v-model="editForm.start_time" type="time" label="Start" :error-messages="editReq.err('start_time')" />
                        </v-col>
                        <v-col cols="6">
                            <v-text-field v-model="editForm.end_time" type="time" label="End" :error-messages="editReq.err('end_time')" />
                        </v-col>
                    </v-row>
                    <v-text-field v-model="editForm.room" label="Room (optional)" />
                </v-card-text>
                <v-card-actions>
                    <v-spacer />
                    <v-btn @click="dialog = false">Cancel</v-btn>
                    <v-btn color="primary" :loading="editForm.processing" @click="saveEdit">Save</v-btn>
                </v-card-actions>
            </v-card>
        </v-dialog>
    </SchoolLayout>
</template>