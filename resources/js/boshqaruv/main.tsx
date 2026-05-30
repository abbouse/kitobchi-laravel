import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import '../../css/boshqaruv.css';

import React from 'react';
import { createRoot } from 'react-dom/client';
import { createInertiaApp } from '@inertiajs/react';
import Layout from './Layout';

const pages = import.meta.glob('./pages/**/*.tsx', { eager: true });

createInertiaApp({
  title: (title) => title ? `${title} - Kitobchi Boshqaruv` : 'Kitobchi Boshqaruv',
  resolve: (name) => {
    const page = pages[`./pages/${name}.tsx`] as { default: React.ComponentType & { layout?: (page: React.ReactNode) => React.ReactNode } };

    if (!page) {
      throw new Error(`Boshqaruv page topilmadi: ${name}`);
    }

    if (!['Login', 'LiveDashboard'].includes(name)) {
      page.default.layout = (component: React.ReactNode) => <Layout>{component}</Layout>;
    }

    return page;
  },
  setup({ el, App, props }) {
    createRoot(el).render(<App {...props} />);
  },
  progress: {
    color: '#4f46e5',
  },
});
