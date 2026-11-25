import { computed, ref } from 'vue';

export function useRequiredFields(form: any, labels: Record<string, string>) {
    const missing = ref<string[]>([]);
    const submitted = ref(false);

    const check = () => {
        submitted.value = true;
        missing.value = Object.keys(labels).filter((key) => {
            const v = form[key];
            return v === null || v === undefined || String(v).trim() === '';
        });
        return missing.value.length === 0;
    };

    // Use as :error-messages="req.err('field')"
    const err = (key: string) =>
        submitted.value && missing.value.includes(key) ? 'Required' : form.errors[key];

    const message = computed(() =>
        missing.value.length
            ? `Please fill in: ${missing.value.map((k) => labels[k]).join(', ')}`
            : '',
    );

    const reset = () => {
        submitted.value = false;
        missing.value = [];
    };

    return { check, err, message, reset };
}