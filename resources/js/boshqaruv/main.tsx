// Tartib muhim: Bootstrap (Axelit bilan kelgan versiya) → ikonkalar →
// Axelit asl uslublari → Kitobchi qatlami.
import '../../css/axelit/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import '../../css/axelit/icons.css';
import '../../css/axelit/axelit.css';
import '../../css/boshqaruv.css';
// Bootstrap'ning JS qismi (dropdown, modal, offcanvas va h.k.) — avval faqat
// CSS import qilingan edi, shuning uchun data-bs-toggle="dropdown" kabi
// barcha elementlar butun panelda bosilganda hech narsa qilmasdi.
import 'bootstrap/dist/js/bootstrap.bundle.min.js';

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
    color: '#8C76F0',
  },
});
