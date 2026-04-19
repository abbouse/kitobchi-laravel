<?php

return [
    'seo' => [
        'title' => 'Careers — open roles & online application | Kitobchi',
        'desc' => 'Join the Kitobchi team: open positions, remote-friendly work, online CV submission, and open applications. Books & stationery marketplace.',
    ],

    'hero' => [
        'eyebrow' => 'With us',
        'heading_l1' => 'With Kitobchi,',
        'heading_l2' => 'write the',
        'heading_l3' => 'next chapter.',
        'p1' => 'A local books & stationery marketplace—we connect stores and customers. Apply online for open roles.',
        'p2' => 'You can also send an open application even if there isn’t a matching vacancy.',
        'open_roles' => 'Open roles',
        'open_inq' => 'Open application',
    ],

    'manifesto' => [
        'h2_l1' => 'Your mission?',
        'h2_l2' => 'Write a new chapter.',
        'p1' => 'Kitobchi is growing as a mobile app and marketplace: sellers, catalog, delivery, and user experience—all built for real people.',
        'p2' => 'If local e‑commerce and “products close to you” resonate with you, it’s a great time to join.',
    ],

    'roles' => [
        'heading' => 'Open roles',
        'intro' => 'Expand a row and complete the application form for that role (CV required; Telegram username is requested).',
        'empty' => 'There are no published vacancies right now. Share your idea via “Open application” below.',
    ],

    'form' => [
        'full_name' => 'Full name *',
        'email' => 'Email *',
        'telegram' => 'Telegram username *',
        'telegram_ph' => '@username or username',
        'cover' => 'Short cover letter (optional)',
        'cover_ph' => 'About you and your motivation…',
        'cv' => 'CV (PDF, DOC, DOCX) *',
        'submit_apply' => 'Submit application',
        'message' => 'Message * (at least 20 characters)',
        'message_ph' => 'The role you want, your experience…',
        'attachment' => 'Attachment (optional, PDF/DOC/DOCX)',
        'submit_inq' => 'Send message',
    ],

    'inquiry' => [
        'heading' => 'A different direction?',
        'intro' => 'If your role isn’t listed, leave an open application—our team will review it in the dashboard and contact you.',
    ],

    'ld' => [
        'default_place' => 'Uzbekistan',
    ],

    'flash' => [
        'application_sent' => 'Your application was received. We’ll get in touch soon.',
        'inquiry_sent' => 'Your message was received.',
    ],

    'attributes' => [
        'full_name' => 'full name',
        'email' => 'email',
        'telegram_username' => 'Telegram username',
        'cover_message' => 'cover letter',
        'cv' => 'CV file',
        'message' => 'message',
        'attachment' => 'attachment',
    ],

    'validation' => [
        'full_name.required' => 'Please enter your full name.',
        'email.required' => 'Please enter your email address.',
        'email.email' => 'Please enter a valid email address.',
        'telegram_username.required' => 'Please enter your Telegram username.',
        'cv.required' => 'Please upload your CV.',
        'cv.file' => 'The CV upload is invalid.',
        'cv.mimes' => 'The CV must be a PDF, DOC, or DOCX file.',
        'cv.max' => 'The CV must not be larger than 10 MB.',
        'message.required' => 'Please enter your message.',
        'message.min' => 'Your message must be at least 20 characters.',
        'attachment.mimes' => 'The attachment must be a PDF, DOC, or DOCX file.',
        'attachment.max' => 'The attachment must not be larger than 10 MB.',
    ],
];
