<?php
/**
 * Bootstrap para a suíte de testes unitários do SkillConnect.
 *
 * Define superglobais mínimas necessárias para as funções de URL
 * e carrega helpers.php (que por sua vez carrega constants.php
 * e inicia a sessão PHP, ambos seguros em modo CLI).
 */

// Superglobais mínimas para app_base_path() / app_url()
$_SERVER['HTTPS']        = 'off';
$_SERVER['SERVER_PORT']  = '80';
$_SERVER['HTTP_HOST']    = 'localhost';
$_SERVER['SCRIPT_NAME']  = '/index.php';
$_SERVER['REQUEST_URI']  = '/';
$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/..');

// helpers.php já faz require_once de constants.php e inicia sessão
require_once __DIR__ . '/../config/helpers.php';
