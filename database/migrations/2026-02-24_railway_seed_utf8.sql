-- MariaDB dump 10.19  Distrib 10.4.32-MariaDB, for Win64 (AMD64)
--
-- Host: localhost    Database: skillconnect
-- ------------------------------------------------------
-- Server version	10.4.32-MariaDB

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `aulas`
--

DROP TABLE IF EXISTS `aulas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `aulas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `modulo_id` int(10) unsigned NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `conteudo` text DEFAULT NULL,
  `video_url` varchar(255) DEFAULT NULL,
  `material_url` varchar(255) DEFAULT NULL,
  `duracao_min` int(10) unsigned DEFAULT NULL,
  `ordem` int(10) unsigned NOT NULL DEFAULT 1,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_aulas_modulo` (`modulo_id`),
  CONSTRAINT `fk_aulas_modulo` FOREIGN KEY (`modulo_id`) REFERENCES `modulos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `aulas`
--

LOCK TABLES `aulas` WRITE;
/*!40000 ALTER TABLE `aulas` DISABLE KEYS */;
INSERT INTO `aulas` VALUES (1,1,'Boas-vindas e objetivos','Introducao ao curso e objetivos de aprendizagem.',NULL,NULL,20,1,1,'2026-02-24 14:46:08','2026-02-24 14:46:08'),(2,1,'Conceitos essenciais','Conteudo base para avancar no tema principal.',NULL,NULL,35,2,1,'2026-02-24 14:46:08','2026-02-24 14:46:08'),(3,1,'Pratica guiada','Atividade pratica com aplicacao no mundo real.',NULL,NULL,40,3,1,'2026-02-24 14:46:08','2026-02-24 14:46:08'),(4,2,'Boas-vindas e objetivos','Introducao ao curso e objetivos de aprendizagem.',NULL,NULL,20,1,1,'2026-02-24 14:46:08','2026-02-24 14:46:08'),(5,2,'Conceitos essenciais','Conteudo base para avancar no tema principal.',NULL,NULL,35,2,1,'2026-02-24 14:46:08','2026-02-24 14:46:08'),(6,2,'Pratica guiada','Atividade pratica com aplicacao no mundo real.',NULL,NULL,40,3,1,'2026-02-24 14:46:08','2026-02-24 14:46:08'),(7,3,'Boas-vindas e objetivos','Introducao ao curso e objetivos de aprendizagem.',NULL,NULL,20,1,1,'2026-02-24 14:46:08','2026-02-24 14:46:08'),(8,3,'Conceitos essenciais','Conteudo base para avancar no tema principal.',NULL,NULL,35,2,1,'2026-02-24 14:46:08','2026-02-24 14:46:08'),(9,3,'Pratica guiada','Atividade pratica com aplicacao no mundo real.',NULL,NULL,40,3,1,'2026-02-24 14:46:08','2026-02-24 14:46:08'),(10,4,'Boas-vindas e objetivos','Introducao ao curso e objetivos de aprendizagem.',NULL,NULL,20,1,1,'2026-02-24 14:46:08','2026-02-24 14:46:08'),(11,4,'Conceitos essenciais','Conteudo base para avancar no tema principal.',NULL,NULL,35,2,1,'2026-02-24 14:46:08','2026-02-24 14:46:08'),(12,4,'Pratica guiada','Atividade pratica com aplicacao no mundo real.',NULL,NULL,40,3,1,'2026-02-24 14:46:08','2026-02-24 14:46:08');
/*!40000 ALTER TABLE `aulas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `candidaturas`
--

DROP TABLE IF EXISTS `candidaturas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `candidaturas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `vaga_id` int(10) unsigned NOT NULL,
  `curriculo_path` varchar(255) DEFAULT NULL,
  `carta_apresentacao` text DEFAULT NULL,
  `status` enum('enviada','em_analise','aprovado','reprovado') NOT NULL DEFAULT 'enviada',
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_candidatura` (`usuario_id`,`vaga_id`),
  KEY `vaga_id` (`vaga_id`),
  CONSTRAINT `candidaturas_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `candidaturas_ibfk_2` FOREIGN KEY (`vaga_id`) REFERENCES `vagas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `candidaturas`
--

LOCK TABLES `candidaturas` WRITE;
/*!40000 ALTER TABLE `candidaturas` DISABLE KEYS */;
/*!40000 ALTER TABLE `candidaturas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `contatos`
--

DROP TABLE IF EXISTS `contatos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `contatos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `mensagem` text NOT NULL,
  `lido` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `contatos`
--

LOCK TABLES `contatos` WRITE;
/*!40000 ALTER TABLE `contatos` DISABLE KEYS */;
/*!40000 ALTER TABLE `contatos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `curriculos`
--

DROP TABLE IF EXISTS `curriculos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `curriculos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `titulo_profissional` varchar(120) DEFAULT NULL,
  `resumo` text DEFAULT NULL,
  `habilidades` text DEFAULT NULL,
  `experiencias` text DEFAULT NULL,
  `formacao` text DEFAULT NULL,
  `links` text DEFAULT NULL,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_curriculo_usuario` (`usuario_id`),
  CONSTRAINT `fk_curriculo_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `curriculos`
--

LOCK TABLES `curriculos` WRITE;
/*!40000 ALTER TABLE `curriculos` DISABLE KEYS */;
/*!40000 ALTER TABLE `curriculos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `cursos`
--

DROP TABLE IF EXISTS `cursos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `cursos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `titulo` varchar(150) NOT NULL,
  `descricao` text DEFAULT NULL,
  `carga_horaria` int(10) unsigned DEFAULT NULL,
  `modalidade` enum('presencial','online','hibrido') NOT NULL DEFAULT 'online',
  `nivel` enum('basico','intermediario','avancado') NOT NULL DEFAULT 'basico',
  `preco` decimal(10,2) NOT NULL DEFAULT 0.00,
  `vagas` int(10) unsigned NOT NULL DEFAULT 0,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `cursos`
--

LOCK TABLES `cursos` WRITE;
/*!40000 ALTER TABLE `cursos` DISABLE KEYS */;
INSERT INTO `cursos` VALUES (1,'Desenvolvimento Web','Aprenda HTML, CSS, JavaScript e PHP do zero.',80,'online','basico',0.00,50,1,'2026-02-22 19:36:20','2026-02-22 19:36:20'),(2,'Excel Avançado','Domine fórmulas, tabelas dinâmicas e macros.',40,'online','intermediario',0.00,30,1,'2026-02-22 19:36:20','2026-02-22 19:36:20'),(3,'Inglês para o Mercado de Trabalho','Comunicação profissional em inglês.',60,'online','basico',0.00,40,1,'2026-02-22 19:36:20','2026-02-22 19:36:20'),(4,'Design Gráfico com Canva','Criação de peças visuais para redes sociais e apresentações.',30,'online','basico',0.00,25,1,'2026-02-22 19:36:20','2026-02-22 19:36:20');
/*!40000 ALTER TABLE `cursos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `inscricoes_cursos`
--

DROP TABLE IF EXISTS `inscricoes_cursos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `inscricoes_cursos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `curso_id` int(10) unsigned NOT NULL,
  `status` enum('pendente','confirmado','cancelado','concluido') NOT NULL DEFAULT 'pendente',
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_inscricao` (`usuario_id`,`curso_id`),
  KEY `curso_id` (`curso_id`),
  CONSTRAINT `inscricoes_cursos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  CONSTRAINT `inscricoes_cursos_ibfk_2` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `inscricoes_cursos`
--

LOCK TABLES `inscricoes_cursos` WRITE;
/*!40000 ALTER TABLE `inscricoes_cursos` DISABLE KEYS */;
INSERT INTO `inscricoes_cursos` VALUES (1,2,4,'pendente','2026-02-23 21:34:03'),(2,2,3,'pendente','2026-02-23 21:42:59');
/*!40000 ALTER TABLE `inscricoes_cursos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `modulos`
--

DROP TABLE IF EXISTS `modulos`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `modulos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `curso_id` int(10) unsigned NOT NULL,
  `titulo` varchar(150) NOT NULL,
  `ordem` int(10) unsigned NOT NULL DEFAULT 1,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_modulos_curso` (`curso_id`),
  CONSTRAINT `fk_modulos_curso` FOREIGN KEY (`curso_id`) REFERENCES `cursos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `modulos`
--

LOCK TABLES `modulos` WRITE;
/*!40000 ALTER TABLE `modulos` DISABLE KEYS */;
INSERT INTO `modulos` VALUES (1,1,'Modulo 1 - Fundamentos',1,1,'2026-02-24 14:46:08','2026-02-24 14:46:08'),(2,2,'Modulo 1 - Fundamentos',1,1,'2026-02-24 14:46:08','2026-02-24 14:46:08'),(3,3,'Modulo 1 - Fundamentos',1,1,'2026-02-24 14:46:08','2026-02-24 14:46:08'),(4,4,'Modulo 1 - Fundamentos',1,1,'2026-02-24 14:46:08','2026-02-24 14:46:08');
/*!40000 ALTER TABLE `modulos` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `progresso_aulas`
--

DROP TABLE IF EXISTS `progresso_aulas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `progresso_aulas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `aula_id` int(10) unsigned NOT NULL,
  `concluido_em` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_progresso_usuario_aula` (`usuario_id`,`aula_id`),
  KEY `idx_progresso_aula` (`aula_id`),
  CONSTRAINT `fk_progresso_aula` FOREIGN KEY (`aula_id`) REFERENCES `aulas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_progresso_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `progresso_aulas`
--

LOCK TABLES `progresso_aulas` WRITE;
/*!40000 ALTER TABLE `progresso_aulas` DISABLE KEYS */;
/*!40000 ALTER TABLE `progresso_aulas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `recuperacao_senha`
--

DROP TABLE IF EXISTS `recuperacao_senha`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `recuperacao_senha` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned NOT NULL,
  `token` varchar(100) NOT NULL,
  `expira_em` datetime NOT NULL,
  `usado` tinyint(1) NOT NULL DEFAULT 0,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `token` (`token`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `recuperacao_senha_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `recuperacao_senha`
--

LOCK TABLES `recuperacao_senha` WRITE;
/*!40000 ALTER TABLE `recuperacao_senha` DISABLE KEYS */;
/*!40000 ALTER TABLE `recuperacao_senha` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usuarios` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nome` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `senha` varchar(255) NOT NULL,
  `cpf` varchar(14) DEFAULT NULL,
  `telefone` varchar(20) DEFAULT NULL,
  `cep` varchar(10) DEFAULT NULL,
  `logradouro` varchar(150) DEFAULT NULL,
  `bairro` varchar(100) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` char(2) DEFAULT NULL,
  `perfil` enum('usuario','admin') NOT NULL DEFAULT 'usuario',
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `cpf` (`cpf`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `usuarios`
--

LOCK TABLES `usuarios` WRITE;
/*!40000 ALTER TABLE `usuarios` DISABLE KEYS */;
INSERT INTO `usuarios` VALUES (1,'Administrador','admin@skillconnect.com','$2y$10$wUIPIYo0utrSjB8sHExH3OkHj1pdeayb3.06L.ZWVL3jUofq/yPFS',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'admin',1,'2026-02-22 19:36:20','2026-02-23 21:32:33'),(2,'Aluno Teste','aluno.teste@skillconnect.com','$2y$10$qCaNnE..kn1YqmGgmV0wDO/uuIMjMoGBVuLKLeT4/nnUEYsB5FH2W',NULL,NULL,NULL,NULL,NULL,NULL,NULL,'usuario',1,'2026-02-23 21:32:33','2026-02-23 21:32:33');
/*!40000 ALTER TABLE `usuarios` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Table structure for table `vagas`
--

DROP TABLE IF EXISTS `vagas`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `vagas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `titulo` varchar(150) NOT NULL,
  `empresa` varchar(150) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `requisitos` text DEFAULT NULL,
  `tipo` enum('CLT','PJ','Estágio','Freelance','Temporário') NOT NULL DEFAULT 'CLT',
  `modalidade` enum('presencial','remoto','hibrido') NOT NULL DEFAULT 'presencial',
  `salario` varchar(50) DEFAULT NULL,
  `cidade` varchar(100) DEFAULT NULL,
  `estado` char(2) DEFAULT NULL,
  `ativo` tinyint(1) NOT NULL DEFAULT 1,
  `criado_em` datetime NOT NULL DEFAULT current_timestamp(),
  `atualizado_em` datetime NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping data for table `vagas`
--

LOCK TABLES `vagas` WRITE;
/*!40000 ALTER TABLE `vagas` DISABLE KEYS */;
INSERT INTO `vagas` VALUES (1,'Desenvolvedor Web Júnior','TechCorp','Desenvolvimento de sistemas web internos.','HTML, CSS, PHP, MySQL','CLT','hibrido','R$ 2.000 - R$ 3.000','São Paulo','SP',1,'2026-02-22 19:36:20','2026-02-22 19:36:20'),(2,'Assistente Administrativo','Grupo Alfa','Suporte às rotinas administrativas da empresa.','Excel, comunicação, organização','CLT','presencial','R$ 1.500 - R$ 2.000','Rio de Janeiro','RJ',1,'2026-02-22 19:36:20','2026-02-22 19:36:20'),(3,'Estágio em TI','Inova Solutions','Suporte técnico e manutenção de sistemas.','Cursando TI, interesse em suporte','Estágio','presencial','R$ 800 - R$ 1.200','Belo Horizonte','MG',1,'2026-02-22 19:36:20','2026-02-22 19:36:20');
/*!40000 ALTER TABLE `vagas` ENABLE KEYS */;
UNLOCK TABLES;

--
-- Dumping routines for database 'skillconnect'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2026-02-24 17:24:38

