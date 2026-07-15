-- phpMyAdmin SQL Dump
-- version 5.2.2
-- https://www.phpmyadmin.net/
--
-- Host: localhost
-- Tempo de geração: 15/07/2026 às 18:10
-- Versão do servidor: 10.6.18-MariaDB-cll-lve
-- Versão do PHP: 8.1.34
--
-- ============================================================
-- CHANGELOG DE CORREÇÕES APLICADAS NESTE DUMP:
--
-- [1] UNIQUE KEY em acessos_pix.txid
--     Impede que o mesmo pagamento seja inserido ou processado
--     duas vezes. Sem isso, IDs 433 e 434 tinham o mesmo txid
--     '167956016043', causando ambiguidade nos UPDATEs do webhook.
--
-- [2] Status 'processando' corrigidos para 'erro_mikrotik'
--     Registros IDs 432, 433 e 434 ficaram presos em 'processando'
--     por causa do bug do estorno (já corrigido no webhook.php).
--     AÇÃO MANUAL NECESSÁRIA: estornar os txids abaixo no painel
--     do Mercado Pago:
--       - 167956061809  (id 432)
--       - 167956016043  (ids 433 e 434 — txid duplicado)
--
-- [3] Tabela cliques_anuncio CRIADA
--     Referenciada em hotspot.php (linha 339) e registrar_clique.php
--     mas completamente ausente do schema anterior, causando erro
--     silencioso no fluxo de liberação de plano gratuito.
--
-- [4] Chave 'tempo_carencia' inserida em configuracoes
--     O PHP busca SELECT valor FROM configuracoes WHERE chave =
--     'tempo_carencia' mas ela não existia. Sem ela, o sistema
--     usava sempre 15 minutos hardcoded como fallback.
--
-- [5] Charset utf8mb4 em todas as tabelas
--     latin1 não suporta emojis (❌ 🚨) nem certos caracteres
--     usados nos logs e mensagens do sistema.
--
-- [6] Coluna cliques adicionada em crm_anuncios
--     O hotspot.php faz UPDATE crm_anuncios SET cliques = cliques + 1
--     mas a coluna não existia no schema original.
--
-- [7] Índices compostos adicionados em acessos_pix
--     (mac_address, status) e (status) aceleram as queries de
--     verificação de sessão ativa executadas a cada acesso.
-- ============================================================

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
-- Tabela `acessos_pix`
-- --------------------------------------------------------

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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [FIX 2] Registros corrigidos: 432 e 433 tinham status 'processando' (bug do estorno).
--         434 é duplicata do txid do 433 (bug de ausência do UNIQUE KEY).
--         Os três precisam de estorno manual no Mercado Pago.
INSERT INTO `acessos_pix` (`id`, `mac_address`, `whatsapp`, `ip_address`, `txid`, `plano_id`, `status`, `expira_em`, `criado_em`, `router_id`) VALUES
(420, '5C:F9:38:11:22:33', '22222222222', '192.168.254.34', 'PUB-1783827189-7406', 4, 'expirado', '2026-07-12 00:43:09', '2026-07-12 03:33:09', 'fortaleza'),
(421, '5C:F9:38:11:22:88', '44444444444', '10.50.0.90', 'PUB-1783827932-6728', 4, 'expirado', '2026-07-12 00:55:32', '2026-07-12 03:45:32', 'fortaleza'),
(422, '5C:F9:38:11:22:33', NULL, '10.50.0.45', '168480359122', 2, 'pendente', NULL, '2026-07-12 18:32:21', 'fortaleza'),
(423, '5C:F9:38:11:22:33', NULL, '10.50.0.45', '167620954653', 1, 'pendente', NULL, '2026-07-12 18:33:56', 'fortaleza'),
(424, '5C:F9:38:11:22:33', NULL, '10.50.0.45', '167620427023', 1, 'pendente', NULL, '2026-07-12 18:34:41', 'fortaleza'),
(425, '5C:F9:38:11:22:33', NULL, '192.168.254.34', '167621999285', 1, 'pendente', NULL, '2026-07-12 18:50:53', 'fortaleza'),
(426, '5C:F9:38:11:22:33', NULL, '192.168.254.34', '168526201564', 3, 'pendente', NULL, '2026-07-13 01:08:19', 'fortaleza'),
(427, '5C:F9:38:11:22:33', '88996567485', '10.50.0.52', 'PUB-1783914414-2244', 4, 'expirado', '2026-07-13 00:56:54', '2026-07-13 03:46:54', 'fortaleza'),
(428, 'B6:57:3B:04:9A:D8', '88888888888', '10.50.0.253', 'PUB-1783974030-6558', 4, 'expirado', '2026-07-13 17:30:30', '2026-07-13 20:20:30', 'fortaleza'),
(429, 'B6:57:3B:04:9A:D8', NULL, '10.50.0.253', '167797342881', 2, 'pendente', NULL, '2026-07-13 21:58:00', 'fortaleza'),
(430, 'B6:57:3B:04:9A:D8', '98888858855', '10.50.0.253', 'PUB-1783979954-9034', 4, 'expirado', '2026-07-13 19:09:14', '2026-07-13 21:59:14', 'fortaleza'),
(431, 'B6:57:3B:04:9A:D8', NULL, '10.50.0.253', '167806030557', 2, 'pendente', NULL, '2026-07-13 22:41:41', 'fortaleza'),
-- CORRIGIDO: era 'processando' — estornar txid 167956061809 no painel do MP
(432, 'B6:57:3B:04:9A:D8', NULL, '10.50.0.254', '167956061809', 2, 'erro_mikrotik', NULL, '2026-07-14 22:07:12', 'fortaleza'),
-- CORRIGIDO: era 'processando' — estornar txid 167956016043 no painel do MP
(433, 'B6:57:3B:04:9A:D8', NULL, '10.50.0.254', '167956016043', 1, 'erro_mikrotik', NULL, '2026-07-14 22:09:19', 'fortaleza'),
-- CORRIGIDO: duplicata do txid acima — renomeado com sufixo _dup para não violar UNIQUE KEY
(434, 'B6:57:3B:04:9A:D8', NULL, '10.50.0.254', '167956016043_dup', 1, 'erro_mikrotik', NULL, '2026-07-14 22:09:29', 'fortaleza'),
(435, 'B6:57:3B:04:9A:D8', NULL, '10.50.0.254', '168824412552', 1, 'expirado', '2026-07-14 20:26:00', '2026-07-14 22:14:13', 'fortaleza'),
(436, 'B6:57:3B:04:9A:D8', NULL, '10.50.0.253', '167962256419', 1, 'pendente', NULL, '2026-07-14 22:27:11', 'fortaleza');

-- --------------------------------------------------------
-- Tabela `admin_users`
-- --------------------------------------------------------

CREATE TABLE `admin_users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL COMMENT 'Password encriptada com password_hash() do PHP',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `admin_users` (`id`, `username`, `password_hash`, `criado_em`) VALUES
(1, 'admin', '$2y$10$e.wXq.3T0n./J8l4.P1I.O1.yQkZ4Z5Z6Z7Z8Z9Z0Z1Z2Z3Z4Z5Z6', '2026-06-17 06:37:27');

-- --------------------------------------------------------
-- Tabela `cliques_anuncio`  [NOVA — FIX 3]
-- --------------------------------------------------------

CREATE TABLE `cliques_anuncio` (
  `id` int(11) NOT NULL,
  `mac_address` varchar(17) NOT NULL COMMENT 'MAC do dispositivo que clicou no anúncio',
  `url_destino` varchar(500) NOT NULL COMMENT 'URL do anúncio clicado',
  `data_clique` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Tabela `configuracoes`
-- --------------------------------------------------------

CREATE TABLE `configuracoes` (
  `chave` varchar(50) NOT NULL,
  `valor` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- [FIX 4] 'tempo_carencia' adicionada — era lida pelo PHP mas não existia no banco.
INSERT INTO `configuracoes` (`chave`, `valor`) VALUES
('ad_link', '{"\\/uploads\\/castelo_1783573136.jpg":"https:\\/\\/tocaki.com.br\\/playlist\\/tocakimusic\\/tocaki-no-sao-joao","\\/uploads\\/castelo_1783573809.jpg":"https:\\/\\/tocaki.com.br\\/playlist\\/tocakimusic\\/tocaki-no-sao-joao","\\/uploads\\/midia_1782314646.jpg":"https:\\/\\/tocaki.com.br\\/playlist\\/tocakimusic\\/tocaki-no-sao-joao","\\/uploads\\/thiago_gomes_1783572381.jpg":"https:\\/\\/tocaki.com.br\\/playlist\\/tocakimusic\\/tocaki-no-sao-joao"}'),
('ad_tipo', 'rotativo'),
('ad_url', '["\\/uploads\\/castelo_1783573136.jpg","\\/uploads\\/castelo_1783573809.jpg","\\/uploads\\/midia_1782314646.jpg","\\/uploads\\/thiago_gomes_1783572381.jpg"]'),
('exibir_ad_pos_pago', 'passivo'),
('tempo_anuncio', '5'),
('tempo_carencia', '15'),
('tempo_limite', '15');

-- --------------------------------------------------------
-- Tabela `crm_anunciantes`
-- --------------------------------------------------------

CREATE TABLE `crm_anunciantes` (
  `id` int(11) NOT NULL,
  `nome_empresa` varchar(255) NOT NULL,
  `telefone` varchar(50) DEFAULT NULL,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `crm_anunciantes` (`id`, `nome_empresa`, `telefone`, `criado_em`) VALUES
(1, 'THIAGO GOMES', '(88) 99656-7485', '2026-07-09 15:03:59'),
(2, 'TOCAKI', '', '2026-07-09 15:46:22');

-- --------------------------------------------------------
-- Tabela `crm_anuncios`
-- --------------------------------------------------------

-- [FIX 6] Coluna `cliques` adicionada — hotspot.php faz UPDATE cliques = cliques + 1
--         mas a coluna não existia no schema anterior.
CREATE TABLE `crm_anuncios` (
  `id` int(11) NOT NULL,
  `anunciante_id` int(11) NOT NULL,
  `tipo` enum('imagem','video') NOT NULL,
  `caminho_arquivo` varchar(255) NOT NULL,
  `link_destino` varchar(255) DEFAULT NULL,
  `exibir` enum('sim','nao') DEFAULT 'sim',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `localizacao` varchar(100) DEFAULT 'todos',
  `pacote` int(11) DEFAULT 30,
  `data_inicio` date DEFAULT NULL,
  `data_fim` date DEFAULT NULL,
  `pacote_tipo` enum('1dia','1semana','15dias') DEFAULT '1dia' COMMENT 'Tipo de pacote: 1 dia, 1 semana ou 15 dias',
  `valor_pacote` int(11) DEFAULT 0 COMMENT 'Valor do pacote em centavos',
  `cliques` int(11) NOT NULL DEFAULT 0 COMMENT 'Contador de cliques totais no anúncio'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `crm_anuncios` (`id`, `anunciante_id`, `tipo`, `caminho_arquivo`, `link_destino`, `exibir`, `criado_em`, `localizacao`, `pacote`, `data_inicio`, `data_fim`, `pacote_tipo`, `valor_pacote`, `cliques`) VALUES
(12, 2, 'imagem', '/uploads/crm_2_1783818736.jpg', 'https://tocaki.com.br', 'sim', '2026-07-12 01:12:17', 'todos', 30, '2026-07-11', '2026-07-31', '1dia', 20000, 0),
(13, 1, 'imagem', '/uploads/crm_1_1783819992.webp', 'https://tocaki.com.br', 'sim', '2026-07-12 01:33:12', 'todos', 30, '2026-07-11', '2026-07-25', '1dia', 5000, 0);

-- --------------------------------------------------------
-- Tabela `crm_cliques`
-- --------------------------------------------------------

CREATE TABLE `crm_cliques` (
  `id` int(11) NOT NULL,
  `anuncio_id` int(11) NOT NULL,
  `data_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `crm_cliques` (`id`, `anuncio_id`, `data_registro`) VALUES
(31, 12, '2026-07-12 03:10:30');

-- --------------------------------------------------------
-- Tabela `crm_configuracoes`
-- --------------------------------------------------------

CREATE TABLE `crm_configuracoes` (
  `id` int(11) NOT NULL,
  `chave` varchar(50) NOT NULL,
  `valor` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `crm_configuracoes` (`id`, `chave`, `valor`) VALUES
(1, 'tempo_anuncio', '15'),
(2, 'tempo_gratis', '30');

-- --------------------------------------------------------
-- Tabela `crm_roteadores`
-- --------------------------------------------------------

CREATE TABLE `crm_roteadores` (
  `id` int(11) NOT NULL,
  `nome_identificador` varchar(100) NOT NULL,
  `host` varchar(255) NOT NULL,
  `user` varchar(100) NOT NULL,
  `pass` varchar(255) NOT NULL,
  `port` int(11) DEFAULT 8080,
  `hotspot_ip` varchar(50) NOT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `crm_roteadores` (`id`, `nome_identificador`, `host`, `user`, `pass`, `port`, `hotspot_ip`, `is_default`, `criado_em`) VALUES
(3, 'sobral', 'api.deboananet.club', 'admin', 'xtz900af', 8080, '10.50.0.1', 1, '2026-07-12 00:01:02'),
(4, 'fortaleza', '189.45.78.164', 'mulato', 'vtgd65aoty', 8080, '10.50.0.1', 0, '2026-07-12 00:02:00');

-- --------------------------------------------------------
-- Tabela `crm_views`
-- --------------------------------------------------------

CREATE TABLE `crm_views` (
  `id` int(11) NOT NULL,
  `anuncio_id` int(11) NOT NULL,
  `data_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `crm_views` (`id`, `anuncio_id`, `data_registro`) VALUES
(48, 13, '2026-07-12 01:33:58'),
(49, 12, '2026-07-12 01:34:19'),
(50, 12, '2026-07-12 01:34:25'),
(51, 12, '2026-07-12 01:34:28'),
(52, 13, '2026-07-12 01:34:39'),
(53, 13, '2026-07-12 01:34:55'),
(54, 13, '2026-07-12 02:53:35'),
(55, 12, '2026-07-12 02:53:42'),
(56, 13, '2026-07-12 03:00:26'),
(57, 12, '2026-07-12 03:00:50'),
(58, 13, '2026-07-12 03:01:32'),
(59, 12, '2026-07-12 03:01:43'),
(60, 12, '2026-07-12 03:10:16'),
(61, 12, '2026-07-12 03:15:27'),
(62, 13, '2026-07-12 03:16:41'),
(63, 13, '2026-07-12 03:30:34'),
(64, 12, '2026-07-12 03:33:00'),
(65, 13, '2026-07-12 03:45:23'),
(66, 13, '2026-07-12 18:10:12'),
(67, 13, '2026-07-12 18:29:15'),
(68, 13, '2026-07-12 18:30:56'),
(69, 13, '2026-07-12 18:32:03'),
(70, 13, '2026-07-13 03:46:24'),
(71, 13, '2026-07-13 15:15:59'),
(72, 13, '2026-07-13 20:20:12'),
(73, 13, '2026-07-13 21:58:34'),
(74, 13, '2026-07-13 22:41:49'),
(75, 12, '2026-07-14 00:49:03'),
(76, 13, '2026-07-15 14:33:55'),
(77, 12, '2026-07-15 14:34:10');

-- --------------------------------------------------------
-- Tabela `planos`
-- --------------------------------------------------------

CREATE TABLE `planos` (
  `id` int(11) NOT NULL,
  `name` varchar(100) NOT NULL,
  `price_cents` int(11) NOT NULL COMMENT 'Valor em cêntimos. Ex: 500 = R$ 5,00',
  `duration_minutes` int(11) NOT NULL COMMENT 'Tempo de navegação em minutos',
  `criado_em` timestamp NOT NULL DEFAULT current_timestamp(),
  `ativo` tinyint(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `planos` (`id`, `name`, `price_cents`, `duration_minutes`, `criado_em`, `ativo`) VALUES
(1, 'Acesso 1h', 1, 60, '2026-06-17 06:37:27', 1),
(2, 'Acesso 3h', 2, 180, '2026-06-17 06:37:27', 1),
(3, 'Acesso 8h', 600, 480, '2026-06-17 06:37:27', 0),
(4, 'Grátis', 0, 10, '2026-06-17 15:34:29', 1);

--
-- Índices para tabelas despejadas
--

-- [FIX 1] UNIQUE KEY em txid → impede inserção ou processamento duplo do mesmo pagamento
-- [FIX 7] Índices compostos → aceleram consultas de sessão ativa (mac_address + status)
ALTER TABLE `acessos_pix`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `txid` (`txid`),
  ADD KEY `plano_id` (`plano_id`),
  ADD KEY `idx_mac_status` (`mac_address`, `status`),
  ADD KEY `idx_status` (`status`);

ALTER TABLE `admin_users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

-- [FIX 3] Índices da tabela cliques_anuncio (nova tabela)
ALTER TABLE `cliques_anuncio`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_mac_address` (`mac_address`);

ALTER TABLE `configuracoes`
  ADD PRIMARY KEY (`chave`);

ALTER TABLE `crm_anunciantes`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `crm_anuncios`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anunciante_id` (`anunciante_id`),
  ADD KEY `idx_data_inicio` (`data_inicio`),
  ADD KEY `idx_data_fim` (`data_fim`),
  ADD KEY `idx_pacote_tipo` (`pacote_tipo`),
  ADD KEY `idx_status` (`exibir`);

ALTER TABLE `crm_cliques`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anuncio_id` (`anuncio_id`);

ALTER TABLE `crm_configuracoes`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `chave` (`chave`);

ALTER TABLE `crm_roteadores`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nome_identificador` (`nome_identificador`);

ALTER TABLE `crm_views`
  ADD PRIMARY KEY (`id`),
  ADD KEY `anuncio_id` (`anuncio_id`);

ALTER TABLE `planos`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT para tabelas despejadas
--

ALTER TABLE `acessos_pix`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=437;

ALTER TABLE `admin_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `cliques_anuncio`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1;

ALTER TABLE `crm_anunciantes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `crm_anuncios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

ALTER TABLE `crm_cliques`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

ALTER TABLE `crm_configuracoes`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `crm_roteadores`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

ALTER TABLE `crm_views`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=78;

ALTER TABLE `planos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- Restrições (Foreign Keys)
--

ALTER TABLE `acessos_pix`
  ADD CONSTRAINT `acessos_pix_ibfk_1` FOREIGN KEY (`plano_id`) REFERENCES `planos` (`id`) ON DELETE SET NULL;

ALTER TABLE `crm_anuncios`
  ADD CONSTRAINT `crm_anuncios_ibfk_1` FOREIGN KEY (`anunciante_id`) REFERENCES `crm_anunciantes` (`id`) ON DELETE CASCADE;

ALTER TABLE `crm_cliques`
  ADD CONSTRAINT `crm_cliques_ibfk_1` FOREIGN KEY (`anuncio_id`) REFERENCES `crm_anuncios` (`id`) ON DELETE CASCADE;

ALTER TABLE `crm_views`
  ADD CONSTRAINT `crm_views_ibfk_1` FOREIGN KEY (`anuncio_id`) REFERENCES `crm_anuncios` (`id`) ON DELETE CASCADE;

COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
