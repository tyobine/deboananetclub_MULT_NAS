-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 10/07/2026 às 17:14
-- Versão do servidor: 10.6.18-MariaDB-cll-lve
-- Versão do PHP: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Banco de dados: `moveisjb_database`
--

-- --------------------------------------------------------

--
-- Estrutura para tabela `acessos_pix`
--

CREATE TABLE `acessos_pix` (
  `id` int(11) NOT NULL,
  `mac_address` varchar(17) NOT NULL COMMENT 'Endereço MAC do dispositivo do cliente',
  `whatsapp` varchar(20) DEFAULT NULL,
  `ip_address` varchar(15) NOT NULL COMMENT 'IP atribuído pelo DHCP do Hotspot',
  `txid` varchar(100) NOT NULL COMMENT 'ID da transação no Mercado Pago',
  `plano_id` int(11) DEFAULT NULL COMMENT 'ID do plano escolhido pelo cliente',
  `status` varchar(50) DEFAULT 'pendente',
  `expira_em` datetime DEFAULT NULL COMMENT 'Data e hora exata em que a internet deve ser cortada',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `router_id` varchar(50) DEFAULT 'sobral'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `acessos_pix`
--

INSERT INTO `acessos_pix` (`id`, `mac_address`, `whatsapp`, `ip_address`, `txid`, `plano_id`, `status`, `expira_em`, `criado_em`, `router_id`) VALUES
(407, 'B6:57:3B:04:9A:D8', '88996567485', '10.50.0.253', 'PUB-1783616895-7927', 4, 'expirado', '2026-07-09 14:18:15', '2026-07-09 17:08:15', 'fortaleza'),
(408, '46:E9:1F:DF:54:14', '85997641084', '10.50.0.245', 'PUB-1783618051-7457', 4, 'expirado', '2026-07-09 14:37:31', '2026-07-09 17:27:31', 'fortaleza'),
(409, '5C:F9:38:11:22:33', '85987106156', '10.50.0.253', 'PUB-1783634657-7035', 4, 'expirado', '2026-07-09 19:14:17', '2026-07-09 22:04:17', 'fortaleza'),
(410, '5C:F9:38:11:22:33', NULL, '10.50.0.253', '168047388888', 1, 'pendente', NULL, '2026-07-09 22:07:05', 'fortaleza');

-- --------------------------------------------------------

--
-- Estrutura para tabela `admin_users`
--

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL COMMENT 'Password encriptada com password_hash() do PHP',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `admin_users`
--

INSERT INTO `admin_users` (`id`, `username`, `password_hash`, `criado_em`) VALUES
(1, 'admin', '$2y$10$e.wXq.3T0n./J8l4.P1I.O1.yQkZ4Z5Z6Z7Z8Z9Z0Z1Z2Z3Z4Z5Z6', '2026-06-17 06:37:27');

-- --------------------------------------------------------

--
-- Estrutura para tabela `configuracoes`
--

CREATE TABLE `configuracoes` (
  `chave` varchar(50) NOT NULL,
  `valor` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `configuracoes`
--

INSERT INTO `configuracoes` (`chave`, `valor`) VALUES
('ad_link', '{\"\\/uploads\\/castelo_1783573136.jpg\":\"https:\\/\\/tocaki.com.br\\/playlist\\/tocakimusic\\/tocaki-no-sao-joao\",\"\\/uploads\\/castelo_1783573809.jpg\":\"https:\\/\\/tocaki.com.br\\/playlist\\/tocakimusic\\/tocaki-no-sao-joao\",\"\\/uploads\\/midia_1782314646.jpg\":\"https:\\/\\/tocaki.com.br\\/playlist\\/tocakimusic\\/tocaki-no-sao-joao\",\"\\/uploads\\/thiago_gomes_1783572381.jpg\":\"https:\\/\\/tocaki.com.br\\/playlist\\/tocakimusic\\/tocaki-no-sao-joao\"}'),
('ad_tipo', 'rotativo'),
('ad_url', '[\"\\/uploads\\/castelo_1783573136.jpg\",\"\\/uploads\\/castelo_1783573809.jpg\",\"\\/uploads\\/midia_1782314646.jpg\",\"\\/uploads\\/thiago_gomes_1783572381.jpg\"]');

-- --------------------------------------------------------

--
-- Estrutura para tabela `crm_anunciantes`
--

CREATE TABLE `crm_anunciantes` (
  `id` int(11) NOT NULL,
  `nome_empresa` varchar(255) NOT NULL,
  `telefone` varchar(50) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `crm_anunciantes`
--

INSERT INTO `crm_anunciantes` (`id`, `nome_empresa`, `telefone`, `criado_em`) VALUES
(1, 'THIAGO GOMES', '(88) 99656-7485', '2026-07-09 15:03:59'),
(2, 'TOCAKI', '', '2026-07-09 15:46:22');

-- --------------------------------------------------------

--
-- Estrutura para tabela `crm_anuncios`
--

CREATE TABLE `crm_anuncios` (
  `id` int(11) NOT NULL,
  `anunciante_id` int(11) NOT NULL,
  `tipo` enum('imagem','video') NOT NULL,
  `caminho_arquivo` varchar(255) NOT NULL,
  `link_destino` varchar(255) DEFAULT NULL,
  `exibir` enum('sim','nao') DEFAULT 'sim',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `localizacao` varchar(100) DEFAULT 'todos'
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `crm_anuncios`
--

INSERT INTO `crm_anuncios` (`id`, `anunciante_id`, `tipo`, `caminho_arquivo`, `link_destino`, `exibir`, `criado_em`, `localizacao`) VALUES
(3, 2, 'imagem', '/uploads/crm_2_1783616296.jpg', 'https://tocaki.com.br', 'sim', '2026-07-09 16:58:16', 'fortaleza'),
(4, 2, 'imagem', '/uploads/crm_2_1783616390.jpg', 'https://tocaki.com.br', 'sim', '2026-07-09 16:59:50', 'sobral'),
(5, 2, 'imagem', '/uploads/crm_2_1783616480.jpg', 'https://tocaki.com.br', 'sim', '2026-07-09 17:01:20', 'fortaleza');

-- --------------------------------------------------------

--
-- Estrutura para tabela `crm_cliques`
--

CREATE TABLE `crm_cliques` (
  `id` int(11) NOT NULL,
  `anuncio_id` int(11) NOT NULL,
  `data_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- --------------------------------------------------------

--
-- Estrutura para tabela `crm_locais`
--

CREATE TABLE `crm_locais` (
  `id` int(11) NOT NULL,
  `nome` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `crm_locais`
--

INSERT INTO `crm_locais` (`id`, `nome`) VALUES
(1, 'todos'),
(2, 'sobral'),
(3, 'fortaleza');

-- --------------------------------------------------------

--
-- Estrutura para tabela `crm_views`
--

CREATE TABLE `crm_views` (
  `id` int(11) NOT NULL,
  `anuncio_id` int(11) NOT NULL,
  `data_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `crm_views`
--

INSERT INTO `crm_views` (`id`, `anuncio_id`, `data_registro`) VALUES
(8, 3, '2026-07-09 17:06:25'),
(9, 3, '2026-07-09 17:07:40'),
(10, 4, '2026-07-09 17:27:05'),
(11, 3, '2026-07-09 22:03:23');

-- --------------------------------------------------------

--
-- Estrutura para tabela `planos`
--

CREATE TABLE `planos` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price_cents` int(11) NOT NULL COMMENT 'Valor em cêntimos. Ex: 500 = R$ 5,00',
  `duration_minutes` int(11) NOT NULL COMMENT 'Tempo de navegação em minutos',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Despejando dados para a tabela `planos`
--

INSERT INTO `planos` (`id`, `name`, `price_cents`, `duration_minutes`, `criado_em`, `ativo`) VALUES
(1, 'Acesso 1h', 1, 60, '2026-06-17 06:37:27', 1),
(2, 'Acesso 3h', 2, 180, '2026-06-17 06:37:27', 1),
(3, 'Acesso 8h', 600, 480, '2026-06-17 06:37:27', 1),
(4, 'Grátis', 0, 10, '2026-06-17 15:34:29', 1);

--
-- Índices para tabelas despejadas
--

--
-- Índices de tabela `acessos_pix`
--
ALTER TABLE `acessos_pix`
  ADD PRIMARY KEY (`id`),
  ADD KEY `plano_id` (`plano_id`);

--
-- Índices de tabela `admin_users`
--
ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Índices de tabela `configuracoes`
--
ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`chave`);

--
-- Índices de tabela `crm_anunciantes`
--
ALTER TABLE `crm_anunciantes`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `crm_anuncios`
--
ALTER TABLE `crm_anuncios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anunciante_id` (`anunciante_id`);

--
-- Índices de tabela `crm_cliques`
--
ALTER TABLE `crm_cliques`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anuncio_id` (`anuncio_id`);

--
-- Índices de tabela `crm_locais`
--
ALTER TABLE `crm_locais`
  ADD PRIMARY KEY (`id`);

--
-- Índices de tabela `crm_views`
--
ALTER TABLE `crm_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anuncio_id` (`anuncio_id`);

--
-- Índices de tabela `planos`
--
ALTER TABLE `planos`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

--
-- AUTO_INCREMENT de tabela `acessos_pix`
--
ALTER TABLE `acessos_pix`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=411;

--
-- AUTO_INCREMENT de tabela `admin_users`
--
ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `crm_anunciantes`
--
ALTER TABLE `crm_anunciantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de tabela `crm_anuncios`
--
ALTER TABLE `crm_anuncios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de tabela `crm_cliques`
--
ALTER TABLE `crm_cliques`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT de tabela `crm_locais`
--
ALTER TABLE `crm_locais`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT de tabela `crm_views`
--
ALTER TABLE `crm_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de tabela `planos`
--
ALTER TABLE `planos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restrições para tabelas despejadas
--

--
-- Restrições para tabelas `acessos_pix`
--
ALTER TABLE `acessos_pix`
  ADD CONSTRAINT `acessos_pix_ibfk_1` FOREIGN KEY (`plano_id`) REFERENCES `planos` (`id`) ON DELETE SET NULL;

--
-- Restrições para tabelas `crm_anuncios`
--
ALTER TABLE `crm_anuncios`
  ADD CONSTRAINT `crm_anuncios_ibfk_1` FOREIGN KEY (`anunciante_id`) REFERENCES `crm_anunciantes` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `crm_cliques`
--
ALTER TABLE `crm_cliques`
  ADD CONSTRAINT `crm_cliques_ibfk_1` FOREIGN KEY (`anuncio_id`) REFERENCES `crm_anuncios` (`id`) ON DELETE CASCADE;

--
-- Restrições para tabelas `crm_views`
--
ALTER TABLE `crm_views`
  ADD CONSTRAINT `crm_views_ibfk_1` FOREIGN KEY (`anuncio_id`) REFERENCES `crm_anuncios` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
