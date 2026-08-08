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
        .status-not-found {
            background: #FEE2E2;
            border: 2px solid #EF4444;
            border-radius: 8px;
            padding: 24px;
            margin-bottom: 24px;
        }
        .error-icon {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: #EF4444;
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
        .message {
            background: white;
            border: 1px solid #E2E8F0;
            border-radius: 6px;
            padding: 20px;
            color: #64748B;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="status-not-found">
            <div class="error-icon">✕</div>
            <div class="status-text">
                <strong>Certificate Not Found</strong>
            </div>
        </div>

        <h1>Not Found</h1>

        <div class="message">
            <p>The certificate you are looking for could not be found. Please check the verification code and try again.</p>
        </div>
    </div>
</body>
</html>
