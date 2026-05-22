<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Verifica que todas as constantes de status e perfil do SkillConnect
 * mantêm os valores exatos esperados pelo banco de dados.
 *
 * Estes testes protegem contra renomeações acidentais que quebrariam
 * queries SQL e comparações em runtime.
 */
class ConstantsTest extends TestCase
{
    // ===== Candidaturas =====

    public function testStatusCandEnviadaValue(): void
    {
        $this->assertSame('enviada', STATUS_CAND_ENVIADA);
    }

    public function testStatusCandAnaliseValue(): void
    {
        $this->assertSame('em_analise', STATUS_CAND_ANALISE);
    }

    public function testStatusCandAprovadoValue(): void
    {
        $this->assertSame('aprovado', STATUS_CAND_APROVADO);
    }

    public function testStatusCandReprovadoValue(): void
    {
        $this->assertSame('reprovado', STATUS_CAND_REPROVADO);
    }

    public function testAllCandidaturaConstantsAreStrings(): void
    {
        $this->assertIsString(STATUS_CAND_ENVIADA);
        $this->assertIsString(STATUS_CAND_ANALISE);
        $this->assertIsString(STATUS_CAND_APROVADO);
        $this->assertIsString(STATUS_CAND_REPROVADO);
    }

    public function testCandidaturaStatusAreDistinct(): void
    {
        $values = [STATUS_CAND_ENVIADA, STATUS_CAND_ANALISE, STATUS_CAND_APROVADO, STATUS_CAND_REPROVADO];
        $this->assertSame(count($values), count(array_unique($values)), 'Cada status de candidatura deve ter valor único');
    }

    // ===== Inscrições =====

    public function testStatusInscPendenteValue(): void
    {
        $this->assertSame('pendente', STATUS_INSC_PENDENTE);
    }

    public function testStatusInscConfirmadoValue(): void
    {
        $this->assertSame('confirmado', STATUS_INSC_CONFIRMADO);
    }

    public function testStatusInscCanceladoValue(): void
    {
        $this->assertSame('cancelado', STATUS_INSC_CANCELADO);
    }

    public function testStatusInscConcluidoValue(): void
    {
        $this->assertSame('concluido', STATUS_INSC_CONCLUIDO);
    }

    public function testAllInscricaoConstantsAreStrings(): void
    {
        $this->assertIsString(STATUS_INSC_PENDENTE);
        $this->assertIsString(STATUS_INSC_CONFIRMADO);
        $this->assertIsString(STATUS_INSC_CANCELADO);
        $this->assertIsString(STATUS_INSC_CONCLUIDO);
    }

    public function testInscricaoStatusAreDistinct(): void
    {
        $values = [STATUS_INSC_PENDENTE, STATUS_INSC_CONFIRMADO, STATUS_INSC_CANCELADO, STATUS_INSC_CONCLUIDO];
        $this->assertSame(count($values), count(array_unique($values)), 'Cada status de inscrição deve ter valor único');
    }

    // ===== Perfis =====

    public function testPerfilUsuarioValue(): void
    {
        $this->assertSame('usuario', PERFIL_USUARIO);
    }

    public function testPerfilAdminValue(): void
    {
        $this->assertSame('admin', PERFIL_ADMIN);
    }

    public function testPerfisAreDistinct(): void
    {
        $this->assertNotSame(PERFIL_USUARIO, PERFIL_ADMIN);
    }
}
