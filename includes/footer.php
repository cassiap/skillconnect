<?php
/**
 * Template do rodapé da aplicação SkillConnect
 * 
 * Este arquivo contém o HTML do rodapé padrão utilizado em todas as páginas
 * da plataforma SkillConnect, incluindo informações sobre a empresa, navegação
 * e contato.
 * 
 * @package SkillConnect
 * @version 1.0
 */

if (!function_exists('app_base_path')) {
    require_once __DIR__ . '/../config/helpers.php';
}
$_base = app_base_path();
?>
<footer class="mt-5" style="background:#0f172a;color:#e2e8f0;border-top:1px solid #1e293b;">
    <div class="container py-4">
        <div class="row">
            <div class="col-md-4 mb-3">
                <h6 class="text-white mb-2">SkillConnect</h6>
                <p class="small mb-0">Plataforma de cursos e vagas com apoio de assistente IA para orientar carreira.</p>
            </div>
            <div class="col-md-4 mb-3">
                <h6 class="text-white mb-2">Navegacao</h6>
                <div class="small">
                    <div><a href="<?= $_base ?>/user/cursos.php" style="color:#93c5fd;">Cursos</a></div>
                    <div><a href="<?= $_base ?>/user/vagas.php" style="color:#93c5fd;">Vagas</a></div>
                    <div><a href="<?= $_base ?>/user/assistente.php" style="color:#93c5fd;">Assistente IA</a></div>
                </div>
            </div>
            <div class="col-md-4 mb-3">
                <h6 class="text-white mb-2">Contato</h6>
                <div class="small">
                    <div><a href="<?= $_base ?>/user/contato.php" style="color:#93c5fd;">Fale conosco</a></div>
                    <div class="text-muted">Resposta em horario comercial</div>
                </div>
            </div>
        </div>
        <hr style="border-color:#1e293b;">
        <div class="d-flex flex-column flex-md-row justify-content-between small text-muted">
            <span>&copy; <?php echo date('Y'); ?> SkillConnect</span>
            <span>Todos os direitos reservados</span>
        </div>
    </div>
</footer>