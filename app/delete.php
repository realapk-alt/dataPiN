<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/github_api.php';
header('Content-Type: application/json; charset=utf-8');
$token = GITHUB_TOKEN;
if (!$token || $token === 'PASTE_YOUR_TOKEN_HERE') {
    http_response_code(400);
    echo json_encode(['message'=>'GitHub token not configured.']);
    exit;
}
$id = $_POST['id'] ?? null;
if (!$id) { http_response_code(400); echo json_encode(['message'=>'No id']); exit; }
$user = GITHUB_USER; $repo = GITHUB_REPO;
$url = "https://api.github.com/repos/$user/$repo/releases/assets/" . intval($id);
$res = gh_api_request('DELETE', $url, $token);
if (isset($res['error'])) { http_response_code(500); echo json_encode(['message'=>'Delete error: '.$res['error']]); exit; }
if ($res['http_code'] == 204) { echo json_encode(['status'=>'ok']); exit; }
http_response_code(500); echo json_encode(['message'=>'Delete failed','raw'=>$res['raw'] ?? null]);
?>
