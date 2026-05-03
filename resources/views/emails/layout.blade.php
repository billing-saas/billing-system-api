<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: Arial, sans-serif;
            font-size: 14px;
            color: #1e293b;
            background: #f8fafc;
        }

        .wrapper {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }

        .header {
            background: #0f172a;
            padding: 28px 40px;
        }

        .header-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .brand {
            font-size: 20px;
            font-weight: bold;
            color: #ffffff;
        }

        .brand-dot { color: #3b82f6; }

        .accent-bar {
            height: 3px;
            background: #3b82f6;
        }

        .body {
            padding: 40px;
        }

        .greeting {
            font-size: 22px;
            font-weight: bold;
            color: #0f172a;
            margin-bottom: 12px;
        }

        .text {
            font-size: 14px;
            color: #64748b;
            line-height: 1.7;
            margin-bottom: 16px;
        }

        .invoice-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px 24px;
            margin: 24px 0;
        }

        .invoice-card table {
            width: 100%;
            border-collapse: collapse;
        }

        .invoice-card td {
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 13px;
        }

        .invoice-card tr:last-child td {
            border-bottom: none;
        }

        .invoice-card .label {
            color: #94a3b8;
            width: 40%;
        }

        .invoice-card .value {
            color: #0f172a;
            font-weight: 600;
            text-align: right;
        }

        .invoice-card .total-label {
            color: #0f172a;
            font-weight: bold;
            font-size: 14px;
        }

        .invoice-card .total-value {
            color: #1d4ed8;
            font-weight: bold;
            font-size: 16px;
            text-align: right;
        }

        .btn {
            display: inline-block;
            padding: 14px 32px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            text-align: center;
            margin: 8px 0;
        }

        .btn-primary {
            background: #1d4ed8;
            color: #ffffff !important;
        }

        .btn-success {
            background: #15803d;
            color: #ffffff !important;
        }

        .btn-warning {
            background: #dc2626;
            color: #ffffff !important;
        }

        .btn-wrapper {
            text-align: center;
            margin: 28px 0;
        }

        .alert-box {
            border-radius: 8px;
            padding: 16px 20px;
            margin: 20px 0;
            font-size: 13px;
            line-height: 1.6;
        }

        .alert-warning {
            background: #fef9c3;
            border-left: 4px solid #ca8a04;
            color: #854d0e;
        }

        .alert-danger {
            background: #fef2f2;
            border-left: 4px solid #dc2626;
            color: #991b1b;
        }

        .alert-success {
            background: #f0fdf4;
            border-left: 4px solid #16a34a;
            color: #166534;
        }

        .divider {
            height: 1px;
            background: #f1f5f9;
            margin: 28px 0;
        }

        .footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 24px 40px;
            text-align: center;
        }

        .footer-text {
            font-size: 12px;
            color: #94a3b8;
            line-height: 1.6;
        }

        .footer-brand {
            font-size: 13px;
            font-weight: bold;
            color: #475569;
            margin-bottom: 6px;
        }
    </style>
</head>
<body>
    <div class="wrapper">

        {{-- Header --}}
        <div class="header">
            <div class="brand">
                Facturo<span class="brand-dot">.</span>
            </div>
        </div>
        <div class="accent-bar"></div>

        {{-- Body --}}
        <div class="body">
            @yield('content')
        </div>

        {{-- Footer --}}
        <div class="footer">
            <div class="footer-brand">
                Facturo<span style="color: #3b82f6;">.</span>
            </div>
            <div class="footer-text">
                This email was sent automatically by Facturo.<br>
                Please do not reply to this email.
            </div>
        </div>

    </div>
</body>
</html>