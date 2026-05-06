import '../css/app.css';
import './bootstrap';
import 'antd/dist/reset.css';

import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ConfigProvider, App as AntdApp } from 'antd';
import locale from 'antd/locale/tr_TR';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { AntdStaticHelper } from '@/Utils/antdHelper';

const appName = window.document.getElementsByTagName('title')[0]?.innerText || 'Laravel';

const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            staleTime: 5 * 60 * 1000,
            gcTime: 10 * 60 * 1000,
            refetchOnWindowFocus: false,
            refetchOnReconnect: false,
            retry: 1,
        },
    },
});

// 🛡️ 401 Unauthorized handler - otomatik login'e yönlendir
window.addEventListener('auth:unauthorized', () => {
    window.location.href = '/login';
});

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.tsx`, import.meta.glob('./Pages/**/*.tsx')) as any,
    setup({ el, App, props }) {
        const root = createRoot(el);

        root.render(
            <QueryClientProvider client={queryClient}>
                <ConfigProvider 
                    locale={locale}
                    theme={{
                        token: {
                            colorPrimary: '#1890ff',
                            borderRadius: 6,
                        },
                    }}
                >
                    <AntdApp>
                        <AntdStaticHelper>
                            <App {...props} />
                        </AntdStaticHelper>
                    </AntdApp>
                </ConfigProvider>
            </QueryClientProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});
