<?php
require_once __DIR__ . '/../config/db.php';

admin_check();

$id = (int) ($_GET['id'] ?? 0);
if ($id <= 0) {
    flash('error', 'Candidatura invalida.');
    redirect('candidaturas.php');
}

$stmt = $cx->prepare("SELECT curriculo_path FROM candidaturas WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

$arquivo = $row['curriculo_path'] ?? '';
if ($arquivo === '' || !preg_match('/^[a-zA-Z0-9._-]+\.pdf$/', $arquivo)) {
    flash('error', 'Curriculo nao encontrado.');
    redirect('candidaturas.php');
}

$uploadsDir = realpath(__DIR__ . '/../uploads');
$caminho = realpath(__DIR__ . '/../uploads/' . $arquivo);

if ($uploadsDir === false || $caminho === false || strpos($caminho, $uploadsDir) !== 0 || !is_file($caminho)) {
    flash('error', 'Arquivo de curriculo indisponivel.');
    redirect('candidaturas.php');
}

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($caminho));
header('Content-Disposition: inline; filename="' . basename($arquivo) . '"');
header('X-Content-Type-Options: nosniff');
readfile($caminho);
exit;
