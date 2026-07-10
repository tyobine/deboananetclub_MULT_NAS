-- =====================================================
-- MIGRATION: Adicionar campos de pacotes e datas
-- =====================================================

-- Verificar se as colunas já existem antes de adicionar
ALTER TABLE crm_anuncios ADD COLUMN IF NOT EXISTS `data_inicio` DATETIME DEFAULT CURRENT_TIMESTAMP COMMENT 'Data de início da exibição';
ALTER TABLE crm_anuncios ADD COLUMN IF NOT EXISTS `data_fim` DATETIME NOT NULL COMMENT 'Data de fim da exibição';
ALTER TABLE crm_anuncios ADD COLUMN IF NOT EXISTS `pacote_tipo` ENUM('1dia', '1semana', '15dias') DEFAULT '1dia' COMMENT 'Tipo de pacote: 1 dia, 1 semana ou 15 dias';
ALTER TABLE crm_anuncios ADD COLUMN IF NOT EXISTS `valor_pacote` INT DEFAULT 0 COMMENT 'Valor do pacote em centavos';

-- Criar índices para melhorar performance nas consultas
ALTER TABLE crm_anuncios ADD INDEX IF NOT EXISTS `idx_data_inicio` (`data_inicio`);
ALTER TABLE crm_anuncios ADD INDEX IF NOT EXISTS `idx_data_fim` (`data_fim`);
ALTER TABLE crm_anuncios ADD INDEX IF NOT EXISTS `idx_pacote_tipo` (`pacote_tipo`);
ALTER TABLE crm_anuncios ADD INDEX IF NOT EXISTS `idx_status` (`exibir`);

-- =====================================================
-- VERIFICAÇÃO: Consultas úteis para o admin
-- =====================================================

-- Ver anúncios ativos
-- SELECT * FROM crm_anuncios WHERE exibir = 'sim' AND data_inicio <= NOW() AND data_fim > NOW();

-- Ver anúncios expirados
-- SELECT * FROM crm_anuncios WHERE data_fim <= NOW();

-- Ver anúncios programados (ainda não iniciados)
-- SELECT * FROM crm_anuncios WHERE data_inicio > NOW();

-- Receita total por pacote
-- SELECT pacote_tipo, COUNT(*) as quantidade, SUM(valor_pacote) as total_centavos, SUM(valor_pacote)/100 as total_reais FROM crm_anuncios GROUP BY pacote_tipo;

-- Anúncios vencendo em breve (próximos 3 dias)
-- SELECT id, anunciante_id, pacote_tipo, data_fim, DATEDIFF(data_fim, NOW()) as dias_restantes FROM crm_anuncios WHERE exibir = 'sim' AND DATEDIFF(data_fim, NOW()) BETWEEN 0 AND 3;
