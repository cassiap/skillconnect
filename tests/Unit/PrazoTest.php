<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Testes da função prazo_encerrado(), usada para bloquear inscrições em
 * cursos e candidaturas a vagas após a data limite definida pelo admin.
 *
 * A regra é inclusiva: no próprio dia da data limite a ação ainda é
 * permitida; o prazo só encerra a partir do dia seguinte.
 */
class PrazoTest extends TestCase
{
    /** Timestamp fixo de referência: 2026-06-10 12:00:00 */
    private const AGORA = 1781179200;

    public function testSemPrazoNuncaEncerra(): void
    {
        $this->assertFalse(prazo_encerrado(null, self::AGORA));
    }

    public function testStringVaziaNuncaEncerra(): void
    {
        $this->assertFalse(prazo_encerrado('', self::AGORA));
        $this->assertFalse(prazo_encerrado('   ', self::AGORA));
    }

    public function testDataInvalidaNuncaEncerra(): void
    {
        $this->assertFalse(prazo_encerrado('nao-e-data', self::AGORA));
    }

    public function testPrazoFuturoNaoEncerrado(): void
    {
        $futuro = date('Y-m-d', self::AGORA + 30 * 86400);
        $this->assertFalse(prazo_encerrado($futuro, self::AGORA));
    }

    public function testPrazoNoProprioDiaAindaValido(): void
    {
        // No dia da data limite a ação ainda é permitida (prazo inclusivo)
        $hoje = date('Y-m-d', self::AGORA);
        $this->assertFalse(prazo_encerrado($hoje, self::AGORA));
    }

    public function testPrazoValidoAteUltimoSegundoDoDia(): void
    {
        $hoje = date('Y-m-d', self::AGORA);
        $fimDoDia = strtotime($hoje . ' 23:59:59');
        $this->assertFalse(prazo_encerrado($hoje, $fimDoDia));
    }

    public function testPrazoEncerraNoDiaSeguinte(): void
    {
        $ontem = date('Y-m-d', self::AGORA - 86400);
        $this->assertTrue(prazo_encerrado($ontem, self::AGORA));
    }

    public function testPrazoEncerraUmSegundoAposFimDoDia(): void
    {
        $hoje = date('Y-m-d', self::AGORA);
        $umSegundoDepois = strtotime($hoje . ' 23:59:59') + 1;
        $this->assertTrue(prazo_encerrado($hoje, $umSegundoDepois));
    }

    public function testPrazoMuitoAntigoEncerrado(): void
    {
        $this->assertTrue(prazo_encerrado('2020-01-01', self::AGORA));
    }
}
