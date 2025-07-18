<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Impressão')</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            background: white;
        }

        .print-container {
            max-width: 210mm;
            min-height: 297mm;
            margin: 0 auto;
            padding: 20mm;
            background: white;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }

        .header h1 {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 14px;
            color: #666;
        }

        .section {
            margin-bottom: 25px;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px solid #ddd;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .info-item {
            display: flex;
            flex-direction: column;
        }

        .info-label {
            font-weight: bold;
            color: #555;
            margin-bottom: 3px;
            font-size: 11px;
            text-transform: uppercase;
        }

        .info-value {
            font-size: 13px;
            color: #333;
            border-bottom: 1px dotted #ccc;
            padding-bottom: 2px;
        }

        .full-width {
            grid-column: 1 / -1;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            border: 1px solid #ddd;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
            border-color: #ffeaa7;
        }

        .status-exam_scheduled {
            background: #d4edda;
            color: #155724;
            border-color: #b8daff;
        }

        .status-exam_ready {
            background: #cce5ff;
            color: #004085;
            border-color: #b3d9ff;
        }

        .status-completed {
            background: #d1ecf1;
            color: #0c5460;
            border-color: #bee5eb;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
            border-color: #f5c6cb;
        }

        .timeline {
            margin-top: 20px;
        }

        .timeline-item {
            display: flex;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .timeline-date {
            width: 120px;
            font-size: 11px;
            color: #666;
            flex-shrink: 0;
        }

        .timeline-content {
            flex: 1;
        }

        .timeline-title {
            font-weight: bold;
            font-size: 12px;
            color: #333;
            margin-bottom: 3px;
        }

        .timeline-description {
            font-size: 11px;
            color: #666;
            line-height: 1.3;
        }

        .documents-list {
            margin-top: 10px;
        }

        .document-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #eee;
        }

        .document-info {
            flex: 1;
        }

        .document-name {
            font-weight: bold;
            font-size: 12px;
            color: #333;
        }

        .document-meta {
            font-size: 10px;
            color: #666;
            margin-top: 2px;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            font-size: 11px;
            color: #666;
        }

        .signature-area {
            margin-top: 40px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }

        .signature-box {
            text-align: center;
        }

        .signature-line {
            border-bottom: 1px solid #333;
            margin-bottom: 8px;
            height: 40px;
        }

        .signature-label {
            font-size: 11px;
            color: #666;
        }

        @media print {
            body {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }

            .print-container {
                margin: 0;
                padding: 15mm;
                max-width: none;
            }

            .section {
                page-break-inside: avoid;
            }

            .timeline-item {
                page-break-inside: avoid;
            }
        }

        @page {
            size: A4;
            margin: 15mm 10mm;
            /* Remove browser headers and footers */
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        @page :first {
            margin-top: 10mm;
        }

        @page :left {
            margin-left: 15mm;
            margin-right: 10mm;
        }

        @page :right {
            margin-left: 10mm;
            margin-right: 15mm;
        }

        /* Additional print optimizations */
        html, body {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Hide any potential browser print elements */
        @media print {
            .no-print {
                display: none !important;
            }

            /* Ensure content fills the page properly */
            .print-container {
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
            }

            /* Remove any borders or backgrounds that might interfere */
            * {
                -webkit-box-shadow: none !important;
                box-shadow: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="print-container">
        @yield('content')
    </div>

    <script>
        // Auto-print when page loads with better control
        window.addEventListener('load', function() {
            setTimeout(function() {
                // Try to remove headers/footers through print settings
                if (window.chrome && window.chrome.runtime) {
                    // Chrome specific
                    window.print();
                } else {
                    // Other browsers
                    window.print();
                }
            }, 500);
        });

        // Additional print optimizations
        window.addEventListener('beforeprint', function() {
            // Hide any elements that shouldn't be printed
            document.body.style.margin = '0';
            document.body.style.padding = '0';
        });

        window.addEventListener('afterprint', function() {
            // Optional: close window after printing
            // window.close();
        });

        // For better browser support
        if (window.matchMedia) {
            var mediaQueryList = window.matchMedia('print');
            mediaQueryList.addListener(function(mql) {
                if (mql.matches) {
                    // Before print
                    document.body.style.margin = '0';
                    document.body.style.padding = '0';
                } else {
                    // After print
                    // Optional cleanup
                }
            });
        }
    </script>
</body>
</html>
