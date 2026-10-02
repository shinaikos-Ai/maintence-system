# Operação e regras

## Fluxo corretivo

1. Usuário abre solicitação com equipamento, leitura, prioridade e descrição; a OS nasce na mesma transação.
2. Controlador analisa e aprova/rejeita. A OS aguarda aprovação para iniciar.
3. Controlador designa mecânico e prazo; mecânico vê apenas OS atribuídas.
4. Mecânico inicia, informa diagnóstico, causa e serviço, e solicita peças.
5. Controlador libera peças. **Liberação representa entrega e consumo**, baixando estoque uma única vez e congelando o custo aplicado.
6. Mecânico registra horas e anexa fotos Antes/Depois vinculadas à OS.
7. Encerramento exige diagnóstico, causa, serviço, mão de obra, ambas as fotos, leituras sem retrocesso e ausência de peças pendentes.
8. Histórico, custos, leituras e estado do equipamento são atualizados atomicamente. Outra OS ainda em execução mantém o equipamento em manutenção.
9. Mecânico responsável, controlador e gerente assinam após encerramento; uma assinatura por perfil/OS. Administrador assina identificado como administrador.

A assinatura é **eletrônica por imagem e autenticação**, com SHA-256 dos dados da OS. Não usa certificado ICP-Brasil ou serviço externo de assinatura qualificada.

## Permissões

| Perfil | Acesso |
|---|---|
| Operador | Equipamentos, suas solicitações e anexos |
| Mecânico | Solicitações próprias, OS atribuídas, execução, apontamentos, peças e assinatura |
| Controlador | Cadastros operacionais, análise, programação, estoque e relatórios |
| Gerente | Consulta, dashboards, relatórios e assinatura; sem alterar operação |
| Administrador | Usuários, cadastros, auditoria, operações e relatórios |

Access é a política executável. As tabelas perfis/permissoes registram a matriz inicial; não existe editor que altere as regras por fora do código. Usuários usam a tabela nativa users. Contas desativadas não acessam painel nem downloads.

Administrador exclui cadastros sem vínculos. OS, solicitações, movimentos, apontamentos, assinaturas e auditoria são preservados. Equipamentos com histórico podem ser inativados. Arquivos privados não são publicados por storage:link.

## Estoque

- Entrada atualiza custo médio ponderado; compras posteriores não alteram custo já aplicado em OS.
- Saída avulsa exige motivo.
- Transferência parcial preserva total da peça.
- Ajuste/inventário recebem o **saldo contado** na localização, não uma variação.
- Liberação para OS consome localizações por ordem de cadastro.
- Saldos não ficam negativos; liberação repetida é recusada. Bloqueios de linha serializam movimentos.
- Localização do cadastro é o endereço padrão; “Saldos por localização” mostra a distribuição física.

## Preventivas

Cadastre planos de 500h, 1000h, 2000h, 4000h, 8000h ou outros intervalos; a demonstração inclui 500h.

Informe base, intervalo, próxima leitura/data e antecedência. O comando maintenance:preventives roda a cada hora. No máximo uma execução pendente por plano. Encerramento agenda o próximo ciclo pela leitura/data de conclusão.

Horímetro: verde acima da antecedência; amarelo até 100h com antecedência 100; vermelho no vencimento ou após. Calendário usa dias e quilometragem usa km.

## Indicadores

- Disponibilidade atual: equipamentos ativos fora de manutenção / frota ativa.
- Disponibilidade no período: horas corridas disponíveis / observadas. OS sobrepostas não duplicam parada.
- MTBF por equipamento: horas corridas disponíveis / falhas corretivas iniciadas no período.
- MTTR por equipamento: duração média das corretivas encerradas no período.
- Cards MTBF/MTTR: média dos equipamentos com base suficiente; sem base aparece “—”.
- Backlog: quantidade de OS pendentes, não estimativa de horas.
- Custos: mão de obra + peças de OS encerradas no período. Aquisição/reforma de pneus/componentes ficam em seus históricos e não são adicionadas novamente às OS.
- Parados e OS abertas são posições atuais; datas filtram encerramentos, consumo e execuções conforme o relatório.

Disponibilidade por turno, produção efetiva e SLA contratual exigem calendários operacionais adicionais. A base atual de horas corridas fica explícita nos relatórios.

## Componentes, pneus e SOS

Alterações de cadastro/instalação geram histórico. Horas acumuladas somam horas anteriores e diferença do horímetro desde instalação. Na transferência, informe o horímetro de instalação no novo equipamento; o uso anterior é acumulado.

Laudos PDF são vinculados à análise SOS. Ferro/silício têm tendência por equipamento; viscosidade e recomendação ficam no registro. Não há interpretação automática do PDF.
