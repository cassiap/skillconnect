<?php
/**
 * Cancelamento de inscrição em curso pelo aluno
 *
 * Endpoint somente POST que marca a inscrição como cancelada, mantendo o
 * histórico no banco. A inscrição pode ser reativada depois via inscrever.php.
 * Inscrições concluídas não podem ser canceladas.
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
    redirect('meus-cursos.php');
}

if (!csrf_validate()) {
    flash('error', 'Sessao expirada. Tente novamente.');
    redirect('meus-cursos.php');
}

$usuarioId = (int) ($_SESSION['user_id'] ?? 0);
$inscricaoId = (int) ($_POST['inscricao_id'] ?? 0);

if ($inscricaoId <= 0) {
    flash('error', 'Inscricao invalida.');
    redirect('meus-cursos.php');
}

// Busca a inscrição garantindo que pertence ao usuário logado.
$stmt = $cx->prepare("SELECT id, status FROM inscricoes_cursos WHERE id = ? AND usuario_id = ? LIMIT 1");
$stmt->bind_param("ii", $inscricaoId, $usuarioId);
$stmt->execute();
$inscricao = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$inscricao) {
    flash('error', 'Inscricao nao encontrada.');
    redirect('meus-cursos.php');
}

if ($inscricao['status'] === STATUS_INSC_CONCLUIDO) {
    flash('info', 'Cursos concluidos nao podem ser cancelados.');
    redirect('meus-cursos.php');
}

if ($inscricao['status'] === STATUS_INSC_CANCELADO) {
    flash('info', 'Esta inscricao ja esta cancelada.');
    redirect('meus-cursos.php');
}

try {
    $upd = $cx->prepare("UPDATE inscricoes_cursos SET status = 'cancelado' WHERE id = ?");
    $upd->bind_param("i", $inscricaoId);
    $upd->execute();
    $upd->close();

    flash('success', 'Inscricao cancelada. Voce pode se inscrever novamente quando quiser.');
} catch (mysqli_sql_exception $e) {
    error_log('cancelar-inscricao.php update error: ' . $e->getMessage());
    flash('error', 'Erro ao cancelar a inscricao. Tente novamente.');
}

redirect('meus-cursos.php');
