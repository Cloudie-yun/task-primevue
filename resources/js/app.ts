import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, createSSRApp, h } from 'vue';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import ProductSettingsLayout from '@/layouts/product/SettingsLayout.vue'
import { initializeFlashToast } from '@/lib/flashToast';
import PrimeVue from 'primevue/config';
import ConfirmationService from 'primevue/confirmationservice';
import ToastService from 'primevue/toastservice';
import Aura from '@primeuix/themes/aura';
import Noir from '@/theme';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';
const primeUIKey = import.meta.env.VITE_PRIMEUI_KEY;

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            case name.startsWith('productSettings/'):
                return [AppLayout, ProductSettingsLayout];
            default:
                return AppLayout;
        }
    },
    setup({ el, App, props, plugin }) {
        const app = (typeof window === 'undefined' ? createSSRApp : createApp)(
            { render: () => h(App, props) },
        )
            .use(plugin)
            .use(PrimeVue, {
                // theme: { preset: Aura },
                theme: { preset: Noir },
                // unstyled: true,
                license: primeUIKey,
            })
            .use(ConfirmationService)
            .use(ToastService);
        if (el) {
            app.mount(el);
        }

        return app;
    },
    progress: {
        color: '#4B5563',
    },
});

if (typeof window !== 'undefined') {
    // This will set light / dark mode on page load...
    initializeTheme();

    // This will listen for flash toast data from the server...
    initializeFlashToast();
}
