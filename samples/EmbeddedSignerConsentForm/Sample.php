<?php
const TEMPLATE_ID = '3d28c78de8ec43ccab81a3e7dde07925cb5a1d29';

function sn_env(string $key, ?string $default = null): ?string {
    return $_ENV[$key] ?? $default;
}

function api_request(string $method, string $path, string $token = null, array $options = []): array {
    $ch = curl_init(sn_env('SIGNNOW_API_HOST') . $path);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    $headers = $options['headers'] ?? [];
    if ($token) {
        $headers[] = 'Authorization: Bearer ' . $token;
    }

    if (isset($options['json'])) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($options['json']));
    } elseif (isset($options['form_params'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($options['form_params']));
    }

    if ($headers) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $body = curl_exec($ch);
    if ($body === false) {
        throw new RuntimeException(curl_error($ch));
    }
    curl_close($ch);

    return json_decode($body, true) ?? [];
}

function authenticate(): string {
    $ch = curl_init(sn_env('SIGNNOW_API_HOST') . '/oauth2/token');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Authorization: Basic ' . sn_env('SIGNNOW_API_BASIC_TOKEN')],
        CURLOPT_POSTFIELDS => http_build_query([
            'grant_type' => 'password',
            'username' => sn_env('SIGNNOW_API_USER'),
            'password' => sn_env('SIGNNOW_API_PASSWORD'),
        ]),
    ]);
    $body = curl_exec($ch);
    if ($body === false) {
        throw new RuntimeException(curl_error($ch));
    }
    curl_close($ch);

    $data = json_decode($body, true);
    return $data['access_token'] ?? '';
}

function handleGet(): void {
    $page = $_GET['page'] ?? '';
    if ($page === 'download-container') {
        include __DIR__ . '/index.php';
        return;
    }

    $token = authenticate();

    $clone = api_request('POST', '/template/' . TEMPLATE_ID . '/copy', $token, ['json' => new stdClass()]);
    $documentId = $clone['id'] ?? '';

    $document = api_request('GET', '/document/' . $documentId, $token);
    $roleId = '';
    foreach ($document['roles'] as $role) {
        if ($role['name'] === 'Recipient 1') {
            $roleId = $role['unique_id'];
            break;
        }
    }

    $invite = api_request('POST', '/v2/documents/' . $documentId . '/embedded-invites', $token, [
        'json' => [
            'invites' => [[
                'email' => sn_env('SIGNNOW_API_SIGNER_EMAIL'),
                'role_id' => $roleId,
                'order' => 1,
                'auth_method' => 'none',
            ]],
        ],
    ]);

    $inviteId = $invite['data'][0]['id'] ?? '';
    $linkData = api_request('POST', '/v2/documents/' . $documentId . '/embedded-invites/' . $inviteId . '/link', $token, [
        'json' => [
            'auth_method' => 'none',
            'link_expiration' => 15,
        ],
    ]);

    $redirectUrl = sn_env('APP_URL') . '/samples/EmbeddedSignerConsentForm?page=download-container&document_id=' . $documentId;
    $link = $linkData['data']['link'] . '&redirect_uri=' . urlencode($redirectUrl);

    header('Location: ' . $link);
}

function handlePost(): void {
    $token = authenticate();
    $documentId = $_POST['document_id'] ?? '';

    $ch = curl_init(sn_env('SIGNNOW_API_HOST') . '/document/' . $documentId . '/download?type=collapsed');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
    ]);
    $file = curl_exec($ch);
    if ($file === false) {
        throw new RuntimeException(curl_error($ch));
    }
    curl_close($ch);

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="result.pdf"');
    echo $file;
}
