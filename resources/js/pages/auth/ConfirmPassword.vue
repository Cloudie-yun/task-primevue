<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import {
    index as confirmOptions,
    store as confirmStore,
} from '@/actions/Laravel/Passkeys/Http/Controllers/PasskeyConfirmationController';
import InputError from '@/components/InputError.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Label } from '@/components/ui/label';
import { Spinner } from '@primeicons/vue';
import { store } from '@/routes/password/confirm';

defineOptions({
    layout: {
        title: 'Confirm password',
        description:
            'This is a secure area of the application. Please confirm your password before continuing.',
    },
});
</script>

<template>
    <Head title="Confirm password" />

    <PasskeyVerify
        :routes="{
            options: confirmOptions(),
            submit: confirmStore(),
        }"
        label="Confirm with passkey"
        loading-label="Confirming..."
        separator="Or confirm with password"
    />

    <Form
        v-bind="store.form()"
        reset-on-success
        v-slot="{ errors, processing }"
    >
        <div class="space-y-6">
            <div class="grid gap-1.5">
                <FloatLabel variant="on">
                    <PasswordInput
                        inputId="password"
                        name="password"
                        class="mt-1 block w-full"
                        required
                        autocomplete="current-password"
                        autofocus
                        :default-mask="true"
                    />
                    <Label for="password">Password</Label>
                </FloatLabel>
                <Message severity="error" v-if="errors.password" size="small" variant="simple">
                    {{ errors.password }}
                </Message>
                <!-- <Label htmlFor="password">Password</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="current-password"
                    autofocus
                />

                <InputError :message="errors.password" /> -->
            </div>

            <div class="flex items-center">
                <Button
                    type="submit"
                    fluid
                    :disabled="processing"
                    data-test="confirm-password-button"
                >
                    <Spinner v-if="processing" class="animate-spin"/>
                    Confirm password
                </Button>
            </div>
        </div>
    </Form>
</template>
