<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { useForm as useInertiaForm } from '@inertiajs/vue3';
import { Form } from '@primevue/forms';
import { zodResolver } from '@primevue/forms/resolvers/zod';
import * as z from "zod";
import { ref } from 'vue';
import type { FormInstance } from '@primevue/forms/form';
// Components
import PasswordInput from '@/components/PasswordInput.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import TextLink from '@/components/TextLink.vue';
import { Label } from '@/components/ui/label';
// Icons
import { Spinner } from '@primeicons/vue';
// Routes
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Log in to your account',
        description: 'Enter your email and password below to log in',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const formRef = ref<FormInstance | null>(null);

// Client-side validation schema — this is UX sugar only.
// Fortify still owns the real validation on submit.
const schema = z.object({
    email: z.string().min(1, 'Email is required').email('Enter a valid email'),
    password: z.string().min(1, 'Password is required')
});
const resolver = zodResolver(schema);

// Inertia owns the actual request: CSRF, processing state,
// and mapping Fortify's server-side errors.
const inertiaForm = useInertiaForm({
    email: '',
    password: '',
    remember: false,
});

function onFormSubmit({ valid, values }: { valid: boolean; values: Record<string, any> }) {
    if (!valid) return;

    inertiaForm.email = values.email;
    inertiaForm.password = values.password;
    inertiaForm.remember = values.remember ?? false;

    inertiaForm.post(store.url(), {
        preserveScroll: true,
        onFinish: () => {
            const { email } = values;

            inertiaForm.reset('password'),
            
            formRef.value?.reset();
            formRef.value?.setFieldValue('email', email);
        },
    });
}
</script>

<template>
    <Head title="Log in" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <PasskeyVerify />

    <Form
        ref="formRef"
        v-slot="$form"
        :resolver="resolver"
        :initial-values="{ email: '', password: '', remember: false }"
        @submit="onFormSubmit"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="flex flex-col gap-1.5">
                <FloatLabel variant="on">
                    <InputText
                        id="email"
                        name="email"
                        type="email"
                        :tabindex="1"
                        autocomplete="email"
                        fluid
                    />
                    <Label for="email">Email address</Label>
                </FloatLabel>   
                <!-- Client-side validation error -->
                <Message severity="error" v-if="$form.email?.invalid" size="small" variant="simple">
                    {{ $form.email.error?.message }}
                </Message>
                <!-- Server-side (Fortify) error, takes precedence once present -->
                <Message severity="error" v-if="inertiaForm.errors.email" size="small" variant="simple">
                    {{ inertiaForm.errors.email }}
                </Message>     
            </div>
            
            <div class="flex flex-col gap-1.5">
                <TextLink
                    v-if="canResetPassword"
                    :href="request()"
                    class="text-sm self-end"
                    :tabindex="5"
                >
                    Forgot your password?
                </TextLink>
                <div class="flex flex-col gap-1.5">
                    <FloatLabel variant="on">
                        <PasswordInput
                            inputId="password"
                            name="password"
                            :tabindex="2"
                            autocomplete="current-password"
                            :defaultMask="true"
                            fluid
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
            </div>
            <div class="flex items-center justify-between">
                <Label for="remember" class="flex items-center space-x-3">
                    <Checkbox id="remember" name="remember" :tabindex="3" binary />
                    <span>Remember me</span>
                </Label>
            </div>

            <Button
                type="submit"
                class="mt-4 w-full"
                :tabindex="4"
                :disabled="inertiaForm.processing"
                data-test="login-button"
            >
                <Spinner v-if="inertiaForm.processing" class="animate-spin" />
                Log in
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            Don't have an account?
            <TextLink :href="register()" :tabindex="5">Sign up</TextLink>
        </div>
    </Form>
</template>