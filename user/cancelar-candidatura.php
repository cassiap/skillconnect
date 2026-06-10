<?php
/**
 * Cancelamento de candidatura a vaga pelo aluno
 *
 * Endpoint somente POST que marca a candidatura como cancelada, mantendo o
 * histórico no banco. Só é permitido cancelar candidaturas que ainda estão
 * em andamento (enviada ou em análise) — resultados já definidos pelo
 * recrutador (aprovado/reprovado) não podem ser desfeitos pelo aluno.
 *
 * @package SkillConnect
 */

require_once __DIR__ . '/../config/db.php';

auth_check();

if (($_SESSION['perfil'] ?? '') === 'admin') {
    flash('info', 'Area exclusiva para alunos.');
    redirect(app_url('admin/admin.php'));
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('minhas-candidaturas.php');
}

if (!csrf_validate()) {
    flash('error', 'Sessao expirada. Tente novamente.');
    redirect('minhas-candidaturas.php');
}

$usuarioId = (int) ($_SESSION['user_id'] ?? 0);
$candidaturaId = (int) ($_POST['candidatura_id'] ?? 0);

if ($candidaturaId <= 0) {
    flash('error', 'Candidatura invalida.');
    redirect('minhas-candidaturas.php');
}

// Busca a candidatura garantindo que pertence ao usuário logado.
$stmt = $cx->prepare("SELECT id, status FROM candidaturas WHERE id = ? AND usuario_id = ? LIMIT 1");
$stmt->bind_param("ii", $candidaturaId, $usuarioId);
$stmt->execute();
$candidatura = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$candidatura) {
    flash('error', 'Candidatura nao encontrada.');
    redirect('minhas-candidaturas.php');
}

$cancelaveis = [STATUS_CAND_ENVIADA, STATUS_CAND_ANALISE];
if (!in_array($candidatura['status'], $cancelaveis, true)) {
    flash('info', 'Esta candidatura nao pode mais ser cancelada.');
    redirect('minhas-candidaturas.php');
}

try {
    $upd = $cx->prepare("UPDATE candidaturas SET status = 'cancelada' WHERE id = ?");
    $upd->bind_param("i", $candidaturaId);
    $upd->execute();
    $upd->close();

    flash('success', 'Candidatura cancelada com sucesso.');
} catch (mysqli_sql_exception $e) {
    error_log('cancelar-candidatura.php update error: ' . $e->getMessage());
    flash('error', 'Erro ao cancelar a candidatura. Tente novamente.');
}

redirect('minhas-candidaturas.php');
