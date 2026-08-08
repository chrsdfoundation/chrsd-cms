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
        .status-valid {
            background: #ECFDF5;
            border: 2px solid #10B981;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .check-icon {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: #10B981;
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
            margin-bottom: 20px;
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
        .verified-by {
            text-align: center;
            padding: 20px;
            background: #F0F9FF;
            border-radius: 6px;
            font-size: 14px;
            color: #64748B;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="status-valid">
            <div class="check-icon">✓</div>
            <div class="status-text">
                <strong>Certificate Verified</strong>
            </div>
        </div>

        <h1>{{ $certificate->recipient_name }}</h1>

        <div class="details">
            <div class="detail-row">
                <div class="detail-label">Program</div>
                <div class="detail-value">{{ $certificate->program_name }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Certificate Title</div>
                <div class="detail-value">{{ $certificate->certificate_title }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Issue Date</div>
                <div class="detail-value">{{ $certificate->issued_on->format('F j, Y') }}</div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Certificate No.</div>
                <div class="detail-value">{{ $certificate->certificate_no }}</div>
            </div>
        </div>

        <div class="verified-by">
            ✓ Verified by CHRS Development
        </div>
    </div>
</body>
</html>
