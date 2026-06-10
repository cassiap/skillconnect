<?php
/**
 * Constantes globais da aplicação SkillConnect
 *
 * Centraliza todos os valores de status e perfil usados em múltiplas partes
 * do sistema. Use estas constantes em vez de strings literais nas comparações
 * PHP e nas chaves de arrays. Strings dentro de queries SQL devem permanecer
 * como literais, pois o MySQL não tem acesso a constantes PHP.
 *
 * @package SkillConnect
 */

// ===== Status de candidaturas a vagas =====

/** Candidatura recém-enviada, aguardando análise. */
const STATUS_CAND_ENVIADA   = 'enviada';

/** Candidatura em processo de avaliação pelo recrutador. */
const STATUS_CAND_ANALISE   = 'em_analise';

/** Candidato aprovado para a vaga. */
const STATUS_CAND_APROVADO  = 'aprovado';

/** Candidato não selecionado para a vaga. */
const STATUS_CAND_REPROVADO = 'reprovado';

/** Candidatura cancelada pelo próprio aluno. */
const STATUS_CAND_CANCELADA = 'cancelada';

// ===== Status de inscrições em cursos =====

/** Inscrição registrada, aguardando confirmação. */
const STATUS_INSC_PENDENTE   = 'pendente';

/** Inscrição ativa — aluno cursando. */
const STATUS_INSC_CONFIRMADO = 'confirmado';

/** Inscrição cancelada pelo aluno ou pelo admin. */
const STATUS_INSC_CANCELADO  = 'cancelado';

/** Todas as aulas concluídas — curso finalizado. */
const STATUS_INSC_CONCLUIDO  = 'concluido';

// ===== Perfis de usuário =====

/** Perfil padrão de aluno. */
const PERFIL_USUARIO = 'usuario';

/** Perfil de administrador da plataforma. */
const PERFIL_ADMIN   = 'admin';
