<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="robots" content="noindex, nofollow">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }} — API Docs</title>

    {{--
      Swagger UI is loaded from a public CDN. It is a fully client-side viewer
      that fetches /api/openapi.json and renders the interactive documentation.
      The rest of the app has zero runtime dependency on this asset — the JSON
      spec is served directly at /api/openapi.json for programmatic consumers.
    --}}
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui.css">
    <style>
        body { margin: 0; background: #fafafa; }
        .top-bar {
            background: #0f4c81; color: #fff;
            padding: 12px 24px; display: flex; justify-content: space-between; align-items: center;
            font-family: system-ui, sans-serif;
        }
        .top-bar .brand { font-weight: 700; font-size: 16px; letter-spacing: 1px; }
        .top-bar a { color: #cbd5e1; text-decoration: none; font-size: 13px; }
        .top-bar a:hover { color: #fff; }
        .info-strip {
            background: #eff6ff; border-bottom: 1px solid #dbeafe;
            padding: 12px 24px; font-family: system-ui, sans-serif; font-size: 13px; color: #1e3a8a;
        }
    </style>
</head>
<body>
    <div class="top-bar">
        <div class="brand">{{ config('app.name') }} · API</div>
        <div><a href="/api/openapi.json">Download JSON</a></div>
    </div>
    <div class="info-strip">
        <strong>Base URL:</strong> {{ url('/') }}
        &nbsp;·&nbsp; <strong>Auth:</strong> Bearer token (mint at <code>/admin/api-tokens</code>)
        &nbsp;·&nbsp; <strong>Rate limit:</strong> 30/min anonymous, 1000/min authenticated
    </div>
    <div id="swagger-ui"></div>

    <script src="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui-bundle.js" crossorigin></script>
    <script src="https://unpkg.com/swagger-ui-dist@5.17.14/swagger-ui-standalone-preset.js" crossorigin></script>
    <script>
        window.onload = function () {
            window.ui = SwaggerUIBundle({
                url: @json(url('/api/openapi.json')),
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset,
                ],
                plugins: [SwaggerUIBundle.plugins.DownloadUrl],
                layout: 'StandaloneLayout',
                persistAuthorization: true,
            });
        };
    </script>
</body>
</html>
