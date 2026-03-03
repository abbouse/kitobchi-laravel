<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Yuridik hujjat') - Kitobchi</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary: #0f172a;
            --primary-light: #1e293b;
            --accent: #3b82f6;
            --accent-hover: #2563eb;
            --text: #334155;
            --text-light: #64748b;
            --border: #e2e8f0;
            --bg: #ffffff;
            --bg-subtle: #f8fafc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background: var(--bg-subtle);
            color: var(--text);
            line-height: 1.7;
            -webkit-font-smoothing: antialiased;
        }

        /* Header */
        .page-header {
            background: var(--bg);
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 100;
            backdrop-filter: blur(8px);
            background: rgba(255, 255, 255, 0.95);
        }

        .header-container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 20px;
        }

        .logo {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .logo i {
            color: var(--accent);
        }

        .header-actions {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            text-decoration: none;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-ghost {
            background: transparent;
            color: var(--text);
        }

        .btn-ghost:hover {
            background: var(--bg-subtle);
        }

        .btn-primary {
            background: var(--accent);
            color: white;
        }

        .btn-primary:hover {
            background: var(--accent-hover);
        }

        /* Document Container */
        .doc-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 0 24px 60px;
        }

        .document {
            background: var(--bg);
            border-radius: 8px;
            border: 1px solid var(--border);
            overflow: hidden;
        }

        .doc-content {
            padding: 60px 80px;
        }

        /* Document Header */
        .doc-header {
            text-align: center;
            padding-bottom: 40px;
            border-bottom: 1px solid var(--border);
            margin-bottom: 48px;
        }

        .doc-title {
            font-size: 32px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 12px;
            letter-spacing: -0.02em;
        }

        .doc-subtitle {
            font-size: 16px;
            color: var(--text-light);
            font-weight: 400;
        }

        .doc-meta {
            display: flex;
            justify-content: center;
            gap: 24px;
            margin-top: 24px;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--text-light);
            font-size: 13px;
        }

        .meta-item i {
            color: var(--accent);
            font-size: 14px;
        }

        /* Sections */
        .doc-section {
            margin-bottom: 48px;
        }

        .section-title {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-number {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 32px;
            height: 32px;
            background: var(--primary);
            color: white;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 700;
        }

        .subsection-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--primary);
            margin: 24px 0 16px;
        }

        .doc-paragraph {
            font-size: 15px;
            line-height: 1.7;
            color: var(--text);
            margin-bottom: 16px;
        }

        .doc-paragraph strong {
            font-weight: 600;
            color: var(--primary);
        }

        .doc-list {
            padding-left: 20px;
            margin: 16px 0;
        }

        .doc-list li {
            margin-bottom: 12px;
            color: var(--text);
            line-height: 1.7;
        }

        /* Info Boxes */
        .info-box,
        .warning-box,
        .important-box {
            padding: 20px;
            border-radius: 6px;
            margin: 24px 0;
            border-left: 3px solid;
        }

        .info-box {
            background: #eff6ff;
            border-color: var(--accent);
        }

        .warning-box {
            background: #fffbeb;
            border-color: #f59e0b;
        }

        .important-box {
            background: #fef2f2;
            border-color: #ef4444;
        }

        .box-title {
            font-weight: 600;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--primary);
        }

        .info-box .box-title { color: var(--accent); }
        .warning-box .box-title { color: #f59e0b; }
        .important-box .box-title { color: #ef4444; }

        /* Table */
        .doc-table {
            width: 100%;
            border-collapse: collapse;
            margin: 24px 0;
            font-size: 14px;
        }

        .doc-table thead {
            background: var(--bg-subtle);
        }

        .doc-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            color: var(--primary);
            border-bottom: 2px solid var(--border);
        }

        .doc-table td {
            padding: 12px;
            border-bottom: 1px solid var(--border);
        }

        .doc-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Contact Section */
        .contact-section {
            background: var(--bg-subtle);
            border-radius: 6px;
            padding: 32px;
            margin-top: 48px;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
            gap: 24px;
            margin-top: 24px;
        }

        .contact-item {
            display: flex;
            gap: 12px;
        }

        .contact-icon {
            width: 40px;
            height: 40px;
            background: var(--primary);
            color: white;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .contact-details h5 {
            font-size: 12px;
            font-weight: 600;
            color: var(--text-light);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .contact-details p {
            font-size: 14px;
            color: var(--primary);
            margin: 0;
            font-weight: 500;
            line-height: 1.5;
        }

        /* Signature */
        .signature-area {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 48px;
            margin-top: 64px;
            padding-top: 32px;
            border-top: 1px solid var(--border);
        }

        .signature-block {
            text-align: center;
        }

        .signature-block h5 {
            font-weight: 700;
            font-size: 12px;
            color: var(--text-light);
            margin-bottom: 16px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
        }

        .signature-line {
            border-bottom: 1px solid var(--primary);
            margin: 40px 20px 8px;
        }

        .signature-text {
            font-size: 13px;
            color: var(--text-light);
        }

        /* Footer */
        .doc-footer {
            margin-top: 48px;
            padding-top: 24px;
            border-top: 1px solid var(--border);
            text-align: center;
        }

        .doc-footer p {
            color: var(--text-light);
            font-size: 13px;
            margin: 8px 0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .doc-content {
                padding: 40px 24px;
            }

            .doc-title {
                font-size: 24px;
            }

            .signature-area {
                grid-template-columns: 1fr;
                gap: 32px;
            }

            .header-container {
                flex-direction: column;
                align-items: stretch;
            }

            .header-actions {
                width: 100%;
            }

            .btn {
                flex: 1;
                justify-content: center;
            }

            .contact-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Print */
        @media print {
            body {
                background: white;
            }

            .page-header,
            .no-print {
                display: none !important;
            }

            .doc-container {
                max-width: 100%;
                margin: 0;
                padding: 0;
            }

            .document {
                border: none;
                border-radius: 0;
            }

            .doc-content {
                padding: 20mm;
            }

            .doc-section {
                page-break-inside: avoid;
            }
        }
    </style>

    @yield('additional_styles')
</head>
<body>
    <!-- Header -->
    <header class="page-header no-print">
        <div class="header-container">
            <a href="{{ url('/') }}" class="logo">
                kitobchi.
            </a>
            <div class="header-actions">
                <a href="{{ url('/') }}" class="btn btn-ghost">
                    <i class="fas fa-arrow-left"></i>
                    Orqaga
                </a>
                <button onclick="window.print()" class="btn btn-ghost">
                    <i class="fas fa-print"></i>
                    Chop etish
                </button>
                <button onclick="downloadPDF()" class="btn btn-primary">
                    <i class="fas fa-download"></i>
                    PDF
                </button>
            </div>
        </div>
    </header>

    <!-- Main Document -->
    <div class="doc-container">
        <article class="document">
            <div class="doc-content">
                @yield('content')
            </div>
        </article>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    
    <script>
        function downloadPDF() {
            const element = document.querySelector('.doc-content');
            const opt = {
                margin: 15,
                filename: '@yield("title", "hujjat")_' + new Date().getTime() + '.pdf',
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true },
                jsPDF: { unit: 'mm', format: 'a4', orientation: 'portrait' }
            };
            
            html2pdf().set(opt).from(element).save();
        }

        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            });
        });
    </script>

    @yield('additional_scripts')
</body>
</html>