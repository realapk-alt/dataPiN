<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/github_api.php';
header('Content-Type: application/json; charset=utf-8');
$token = GITHUB_TOKEN;
if (!$token || $token === 'PASTE_YOUR_TOKEN_HERE') {
    http_response_code(400);
    echo json_encode(['message'=>'GitHub token not configured. Edit app/config.php.','files'=>[]]);
    exit;
}
$user = GITHUB_USER; $repo = GITHUB_REPO;
$gr = gh_get_or_create_release($user, $repo, $token);
if (isset($gr['error'])) { http_response_code(500); echo json_encode(['message'=>'Release error: '.$gr['error'],'files'=>[]]); exit; }
$release = $gr['release'];
$assets_url = $release['assets_url'] ?? null;
if (!$assets_url) { echo json_encode(['files'=>[]]); exit; }
$res = gh_api_request('GET', $assets_url, $token);
if (isset($res['error'])) { http_response_code(500); echo json_encode(['message'=>'Assets error: '.$res['error'],'files'=>[]]); exit; }
$assets = $res['body'] ?? [];
$out = [];
foreach ($assets as $a) {
    $out[] = ['id'=>$a['id'],'name'=>$a['name'],'size'=>$a['size'],'browser_download_url'=>$a['browser_download_url']];
}
echo json_encode(['files'=>$out]);
?>
