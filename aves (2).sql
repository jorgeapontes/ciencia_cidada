-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Tempo de geração: 17/05/2025 às 05:00
-- Versão do servidor: 10.4.32-MariaDB
-- Versão do PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `aves`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `atropelamentos`
--

CREATE TABLE `atropelamentos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `data_ocorrencia` datetime NOT NULL,
  `localizacao` varchar(255) DEFAULT NULL,
  `especie` varchar(255) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `caminho_foto` varchar(255) DEFAULT NULL,
  `data_postagem` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `atropelamentos`
--

INSERT INTO `atropelamentos` (`id`, `usuario_id`, `data_ocorrencia`, `localizacao`, `especie`, `descricao`, `caminho_foto`, `data_postagem`) VALUES
(4, 16, '2025-05-16 14:33:33', 'Rua X', '', 'Animal atropelado, encontrado...', 'fotos/682776ed2af16_x.png', '2025-05-16 17:33:33');

-- --------------------------------------------------------

--
-- Estrutura para tabela `comentarios`
--

CREATE TABLE `comentarios` (
  `id` int(11) NOT NULL,
  `publicacao_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `comentario` text NOT NULL,
  `texto` text NOT NULL,
  `data_comentario` datetime DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `comentarios_atropelamentos`
--

CREATE TABLE `comentarios_atropelamentos` (
  `id` int(11) NOT NULL,
  `atropelamento_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `comentario` text NOT NULL,
  `data_comentario` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `curtidas`
--

CREATE TABLE `curtidas` (
  `id` int(11) NOT NULL,
  `publicacao_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `interacoes`
--

CREATE TABLE `interacoes` (
  `id` int(11) NOT NULL,
  `publicacao_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `tipo` enum('like','dislike') NOT NULL,
  `data_interacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `interacoes_atropelamentos`
--

CREATE TABLE `interacoes_atropelamentos` (
  `id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `atropelamento_id` int(11) NOT NULL,
  `tipo` enum('like','dislike') NOT NULL,
  `data_interacao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `publicacoes`
--

CREATE TABLE `publicacoes` (
  `id` int(11) NOT NULL,
  `especie` varchar(255) NOT NULL,
  `foto` varchar(255) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `data_publicacao` datetime DEFAULT current_timestamp(),
  `titulo` varchar(255) DEFAULT NULL,
  `descricao` text DEFAULT NULL,
  `caminho_foto` varchar(255) DEFAULT NULL,
  `atropelamento` tinyint(1) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `publicacoes`
--

INSERT INTO `publicacoes` (`id`, `especie`, `foto`, `usuario_id`, `data_publicacao`, `titulo`, `descricao`, `caminho_foto`, `atropelamento`) VALUES
(23, '', '', 16, '2025-05-16 15:35:54', 'Tucano', 'Pássaro com um bico longo', 'fotos/6827858af016e_tucano.jpg', 0);

-- --------------------------------------------------------

--
-- Estrutura para tabela `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `senha` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `cargo` enum('user','especialista','admin') DEFAULT 'user',
  `token_senha` varchar(64) DEFAULT NULL,
  `token_expiracao` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Despejando dados para a tabela `usuarios`
--

INSERT INTO `usuarios` (`id`, `nome`, `email`, `senha`, `cargo`, `token_senha`, `token_expiracao`) VALUES
(1, 'Admin', 'admin@aves.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', NULL, NULL),
(2, 'Jorge', 'jorgeappontes13@gmail.com', '$2y$10$XiRfRnVFHfGE13BPUjBviO3NHhmVY/Y0ITKTGvjQlS1es8uoOJFEu', 'admin', '78d417165cfaaabcb0cfb5daa68082971bc33c1f750ba79ac6d639e02b0ff810', '2025-05-13 03:17:12'),
(12, 'jorge especialista', 'jorge3@gmail.com', '$2y$10$5T0.XfGLerhTCfJib8Tp6OVzdTXEe75LkGAdMEzg2qsQfdkV13OR6', 'especialista', NULL, NULL),
(14, 'João', 'jv06.sanches@gmail.com', '$2y$10$xeAeqQQKyc1T7EUZ3BObaeR358W5tlrlk9pftsw.D3c.2fVQ90Tim', 'user', NULL, NULL),
(15, 'Murilo Suhett do Nascimento', 'murilosuhett55@gmail.com', '$2y$10$eGcRArzIFM/sckm3d5cqoOgLTETgg/caDuZyN/TNVAk5cAAyKo/e6', 'user', NULL, NULL),
(16, 'Jorge', 'jorge2@gmail.com', '$2y$10$dpEZkAu/hXbinOKar0IFAey/twCd9q5MdDn1wl2h1J4F3myMyu8le', 'user', NULL, NULL);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `atropelamentos`
--
ALTER TABLE `atropelamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `comentarios`
--
ALTER TABLE `comentarios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `publicacao_id` (`publicacao_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `comentarios_atropelamentos`
--
ALTER TABLE `comentarios_atropelamentos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `atropelamento_id` (`atropelamento_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `curtidas`
--
ALTER TABLE `curtidas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `publicacao_id` (`publicacao_id`,`usuario_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `interacoes`
--
ALTER TABLE `interacoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `publicacao_id` (`publicacao_id`,`usuario_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `interacoes_atropelamentos`
--
ALTER TABLE `interacoes_atropelamentos`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `usuario_atropelamento_tipo` (`usuario_id`,`atropelamento_id`,`tipo`),
  ADD KEY `atropelamento_id` (`atropelamento_id`);

--
-- Índices de tabela `publicacoes`
--
ALTER TABLE `publicacoes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Índices de tabela `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `atropelamentos`
--
ALTER TABLE `atropelamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `comentarios`
--
ALTER TABLE `comentarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de tabela `comentarios_atropelamentos`
--
ALTER TABLE `comentarios_atropelamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `curtidas`
--
ALTER TABLE `curtidas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `interacoes`
--
ALTER TABLE `interacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT de tabela `interacoes_atropelamentos`
--
ALTER TABLE `interacoes_atropelamentos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de tabela `publicacoes`
--
ALTER TABLE `publicacoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=24;

--
-- AUTO_INCREMENT de tabela `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `atropelamentos`
--
ALTER TABLE `atropelamentos`
  ADD CONSTRAINT `atropelamentos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `comentarios`
--
ALTER TABLE `comentarios`
  ADD CONSTRAINT `comentarios_ibfk_1` FOREIGN KEY (`publicacao_id`) REFERENCES `publicacoes` (`id`),
  ADD CONSTRAINT `comentarios_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `comentarios_atropelamentos`
--
ALTER TABLE `comentarios_atropelamentos`
  ADD CONSTRAINT `comentarios_atropelamentos_ibfk_1` FOREIGN KEY (`atropelamento_id`) REFERENCES `atropelamentos` (`id`),
  ADD CONSTRAINT `comentarios_atropelamentos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `curtidas`
--
ALTER TABLE `curtidas`
  ADD CONSTRAINT `curtidas_ibfk_1` FOREIGN KEY (`publicacao_id`) REFERENCES `publicacoes` (`id`),
  ADD CONSTRAINT `curtidas_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `interacoes`
--
ALTER TABLE `interacoes`
  ADD CONSTRAINT `interacoes_ibfk_1` FOREIGN KEY (`publicacao_id`) REFERENCES `publicacoes` (`id`),
  ADD CONSTRAINT `interacoes_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Restrições para tabelas `interacoes_atropelamentos`
--
ALTER TABLE `interacoes_atropelamentos`
  ADD CONSTRAINT `interacoes_atropelamentos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  ADD CONSTRAINT `interacoes_atropelamentos_ibfk_2` FOREIGN KEY (`atropelamento_id`) REFERENCES `atropelamentos` (`id`);

--
-- Restrições para tabelas `publicacoes`
--
ALTER TABLE `publicacoes`
  ADD CONSTRAINT `publicacoes_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
