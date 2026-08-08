<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Verification</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        html, body {
            font-family: system-ui, -apple-system, sans-serif;
            background: #FAF9F5;
            color: #1E293B;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
        }
        .status-revoked {
            background: #FEF3C7;
            border: 2px solid #F59E0B;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .warning-icon {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: #F59E0B;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            font-size: 24px;
            margin-right: 12px;
        }
        .status-text {
            display: inline-block;
            vertical-align: middle;
        }
        h1 {
            font-size: 28px;
            margin: 24px 0 16px;
        }
        .details {
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            padding: 20px;
        }
        .detail-row {
            display: flex;
            padding: 12px 0;
            border-bottom: 1px solid #F1F5F9;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: bold;
            width: 40%;
            color: #64748B;
        }
        .detail-value {
            width: 60%;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="status-revoked">
            <div class="warning-icon">⚠</div>
            <div class="status-text">
                <strong>Certificate Revoked</strong>
            </div>
        </div>

        <h1>{{ $certificate->recipient_name }}</h1>

        <div class="details">
            <div class="detail-row">
                <div class="detail-label">Status</div>
                <div class="detail-value">Revoked</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Revoked On</div>
                <div class="detail-value">{{ $certificate->revoked_at->format('F j, Y') }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Certificate No.</div>
                <div class="detail-value">{{ $certificate->certificate_no }}</div>
            </div>
        </div>
    </div>
</body>
</html>
