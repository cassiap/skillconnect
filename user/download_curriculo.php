<?php
/**
 * Script para download de currículo
 * 
 * Este arquivo permite que usuários autenticados façam download dos seus próprios currículos
 * enviados nas candidaturas. Inclui validações de segurança para prevenir acesso não autorizado.
 * 
 * @author Sistema de Vagas
 * @version 1.0
 */

require_once __DIR__ . '/../config/db.php';

auth_check();

if (($_SESSION['perfil'] ?? '') === 'admin') {
    flash('info', 'Area exclusiva para alunos.');
    redirect(app_url('admin/admin.php'));
}

$id = (int) ($_GET['id'] ?? 0);
$usuarioId = (int) ($_SESSION['user_id'] ?? 0);

if ($id <= 0) {
    flash('error', 'Curriculo invalido.');
    redirect('minhas-candidaturas.php');
}

$stmt = $cx->prepare("SELECT curriculo_path FROM candidaturas WHERE id = ? AND usuario_id = ? LIMIT 1");
$stmt->bind_param("ii", $id, $usuarioId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

$arquivo = $row['curriculo_path'] ?? '';
if ($arquivo === '' || !preg_match('/^[a-zA-Z0-9._-]+\.pdf$/', $arquivo)) {
    flash('error', 'Curriculo nao encontrado.');
    redirect('minhas-candidaturas.php');
}

$uploadsDir = realpath(__DIR__ . '/../uploads');
$caminho = realpath(__DIR__ . '/../uploads/' . $arquivo);

if ($uploadsDir === false || $caminho === false || strpos($caminho, $uploadsDir) !== 0 || !is_file($caminho)) {
    flash('error', 'Arquivo indisponivel.');
    redirect('minhas-candidaturas.php');
}

header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($caminho));
header('Content-Disposition: inline; filename="' . basename($arquivo) . '"');
header('X-Content-Type-Options: nosniff');
readfile($caminho);
exit;
