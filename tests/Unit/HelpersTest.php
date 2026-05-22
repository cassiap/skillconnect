<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Testes unitários para as funções auxiliares globais (config/helpers.php).
 *
 * Cobre: geração e validação de token CSRF, mensagens flash,
 * geração de URLs, e constante de timeout de sessão.
 *
 * Funções que chamam redirect() + exit (auth_check, admin_check,
 * session_check_timeout no caminho de expiração) não são testadas
 * aqui — dependem de estado de sessão e HTTP que pertencem a
 * testes de integração.
 */
class HelpersTest extends TestCase
{
    protected function setUp(): void
    {
        // Garante sessão limpa a cada teste
        $_SESSION = [];
        $_POST    = [];
    }

    // ===== SESSION_TIMEOUT =====

    public function testSessionTimeoutIsDefined(): void
    {
        $this->assertTrue(defined('SESSION_TIMEOUT'));
    }

    public function testSessionTimeoutIsPositiveInteger(): void
    {
        $this->assertIsInt(SESSION_TIMEOUT);
        $this->assertGreaterThan(0, SESSION_TIMEOUT);
    }

    public function testSessionTimeoutIs30Minutes(): void
    {
        $this->assertSame(1800, SESSION_TIMEOUT);
    }

    // ===== CSRF =====

    public function testCsrfTokenReturnsHexString(): void
    {
        $token = csrf_token();
        $this->assertIsString($token);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token, 'Token deve ter 64 caracteres hexadecimais');
    }

    public function testCsrfTokenIsPersistentWithinSameRequest(): void
    {
        $first  = csrf_token();
        $second = csrf_token();
        $this->assertSame($first, $second, 'Token deve ser o mesmo durante a mesma requisição');
    }

    public function testCsrfFieldContainsTokenValue(): void
    {
        $token = csrf_token();
        $field = csrf_field();
        $this->assertStringContainsString($token, $field);
    }

    public function testCsrfFieldIsHiddenInput(): void
    {
        $field = csrf_field();
        $this->assertStringContainsString('type="hidden"', $field);
        $this->assertStringContainsString('name="csrf_token"', $field);
    }

    public function testCsrfValidateReturnsFalseWithNoPostData(): void
    {
        csrf_token(); // garante que sessão tem token
        $this->assertFalse(csrf_validate());
    }

    public function testCsrfValidateReturnsFalseWithWrongToken(): void
    {
        csrf_token();
        $_POST['csrf_token'] = 'token_errado_qualquer';
        $this->assertFalse(csrf_validate());
    }

    public function testCsrfValidateReturnsTrueWithCorrectToken(): void
    {
        $token = csrf_token();
        $_POST['csrf_token'] = $token;
        $this->assertTrue(csrf_validate());
    }

    public function testCsrfValidateReturnsFalseWhenSessionTokenMissing(): void
    {
        unset($_SESSION['csrf_token']);
        $_POST['csrf_token'] = 'qualquer_valor';
        $this->assertFalse(csrf_validate());
    }

    // ===== Flash Messages =====

    public function testFlashSetsMessageInSession(): void
    {
        flash('success', 'Operação realizada.');
        $this->assertSame('Operação realizada.', $_SESSION['flash_success']);
    }

    public function testGetFlashReturnsMessageAndRemovesIt(): void
    {
        flash('info', 'Mensagem de teste.');
        $value = get_flash('info');
        $this->assertSame('Mensagem de teste.', $value);
        $this->assertArrayNotHasKey('flash_info', $_SESSION, 'Flash deve ser removido após leitura');
    }

    public function testGetFlashReturnsNullWhenKeyDoesNotExist(): void
    {
        $this->assertNull(get_flash('nonexistent'));
    }

    public function testGetFlashIsConsumedOnFirstRead(): void
    {
        flash('error', 'Algo deu errado.');
        get_flash('error');
        $this->assertNull(get_flash('error'), 'Segunda leitura deve retornar null');
    }

    public function testDifferentFlashTypesAreIndependent(): void
    {
        flash('success', 'ok');
        flash('error', 'falhou');

        $this->assertSame('ok',     get_flash('success'));
        $this->assertSame('falhou', get_flash('error'));
    }

    // ===== URLs =====

    public function testAppUrlReturnsStringStartingWithSlash(): void
    {
        $url = app_url();
        $this->assertIsString($url);
        $this->assertStringStartsWith('/', $url);
    }

    public function testAppUrlEndsWithSlashWhenNoPathGiven(): void
    {
        $url = app_url();
        $this->assertStringEndsWith('/', $url);
    }

    public function testAppUrlWithPathContainsThePath(): void
    {
        $url = app_url('auth/login.php');
        $this->assertStringContainsString('auth/login.php', $url);
    }

    public function testAppUrlWithPathStartsWithSlash(): void
    {
        $url = app_url('user/painel.php');
        $this->assertStringStartsWith('/', $url);
    }

    // ===== session_check_timeout — caminho sem expiração =====

    public function testSessionCheckTimeoutUpdatesLastActivity(): void
    {
        $_SESSION['logado']        = true;
        $_SESSION['last_activity'] = time() - 60; // 1 minuto atrás

        session_check_timeout(); // não deve expirar

        $this->assertArrayHasKey('last_activity', $_SESSION);
        $this->assertGreaterThanOrEqual(time() - 2, $_SESSION['last_activity']);
    }

    public function testSessionCheckTimeoutDoesNothingWhenNotLoggedIn(): void
    {
        unset($_SESSION['logado'], $_SESSION['last_activity']);
        session_check_timeout();
        $this->assertArrayNotHasKey('last_activity', $_SESSION);
    }

    public function testSessionCheckTimeoutSetsLastActivityOnFirstAuthenticatedRequest(): void
    {
        $_SESSION['logado'] = true;
        unset($_SESSION['last_activity']);

        session_check_timeout();

        $this->assertArrayHasKey('last_activity', $_SESSION);
        $this->assertIsInt($_SESSION['last_activity']);
    }
}
