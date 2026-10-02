CMF MAINTENANCE ENTERPRISE
Desenvolva uma plataforma corporativa completa de Gestão de Manutenção para equipamentos pesados, mineração, linha amarela, caminhões e veículos de apoio.
Objetivo
Criar um sistema moderno, responsivo e escalável para controlar:
•	Solicitações de manutenção
•	Ordens de Serviço (OS)
•	Estoque de peças
•	Manutenções preventivas
•	Componentes
•	Análises SOS
•	Histórico dos equipamentos
•	Custos
•	Indicadores de manutenção
O sistema deve possuir aparência profissional semelhante a SAP PM, Engeman, Sisloc, IBM Maximo e Hexagon EAM.
Tema escuro industrial com detalhes em laranja.
________________________________________
TECNOLOGIAS
peça ao dev para incorporar as tecnologias que mais se encaixem nesse sistema, mas se possivel de preferencia para laravel/fillament
________________________________________
PERFIS DE USUÁRIOS
Operador
Permissões:
•	Abrir solicitações
•	Consultar solicitações
Mecânico
Permissões:
•	Visualizar OS atribuídas
•	Executar manutenção
•	Registrar diagnóstico
•	Registrar peças utilizadas
•	Registrar horas trabalhadas
•	Encerrar OS
Controlador
Permissões:
•	Gerar Ordens de Serviço
•	Designar mecânicos
•	Controlar estoque
•	Liberar peças
•	Gerenciar movimentações
•	Programar preventivas
•	Gerenciar cronogramas
•	Acompanhar indicadores
•	Visualizar dashboards
•	Visualizar relatórios
•	Acompanhar custos
Gerente
Permissões:
•	Visualizar dashboards
•	Visualizar relatórios
•	Acompanhar custos
Administrador
Permissões totais.
________________________________________
FLUXO OPERACIONAL
1.	Operador, Mecânico ou Controlador identifica uma falha.
2.	Usuário abre uma Solicitação de Manutenção.
3.	Sistema gera automaticamente uma Ordem de Serviço.
4.	Controlador analisa e define prioridade.
5.	Mecânico recebe a Ordem de Serviço.
6.	Mecânico executa o serviço.
7.	Mecânico solicita peças.
8.	Controlador libera peças.
9.	Mecânico registra mão de obra.
10.	Mecânico registra materiais utilizados.
11.	Mecânico anexa fotos antes e depois.
12.	Mecânico encerra a Ordem de Serviço.
13.	Sistema atualiza automaticamente:
•	Histórico do equipamento
•	Horímetro
•	Estoque
•	Custos
•	Indicadores
________________________________________
DASHBOARD
Cards:
•	Disponibilidade da Frota
•	Equipamentos Operando
•	Equipamentos Parados
•	OS Abertas
•	OS Em Andamento
•	OS Encerradas
•	OS Atrasadas
•	Preventivas Vencendo
•	Custo Mensal
Indicadores:
•	MTBF
•	MTTR
•	Backlog de Manutenção
•	Custo por Equipamento
•	Custo por TAG
•	Custo por Contrato
Gráficos:
•	Custos por Equipamento
•	Custos por Fabricante
•	Custos Mensais
•	Corretiva x Preventiva
•	Tendência de Quebras
•	Top 10 Equipamentos com Maior Custo
________________________________________
MÓDULO EQUIPAMENTOS
Campos:
•	ID
•	TAG
•	Número Patrimonial
•	Fabricante
•	Modelo
•	Número de Série
•	Ano
•	Horímetro Atual
•	Quilometragem
•	Centro de Custo
•	Contrato
•	Localização
•	Criticidade
•	QR Code
•	Status
Criticidade:
•	Baixa
•	Média
•	Alta
•	Crítica
Status:
•	Operando
•	Em Manutenção
•	Disponível
•	Inativo
Gerar QR Code automaticamente para cada equipamento.
________________________________________
MÓDULO SOLICITAÇÕES
Campos:
•	Número da Solicitação
•	Equipamento
•	Horímetro
•	Prioridade
•	Descrição
•	Fotos
•	Solicitante
•	Data
Prioridades:
•	Baixa
•	Média
•	Alta
•	Crítica
Status:
•	Aberta
•	Em Análise
•	Aprovada
•	Rejeitada
Exibir em Kanban.
________________________________________
MÓDULO ORDENS DE SERVIÇO
Campos:
•	Número da OS
•	Equipamento
•	Mecânico Responsável
•	Data Início
•	Data Fim
•	Descrição da Falha
•	Diagnóstico
•	Causa Raiz
•	Serviço Executado
•	Horas Trabalhadas
•	Horas Paradas
•	Custo Total
Status:
•	Aberta
•	Em Andamento
•	Aguardando Peças
•	Encerrada
Visualização Timeline.
________________________________________
MÓDULO ESTOQUE
Cadastro:
•	Código
•	Descrição
•	Fabricante
•	Categoria
•	Unidade
•	Custo Unitário
•	Estoque Atual
•	Estoque Mínimo
•	Estoque Máximo
•	Localização
Movimentações:
•	Entrada
•	Saída
•	Transferência
•	Ajuste
•	Inventário
Baixa automática ao consumir peças na OS.
________________________________________
MÓDULO MANUTENÇÃO PREVENTIVA
Planos:
•	500h
•	1000h
•	2000h
•	4000h
•	8000h
Programação por:
•	Horímetro
•	Quilometragem
•	Calendário
Gerar automaticamente OS Preventiva.
Alertas:
Verde = mais de 100h restantes
Amarelo = menos de 100h restantes
Vermelho = vencida
________________________________________
MÓDULO COMPONENTES
Controlar:
•	Motor
•	Transmissão
•	Conversor
•	Diferencial
•	Bombas Hidráulicas
•	Redutores Finais
Campos:
•	Número de Série
•	Data Instalação
•	Horas Trabalhadas
•	Horas Reforma
•	Vida Útil
•	Custo Reposição
________________________________________
MÓDULO PNEUS E RODANTES
Campos:
•	Número de Fogo
•	Marca
•	Modelo
•	Medida
•	Equipamento
•	Posição
•	Data Instalação
•	Horas Trabalhadas
•	Sulco Inicial
•	Sulco Atual
•	Status
Status:
•	Em Uso
•	Reserva
•	Reforma
•	Sucata
Criar histórico e custo por pneu.
________________________________________
MÓDULO ANÁLISE SOS
Campos:
•	Número da Amostra
•	Equipamento
•	Componente
•	Data Coleta
•	Laboratório
•	Resultado
•	Recomendação
Permitir anexar PDF.
Exibir histórico e tendência.
________________________________________
MÓDULO HISTÓRICO
Criar timeline completa contendo:
•	Ordens de Serviço
•	Preventivas
•	Corretivas
•	Troca de Componentes
•	Análises SOS
•	Custos
•	Evolução do Horímetro
________________________________________
RELATÓRIOS
•	Disponibilidade
•	MTBF
•	MTTR
•	Equipamentos Parados
•	OS Abertas
•	OS Encerradas
•	Custos por Equipamento
•	Custos por TAG
•	Custos por Contrato
•	Consumo de Peças
•	Preventivas Executadas
•	Preventivas Pendentes
Exportação:
•	PDF
•	Excel
________________________________________
ASSINATURAS DIGITAIS
Implementar assinatura:
•	Mecânico
•	Controlador
•	Gerente
Salvar:
•	Imagem
•	Data
•	Hora
•	Usuário
________________________________________
AUDITORIA
Registrar:
•	Usuário
•	Data
•	Hora
•	Ação
•	Valor Anterior
•	Novo Valor
Para todas as alterações críticas.
________________________________________
BANCO DE DADOS
Criar tabelas:
usuarios
perfis
permissoes
equipamentos
solicitacoes
ordens_servico
mao_obra_os
pecas_os
pecas
movimentacoes_estoque
planos_preventivos
execucoes_preventivas
componentes
historico_componentes
analises_sos
historico_equipamentos
pneus
historico_pneus
anexos
notificacoes
auditoria
contratos
centros_custo
localizacoes
________________________________________
MENU PRINCIPAL
Dashboard
Equipamentos
Solicitações
Ordens de Serviço
Estoque
Preventivas
Componentes
Pneus e Rodantes
SOS
Histórico
Relatórios
Administração
________________________________________
REQUISITO FINAL
Gerar aplicação totalmente funcional com:
•	Banco de dados PostgreSQL criado
•	Supabase configurado
•	Autenticação implementada
•	CRUD completo em todos os módulos
•	Controle de permissões por perfil
•	Dashboard funcional
•	Relatórios funcionais
•	Upload de fotos e documentos
•	QR Code por equipamento
•	Assinaturas digitais
•	Auditoria completa
•	Dados de exemplo realistas
O sistema deve estar preparado para operar com mais 500 equipamentos e mais de 10 usuários simultâneos.
