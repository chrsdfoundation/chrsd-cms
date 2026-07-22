<?php

/*
|--------------------------------------------------------------------------
| CHRSD CMS — OpenAPI 3.1 spec
|--------------------------------------------------------------------------
|
| Hand-authored (not generated) because the API surface is small and stable:
| 3 endpoints total. Regenerated tooling would be more code to maintain than
| this file. When you add a new public endpoint, add a `paths` entry here
| and a matching test in `OpenApiSpecTest`.
|
| Served as JSON at /api/openapi.json (see routes/web.php).
|
*/

return [

    'openapi' => '3.1.0',

    'info' => [
        'title' => 'CHRSD CMS — Public Verify API',
        'version' => '1.0.0',
        'description' => "Programmatic verification of employment certificates, official letters, and employee records issued by the CHRSD CMS.\n\n"
                       . "Three input paths:\n\n"
                       . "1. **By verification hash** (from a printed QR code) — `GET /api/verify/{hash}`\n"
                       . "2. **By PDF content** (upload the file itself) — `POST /api/verify/pdf`\n\n"
                       . 'All endpoints are anonymous-accessible with a modest per-IP rate limit. '
                       . 'Trusted integrators can register a Sanctum bearer token to raise the limit '
                       . 'to 1000 req/min and get per-token audit logs.',
        'contact' => [
            'name' => env('APP_NAME', 'CHRSD CMS'),
            'email' => env('MAIL_FROM_ADDRESS', 'noreply@chrsd.org'),
        ],
        'license' => [
            'name' => 'Proprietary — CHRSD internal',
        ],
    ],

    'servers' => [
        ['url' => env('APP_URL', 'http://localhost'), 'description' => 'Current deployment'],
    ],

    'security' => [
        [],                                    // anonymous allowed
        ['sanctumBearerToken' => []],          // authenticated preferred
    ],

    'tags' => [
        ['name' => 'Verification', 'description' => 'Look up a document\'s current status.'],
    ],

    'paths' => [

        '/api/verify/{hash}' => [
            'get' => [
                'tags' => ['Verification'],
                'operationId' => 'verifyByHash',
                'summary' => 'Verify by hash',
                'description' => 'Resolves the 64-char HMAC-SHA256 verification hash printed on the document QR to the current status of the underlying employee/certificate/letter.',
                'parameters' => [
                    [
                        'name' => 'hash',
                        'in' => 'path',
                        'required' => true,
                        'description' => '64 lowercase hex characters.',
                        'schema' => ['type' => 'string', 'pattern' => '^[a-f0-9]{64}$', 'minLength' => 64, 'maxLength' => 64],
                        'example' => '43ca9637cb322d62403a1f98e9b84148b2414949a876c9b0d4737a074ee1a050',
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'Document resolved.',
                        'content' => [
                            'application/json' => [
                                'schema' => ['$ref' => '#/components/schemas/VerificationEnvelope'],
                                'example' => [
                                    'found' => true,
                                    'snapshot' => [
                                        'serial' => 'CERT-2026-000005',
                                        'kind' => 'Certificate',
                                        'status' => 'valid',
                                        'issued_on' => '2026-07-03',
                                        'is_valid' => true,
                                    ],
                                ],
                            ],
                        ],
                    ],
                    '404' => ['$ref' => '#/components/responses/NotFound'],
                    '429' => ['$ref' => '#/components/responses/RateLimited'],
                ],
            ],
        ],

        '/api/verify/pdf' => [
            'post' => [
                'tags' => ['Verification'],
                'operationId' => 'verifyByPdf',
                'summary' => 'Verify by PDF content',
                'description' => "Upload a PDF that was issued from this system and receive the same envelope as `verifyByHash`. The PDF's bytes are HMAC-signed at generation time, so any single-byte modification invalidates the match. Returns `signature` (the last 12 hex chars of the HMAC) as a human-verifiable fingerprint.",
                'requestBody' => [
                    'required' => true,
                    'content' => [
                        'multipart/form-data' => [
                            'schema' => [
                                'type' => 'object',
                                'required' => ['pdf'],
                                'properties' => [
                                    'pdf' => [
                                        'type' => 'string',
                                        'format' => 'binary',
                                        'description' => 'The PDF file (max 20 MB).',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'PDF matched a document.',
                        'content' => ['application/json' => ['schema' => ['$ref' => '#/components/schemas/VerificationEnvelope']]],
                    ],
                    '404' => ['$ref' => '#/components/responses/NotFound'],
                    '422' => ['$ref' => '#/components/responses/ValidationError'],
                    '429' => ['$ref' => '#/components/responses/RateLimited'],
                ],
            ],
        ],

        '/verify/{hash}' => [
            'get' => [
                'tags' => ['Verification'],
                'operationId' => 'verifyByHashHtml',
                'summary' => 'Public HTML verify page',
                'description' => 'Same hash lookup, HTML output for human eyeballs. Includes noindex + no-referrer headers so QR-URL sharing does not leak into search engines.',
                'parameters' => [
                    [
                        'name' => 'hash',
                        'in' => 'path',
                        'required' => true,
                        'schema' => ['type' => 'string', 'pattern' => '^[a-f0-9]{64}$'],
                    ],
                ],
                'responses' => [
                    '200' => [
                        'description' => 'HTML page',
                        'content' => ['text/html' => ['schema' => ['type' => 'string']]],
                    ],
                    '404' => ['description' => 'Not found or malformed hash'],
                ],
            ],
        ],
    ],

    'components' => [

        'securitySchemes' => [
            'sanctumBearerToken' => [
                'type' => 'http',
                'scheme' => 'bearer',
                'description' => 'Sanctum personal access token. Admins mint them at /admin/api-tokens.',
                'bearerFormat' => 'plaintext',
            ],
        ],

        'schemas' => [

            'VerificationEnvelope' => [
                'type' => 'object',
                'required' => ['found'],
                'properties' => [
                    'found' => ['type' => 'boolean'],
                    'signature' => [
                        'type' => 'string',
                        'description' => 'Only present on PDF verifications — a 12-char human fingerprint of the HMAC signature.',
                        'example' => 'A1B2C3D4E5F6',
                    ],
                    'snapshot' => ['$ref' => '#/components/schemas/PublicSnapshot'],
                ],
            ],

            'PublicSnapshot' => [
                'type' => 'object',
                'required' => ['serial', 'kind', 'status', 'is_valid'],
                'properties' => [
                    'serial' => ['type' => 'string', 'example' => 'CERT-2026-000005'],
                    'kind' => ['type' => 'string', 'enum' => ['Employee', 'Certificate', 'OfficialLetter']],
                    'status' => ['type' => 'string', 'enum' => ['valid', 'invalid', 'revoked', 'expired']],
                    'issued_on' => ['type' => 'string', 'format' => 'date', 'nullable' => true],
                    'valid_until' => ['type' => 'string', 'format' => 'date', 'nullable' => true],
                    'revoked_at' => ['type' => 'string', 'format' => 'date', 'nullable' => true],
                    'revocation_reason' => ['type' => 'string', 'nullable' => true],
                    'is_valid' => ['type' => 'boolean'],
                ],
            ],

        ],

        'responses' => [

            'NotFound' => [
                'description' => 'No matching document was found.',
                'content' => ['application/json' => ['schema' => [
                    'type' => 'object',
                    'properties' => [
                        'found' => ['type' => 'boolean', 'example' => false],
                        'reason' => ['type' => 'string', 'example' => 'no_matching_document'],
                    ],
                ]]],
            ],

            'ValidationError' => [
                'description' => 'The request payload failed validation.',
                'content' => ['application/json' => ['schema' => [
                    'type' => 'object',
                    'properties' => [
                        'message' => ['type' => 'string'],
                        'errors' => ['type' => 'object', 'additionalProperties' => ['type' => 'array', 'items' => ['type' => 'string']]],
                    ],
                ]]],
            ],

            'RateLimited' => [
                'description' => 'Rate limit exceeded (anonymous: 30 req/min per IP, authenticated: 1000 req/min per token).',
                'headers' => [
                    'X-RateLimit-Limit' => ['schema' => ['type' => 'integer']],
                    'X-RateLimit-Remaining' => ['schema' => ['type' => 'integer']],
                    'Retry-After' => ['schema' => ['type' => 'integer']],
                ],
            ],

        ],

    ],

];
