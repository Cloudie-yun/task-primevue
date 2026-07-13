<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useForm as useInertiaForm } from '@inertiajs/vue3';
import { Form } from '@primevue/forms';
import {zodResolver} from '@primevue/forms/resolvers/zod';
import * as z from 'zod';
import { ref } from 'vue';
import type { FormInstance } from '@primevue/forms/form';
// Validation
import { passwordSchema } from '@/validation/passwordRules';

// Components
import TextLink from '@/components/TextLink.vue';
import { Label } from '@/components/ui/label';
import PasswordInput from '@/components/PasswordInput.vue';
//Icons 
import { Spinner } from '@primeicons/vue';
// Routes
import { login } from '@/routes';
import { store } from '@/routes/register';


defineOptions({
    layout: {
        title: 'Create an account',
        description: 'Enter your details below to create your account',
    },
});

defineProps<{
    passwordRules: string;
}>();

const formRef = ref<FormInstance | null>(null);

// Client-side validation schema — this is UX sugar only.
// Fortify still owns the real validation on submit.
const schema = z.object({
    name: z.string().min(1, 'Name is required'),
    email: z.string().min(1, 'Email is required').email('Enter a valid email'),
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
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

function onFormSubmit({ valid, values }: { valid: boolean; values: Record<string, any> }) {
    if (!valid) return;

    inertiaForm.name = values.name;
    inertiaForm.email = values.email;
    inertiaForm.password = values.password;
    inertiaForm.password_confirmation = values.password_confirmation

    inertiaForm.post(store.url(), {
        preserveScroll: true,
        // onFinish: () => inertiaForm.reset('password','password_confirmation'),
        onFinish: () => {
            const { name, email } = values;

            inertiaForm.reset('password', 'password_confirmation');

            formRef.value?.reset();
            formRef.value?.setFieldValue('name', name);
            formRef.value?.setFieldValue('email', email);
        },
    });
}

</script>

<template>
    <Head title="Register" />

    <Form
        ref="formRef"
        v-slot="$form"
        :resolver="resolver"
        :initial-values="{ name: '', email: '', password: '', password_confirmation: '' }"
        @submit="onFormSubmit"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-1.5">
                <FloatLabel variant="on">
                    <InputText
                        id="name"
                        name="name"
                        type="text"
                        :tabindex="1"
                        autocomplete="name"
                        fluid
                    />
                    <Label for="name">Name</Label>
                </FloatLabel>
                <Message severity="error" v-if="$form.name?.invalid" size="small" variant="simple">
                    {{ $form.name.error?.message }}
                </Message>
                <Message severity="error" v-if="inertiaForm.errors.name" size="small" variant="simple">
                    {{ inertiaForm.errors.name }}
                </Message>
            </div>

            <div class="grid gap-1.5">
                <FloatLabel variant="on">
                    <InputText
                        id="email"
                        name="email"
                        type="email"
                        :tabindex="2"
                        autocomplete="email"
                        fluid
                    />
                    <Label for="email">Email address</Label>
                </FloatLabel>
                <Message severity="error" v-if="$form.email?.invalid" size="small" variant="simple">
                    {{ $form.email.error?.message }}
                </Message>
                <Message severity="error" v-if="inertiaForm.errors.email" size="small" variant="simple">
                    {{ inertiaForm.errors.email }}
                </Message>
            </div>

            <div class="grid gap-1.5">
                <FloatLabel variant="on">
                    <PasswordInput
                        inputId="password"
                        name="password"
                        :tabindex="3"
                        autocomplete="new-password"
                        fluid
                        :passwordrules="passwordRules"
                        :defaultMask="true"
                    />
                    <Label for="password">Password</Label>
                </FloatLabel>
                <Message severity="error" v-if="$form.password?.invalid" size="small" variant="simple">
                    {{ $form.password.error?.message }}
                </Message>
                <Message severity="error" v-if="inertiaForm.errors.password" size="small" variant="simple" >
                    {{ inertiaForm.errors.password }}
                </Message>
            </div>

            <div class="grid gap-1.5">
                <FloatLabel variant="on">
                    <PasswordInput
                        inputId="password_confirmation"
                        name="password_confirmation"
                        :tabindex="4"
                        autocomplete="new-password"
                        fluid
                        :passwordrules="passwordRules"
                        :defaultMask="true"
                    />
                    <Label for="password_confirmation">Confirm Password</Label>
                </FloatLabel>
                <Message severity="error" v-if="$form.password_confirmation?.invalid" size="small" variant="simple">
                    {{ $form.password_confirmation.error?.message }}
                </Message>
                <Message severity="error" v-if="inertiaForm.errors.password_confirmation" size="small" variant="simple" >
                    {{ inertiaForm.errors.password_confirmation }}
                </Message>
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                tabindex="5"
                :disabled="inertiaForm.processing"
                data-test="register-user-button"
            >
                <Spinner v-if="inertiaForm.processing"  class="animate-spin" />
                Create account
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Already have an account?
            <TextLink
                :href="login()"
                :tabindex="6"
                >Log in</TextLink
            >
        </div>
    </Form>
</template>
