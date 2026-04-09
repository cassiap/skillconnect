<?php
/**
 * Script de logout do sistema
 * 
 * Este arquivo é responsável por encerrar a sessão do usuário,
 * limpar os dados de sessão e redirecionar para a página de login.
 * 
 * @author Sistema
 * @version 1.0
 */

require_once __DIR__ . '/../config/helpers.php';

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

session_destroy();
redirect('login.php');