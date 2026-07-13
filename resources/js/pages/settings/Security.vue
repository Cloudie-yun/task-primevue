<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3';
import { useForm as useInertiaForm } from '@inertiajs/vue3';
import { Form } from '@primevue/forms';
import {zodResolver} from '@primevue/forms/resolvers/zod';
import * as z from 'zod';
import { ref, computed } from 'vue';
import type { FormInstance } from '@primevue/forms/form';
import SecurityController from '@/actions/App/Http/Controllers/Settings/SecurityController';
// Validation
import { passwordSchema } from '@/validation/passwordRules';
// Components
import Heading from '@/components/Heading.vue';
import type { Props as ManagePasskeysProps } from '@/components/ManagePasskeys.vue';
import ManagePasskeys from '@/components/ManagePasskeys.vue';
import type { Props as ManageTwoFactorProps } from '@/components/ManageTwoFactor.vue';
import ManageTwoFactor from '@/components/ManageTwoFactor.vue';
import PasswordInput from '@/components/PasswordInput.vue';
// import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
// Routes
import { edit } from '@/routes/security';
// Icons
import { Spinner } from '@primeicons/vue';


type Props = {
    passwordRules: string;
} & ManagePasskeysProps &
    ManageTwoFactorProps;

const props = defineProps<Props>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Security settings',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const formRef = ref<FormInstance | null>(null);

// Client-side validation schema — this is UX sugar only.
// Fortify still owns the real validation on submit.
const schema = z.object({
    current_password: z.string().nonempty('Current password is required'),
    password: passwordSchema,
    password_confirmation: z.string().nonempty('Password confirmation is required'),
}).refine((data) => data.password === data.password_confirmation, {
    message: 'Passwords do not match',
    path: ['password_confirmation'],
});

const resolver = zodResolver(schema);

// Inertia owns the actual request: CSRF, processing state,
// and mapping Fortify's server-side errors.
const inertiaForm = useInertiaForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

function clearPasswordFields() {
    inertiaForm.reset('current_password', 'password', 'password_confirmation');
    formRef.value?.reset();
}

function onFormSubmit({ valid, values }: { valid: boolean; values: Record<string, any> }) {
    if (!valid) return;

    inertiaForm.current_password = values.current_password;
    inertiaForm.password = values.password;
    inertiaForm.password_confirmation = values.password_confirmation;

    inertiaForm.put(SecurityController.update.url(), {
        preserveScroll: true,
        onSuccess: clearPasswordFields,
        onError: clearPasswordFields,
    });
}
</script>

<template>
    <Head title="Security settings" />

    <h1 class="sr-only">Security settings</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            title="Update password"
            description="Ensure your account is using a long, random password to stay secure"
        />

        <Form
            ref="formRef"
            v-slot="$form"
            :resolver="resolver"
            @submit="onFormSubmit"
            :initial-values="{ current_password: '', password: '', password_confirmation: '' }"
            class="space-y-6"
        >
            <!-- Hidden username field for password managers -->
            <input
                type="email"
                name="username"
                autocomplete="username"
                :value="user.email"
                :tabindex="-1"
                class="sr-only"
                aria-hidden="true"
            />
            
            <div class="grid gap-1.5">
                <FloatLabel variant="on">
                    <PasswordInput
                        inputId="current_password"
                        name="current_password"
                        class="mt-1 block w-full"
                        autocomplete="current-password"
                        fluid
                        :default-mask="true"
                    />
                    <Label for="current_password">Current password</Label>
                </FloatLabel>
                <Message severity="error" v-if="$form.current_password?.invalid" size="small" variant="simple">
                    {{ $form.current_password.error?.message }}
                </Message>
                <Message severity="error" v-if="inertiaForm.errors.current_password" size="small" variant="simple">
                    {{ inertiaForm.errors.current_password }}
                </Message>
            </div>

            <div class="grid gap-1.5">
                <FloatLabel variant="on">
                    <PasswordInput
                        inputId="password"
                        name="password"
                        class="mt-1 block w-full"
                        autocomplete="new-password"
                        fluid
                        :default-mask="true"
                        :passwordrules="props.passwordRules"
                    />
                    <Label for="password">New password</Label>
                </FloatLabel>
                <Message severity="error" v-if="$form.password?.invalid" size="small" variant="simple">
                    {{ $form.password.error?.message }}
                </Message>
                <Message severity="error" v-if="inertiaForm.errors.password" size="small" variant="simple">
                    {{ inertiaForm.errors.password }}
                </Message>
            </div>

            <div class="grid gap-1.5">
                <FloatLabel variant="on">
                    <PasswordInput
                        inputId="password_confirmation"
                        name="password_confirmation"
                        class="mt-1 block w-full"
                        autocomplete="new-password"
                        fluid
                        :default-mask="true"
                        :passwordrules="props.passwordRules"
                    />
                    <Label for="password_confirmation">Confirm password</Label>
                </FloatLabel>
                <Message severity="error" v-if="$form.password_confirmation?.invalid" size="small" variant="simple">
                    {{ $form.password_confirmation.error?.message }}
                </Message>
                <Message severity="error" v-if="inertiaForm.errors.password_confirmation" size="small" variant="simple">
                    {{ inertiaForm.errors.password_confirmation }}
                </Message>
            </div>

            <div class="flex items-center gap-4">
                <Button
                    type="submit"
                    :disabled="inertiaForm.processing"
                    data-test="update-password-button"
                    fluid
                    class=" max-w-32"
                >
                    <Spinner v-if="inertiaForm.processing" class="animate-spin" />
                    Save
                </Button>
            </div>
        </Form>
    </div>

    <ManageTwoFactor
        :canManageTwoFactor="canManageTwoFactor"
        :requiresConfirmation="requiresConfirmation"
        :twoFactorEnabled="twoFactorEnabled"
    />

    <ManagePasskeys
        :canManagePasskeys="canManagePasskeys"
        :passkeys="passkeys"
    />
</template>
