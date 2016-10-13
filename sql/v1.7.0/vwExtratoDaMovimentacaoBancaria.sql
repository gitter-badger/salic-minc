-- ===========================================================================================
-- Autor: Rômulo Menhô Barbosa
-- Data de Criacao: 22/04/2016
-- Descricao: Extrato bancario da movimentacao com as entradas e saidas de recursos da contas.
-- ===========================================================================================

IF OBJECT_ID ('vwExtratoDaMovimentacaoBancaria', 'V') IS NOT NULL
DROP VIEW vwExtratoDaMovimentacaoBancaria ;
GO

SET ANSI_NULLS ON
GO
SET QUOTED_IDENTIFIER ON
GO
CREATE VIEW dbo.vwExtratoDaMovimentacaoBancaria
AS
SELECT b.idPronac,b.AnoProjeto+b.Sequencial as Pronac,b.NomeProjeto,a.stContaLancamento,
       CASE
	     WHEN a.stContaLancamento = 0
		   THEN 'Captação'
		   ELSE 'Movimentação'
		 END Tipo,
       a.nrAgenciaLancamento as Agencia,a.nrContaLancamento as NrConta,a.cdLancamento,a.dsLancamento as Lancamento,
       a.nrLancamento,a.dtLancamento,a.vlLancamento,a.stLancamento	    
FROM SAC.DBO.tbLancamentoBancario  a 
INNER JOIN SAC.dbo.Projetos        b on (a.idPronac = b.IdPRONAC)

GO 

GRANT  SELECT ON dbo.vwExtratoDaMovimentacaoBancaria  TO usuarios_internet
GO
