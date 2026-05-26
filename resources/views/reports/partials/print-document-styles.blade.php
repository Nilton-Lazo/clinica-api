<style>
    @page {
        size: A4 portrait;
        margin: 0;
    }

    html, body {
        margin: 0;
        padding: 0;
        width: 210mm;
    }

    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 2.5mm;
        line-height: 1.4;
        color: #1e293b;
        background: #ffffff;
    }

    table {
        border-collapse: collapse;
    }

    table.doc-page {
        width: 210mm;
        min-height: 297mm;
        border: 0.35mm solid #cbd5e1;
        background: #ffffff;
    }

    table.doc-page > tbody > tr > td.doc-body {
        padding: 14mm 14mm 0 14mm;
        vertical-align: top;
    }

    table.doc-page > tbody > tr > td.doc-footer {
        padding: 3mm 14mm 14mm 14mm;
        vertical-align: bottom;
    }

    table.doc-frame {
        width: 100%;
        border: 0.35mm solid #cbd5e1;
    }

    table.doc-frame > tbody > tr > td {
        padding: 2.7mm 3.2mm;
        vertical-align: top;
        border: none;
    }

    table.report-banner {
        width: 100%;
        margin: 0 0 3.7mm 0;
    }

    table.report-banner td {
        background: #0f4c75;
        color: #ffffff;
        text-align: center;
        padding: 2.7mm 3.2mm;
        border: none;
    }

    .report-banner__title {
        font-size: 4mm;
        font-weight: bold;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        line-height: 1.25;
    }

    .report-banner__subtitle {
        font-size: 2.4mm;
        margin-top: 1.1mm;
        color: #bfdbfe;
        letter-spacing: 0.02em;
    }

    table.section-head {
        width: 100%;
        margin: 0 0 2.1mm 0;
    }

    table.section-head td {
        font-size: 2.5mm;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #0f4c75;
        padding: 0 0 1.3mm 0;
        border: none;
        border-bottom: 0.5mm solid #0f4c75;
    }

    table.section-spacer {
        width: 100%;
    }

    table.section-spacer td {
        height: 3.7mm;
        font-size: 1px;
        line-height: 1px;
        border: none;
        padding: 0;
    }

    table.field-sheet {
        width: 100%;
        font-size: 2.5mm;
        border: 0.35mm solid #cbd5e1;
    }

    table.field-sheet td {
        border: 0.35mm solid #cbd5e1;
        padding: 1.6mm 2.1mm;
        vertical-align: top;
    }

    table.field-sheet td.lbl {
        width: 24%;
        font-weight: bold;
        font-size: 2.25mm;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        color: #475569;
        background: #f1f5f9;
    }

    table.field-sheet td.val {
        color: #0f172a;
    }

    table.meta-strip {
        width: 100%;
        background: #f8fafc;
        border: 0.35mm solid #cbd5e1;
    }

    table.meta-strip td {
        padding: 1.1mm 2.1mm;
        border-right: 0.35mm solid #e2e8f0;
        vertical-align: middle;
        text-align: center;
    }

    table.meta-strip td:last-child {
        border-right: none;
    }

    table.meta-cell {
        width: 100%;
    }

    table.meta-cell td.meta-lbl {
        font-size: 1.85mm;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #64748b;
        text-align: center;
        padding: 0;
        border: none;
        line-height: 1.2;
    }

    table.meta-cell td.meta-val {
        font-size: 2.4mm;
        font-weight: bold;
        color: #0f172a;
        text-align: center;
        padding: 0.3mm 0 0 0;
        border: none;
        line-height: 1.2;
    }

    table.footer-sheet {
        width: 100%;
        border: 0.35mm solid #cbd5e1;
        background: #ffffff;
    }

    table.footer-sheet td {
        padding: 2.1mm 3.2mm;
        border: none;
    }

    .muted { color: #64748b; }
    .tabular { font-variant-numeric: tabular-nums; }
    .text-bold { font-weight: bold; }

    @media screen {
        html, body {
            width: auto;
            min-height: 100%;
            background: #e2e8f0;
        }

        body {
            padding: 5mm 0;
        }

        table.doc-page {
            margin: 0 auto;
            box-shadow: 0 1mm 2.5mm rgba(15, 23, 42, 0.12);
        }
    }

    @media print {
        html, body {
            width: 210mm;
            background: #ffffff !important;
            padding: 0;
        }

        table.doc-page {
            margin: 0;
            box-shadow: none;
            page-break-after: auto;
        }

        body {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
    }
</style>
