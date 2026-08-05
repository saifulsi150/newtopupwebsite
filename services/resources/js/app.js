import './bootstrap';

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';

const appName = import.meta.env.VITE_APP_NAME || 'LX TOPUP';
let hasStarted = false;

const startInertiaApp = () => {
	if (hasStarted) return;
	hasStarted = true;

	const appElement = document.getElementById('app');
	if (!appElement) return;

	const initialPage = appElement?.dataset.page ? JSON.parse(appElement.dataset.page) : null;

	createInertiaApp({
		page: initialPage,
		title: (title) => (title ? `${title} - ${appName}` : appName),
		resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
		setup({ el, App, props, plugin }) {
			return createApp({ render: () => h(App, props) }).use(plugin).mount(el);
		},
		progress: false,
	});
};

if (document.readyState === 'loading') {
	document.addEventListener('DOMContentLoaded', startInertiaApp, { once: true });
} else {
	startInertiaApp();
}
