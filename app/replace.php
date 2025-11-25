<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/github_api.php';
header('Content-Type: application/json; charset=utf-8');
$token = GITHUB_TOKEN;
if (!$token || $token === 'PASTE_YOUR_TOKEN_HERE') { http_response_code(400); echo json_encode(['message'=>'GitHub token not configured.']); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['message'=>'Method not allowed']); exit; }
$id = $_POST['id'] ?? null;
if (!$id) { http_response_code(400); echo json_encode(['message'=>'No id']); exit; }
if (!isset($_FILES['file'])) { http_response_code(400); echo json_encode(['message'=>'No file']); exit; }
$file = $_FILES['file'];
$user = GITHUB_USER; $repo = GITHUB_REPO;
$delurl = "https://api.github.com/repos/$user/$repo/releases/assets/" . intval($id);
$dres = gh_api_request('DELETE', $delurl, $token);
if (isset($dres['error'])) { http_response_code(500); echo json_encode(['message'=>'Delete error: '.$dres['error']]); exit; }
if ($dres['http_code'] != 204) { http_response_code(500); echo json_encode(['message'=>'Failed to delete old asset','raw'=>$dres['raw'] ?? null]); exit; }
$orig = basename($file['name']); $safe = preg_replace('/[^A-Za-z0-9._-]/','_',$orig);
$gr = gh_get_or_create_release($user,$repo,$token);
if (isset($gr['error'])) { http_response_code(500); echo json_encode(['message'=>'Release error: '.$gr['error']]); exit; }
$release = $gr['release']; $upload_url_t = $release['upload_url'] ?? null;
if (!$upload_url_t) { http_response_code(500); echo json_encode(['message'=>'No upload URL']); exit; }
$upload_url = preg_replace('/\{.*\}$/','',$upload_url_t) . '?name=' . rawurlencode($safe);
$data = file_get_contents($file['tmp_name']); $mime = mime_content_type($file['tmp_name']) ?: 'application/octet-stream';
$h = ['Content-Type: ' . $mime, 'Content-Length: ' . strlen($data)];
$res = gh_api_request('POST', $upload_url, $token, $h, $data);
if (isset($res['error'])) { http_response_code(500); echo json_encode(['message'=>'Upload error: '.$res['error']]); exit; }
if (isset($res['body']['browser_download_url'])) { echo json_encode(['browser_download_url'=>$res['body']['browser_download_url'],'name'=>$res['body']['name'],'id'=>$res['body']['id']]); exit; }
http_response_code(500); echo json_encode(['message'=>'Unknown upload failure','raw'=>$res['raw'] ?? null]);
?>
