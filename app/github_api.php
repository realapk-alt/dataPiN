<?php
// Helper for GitHub API calls (no token here)
function gh_api_request($method, $url, $token, $headers = [], $data = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    $all_headers = array_merge(['Authorization: token ' . $token, 'User-Agent: PHP-GH-Client'], $headers);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $all_headers);
    if ($data !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_FAILONERROR, false);
    $resp = curl_exec($ch);
    $info = curl_getinfo($ch);
    if (curl_errno($ch)) {
        $err = curl_error($ch);
        curl_close($ch);
        return ['error' => $err];
    }
    curl_close($ch);
    $decoded = json_decode($resp, true);
    return ['http_code' => $info['http_code'], 'body' => $decoded, 'raw' => $resp];
}
function gh_get_or_create_release($user, $repo, $token, $tag = 'auto-upload') {
    $api = "https://api.github.com/repos/$user/$repo/releases";
    $res = gh_api_request('GET', $api, $token);
    if (isset($res['error'])) return ['error' => $res['error']];
    if (is_array($res['body'])) {
        foreach ($res['body'] as $r) {
            if (isset($r['tag_name']) && $r['tag_name'] === $tag) {
                return ['release' => $r];
            }
        }
    }
    $payload = json_encode(['tag_name' => $tag, 'name' => $tag, 'prerelease' => false]);
    $r2 = gh_api_request('POST', $api, $token, ['Content-Type: application/json'], $payload);
    if (isset($r2['error'])) return ['error' => $r2['error']];
    if (isset($r2['body']['id'])) return ['release' => $r2['body']];
    return ['error' => 'Failed to create release: ' . ($r2['raw'] ?? 'unknown')];
}
?>
