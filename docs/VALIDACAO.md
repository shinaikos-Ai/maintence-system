# Validação da entrega

Verificação em 02/10/2026.

| Verificação | Resultado |
|---|---|
| Testes em SQLite | 26 testes / 194 asserções passaram |
| Mesma suíte em PostgreSQL 17.11 | 26 testes / 194 asserções passaram |
| Dados de demonstração | 25 equipamentos, cinco perfis, OS abertas/em execução/encerradas |
| Telas e detalhes de todos os módulos com dados | Verificados por testes HTTP e Livewire |
| Solicitação criada pela ação da tela | Gera OS e vincula solicitante autenticado |
| Fluxo completo de OS | Estoque, custo, leituras e histórico conferidos |
| Peças | Saldo insuficiente e liberação duplicada recusados |
| Localizações | Transferência parcial, contagem e custo médio conferidos |
| Permissões | Perfis, OS de outro mecânico, anexos privados e conta inativa conferidos |
| Preventivas | Horas, km, calendário e geração idempotente conferidos |
| Indicadores | Paradas sobrepostas, disponibilidade, MTBF, MTTR e custos conferidos |
| Exportações | PDF, XLSX e SVG do QR Code gerados |
| Assinaturas | Vínculo com OS encerrada, hash e recusa de duplicata conferidos |
| Componentes/pneus | Histórico de reforma e custo conferidos |
| Catálogo com 550 equipamentos | Página paginada renderizada com sucesso |

Os testes verificam renderização no servidor, regras de negócio e ações Livewire. Não substituem inspeção visual em dispositivos reais.

## Infraestrutura pendente

- Supabase: conexão da conta pendente; nenhum projeto hospedado foi configurado.
- Implantação em servidor, domínio, HTTPS, backups e monitoramento: dependem do ambiente final.
- Docker: configuração fornecida, sem build executado neste ambiente.
- Mais de 10 usuários simultâneos: arquitetura usa sessões no banco, paginação, índices e transações com bloqueio de linha; **não foi executado ensaio de carga concorrente**. O teste de 550 equipamentos é funcional, não certificação de capacidade.
- Acesso PostgreSQL/Supabase: validar schema privado e exposição da Data API antes de produção. Nenhuma alteração de RLS/privilégios é aplicada automaticamente.

## Limites funcionais explícitos

- Assinatura por imagem autenticada, sem ICP-Brasil.
- Indicadores usam horas corridas, não calendário de turnos ou produção.
- Backlog é quantidade de OS; não há estimativa de horas pendentes.
- Fluxo de peças interpreta liberação como consumo; não há etapa separada de reserva/devolução nesta versão.
- Cadastros permitem manutenção e exclusão quando sem vínculos; registros auditáveis de operação são preservados.
- Custos dos relatórios são peças e mão de obra das OS encerradas. Custos de vida de pneus/componentes ficam nos respectivos históricos.
- Kanban agrupa solicitações; mudanças de status passam pela ação Analisar da lista.
- PDFs de SOS são armazenados, sem extração automática de resultados.
