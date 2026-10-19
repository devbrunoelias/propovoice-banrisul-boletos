## Roadmap de homologação e produção após a V7
- Este documento registra melhorias planejadas após a validação dos estados de cobrança na V7.

> Os itens abaixo não devem ser considerados funcionalidades concluídas na V7 sem confirmação no código-fonte.

1. Persistência dos dados de liquidação
Planejar a inclusão de colunas próprias para os dados retornados em uma liquidação:

- data_pagamento;
- data_credito;
- valor_pagamento;
- valor_cobrado;
- valor_creditado_debitado.

Esses campos poderão apoiar a conciliação financeira e uma futura atualização segura da invoice do PROPOVOICE.

A interpretação e disponibilidade dos campos devem ser confirmadas durante a homologação.

2. Proteção visual para produção
> Quando o ambiente selecionado for produção, exibir um aviso destacado:

>AMBIENTE DE PRODUÇÃO ATIVO
- Esta operação registrará uma cobrança bancária real.

Também deverá ser avaliada uma confirmação adicional antes do registro do boleto.

3. Apresentação dos dados
Formatar valores e datas apenas na interface:

150.30
    ↓
R$ 150,30

2026-08-13
    ↓
13/08/2026

4. Histórico dos boletos

Planejar a inclusão das seguintes informações:

- data de criação;
- data da última consulta;
- data do pagamento;
- valor pago;
- situação Banrisul;
- situação CIP;
- origem da atualização:
    manual;
    cron;
    webhook.

5. Prevenção de geração duplicada

Antes de registrar novamente um boleto vinculado à mesma invoice, apresentar uma confirmação:

> Já existe um boleto para esta invoice.
 Deseja consultar o boleto existente ou emitir novamente?

Essa validação é especialmente importante no ambiente de produção.

6. Receptor de webhook

Rota inicialmente planejada: /wp-json/pbb/v1/webhook/banrisul

Primeira etapa:

- receber o payload;
- registrar a data do recebimento;
- armazenar somente o conteúdo necessário;
- responder com HTTP 200;
- não alterar automaticamente a invoice.

> O processamento definitivo dependerá da confirmação pelo Banrisul de:

- autenticação da chamada;
- formato do payload;
- eventos enviados;
- política de repetição;
- critérios de validação.

> Não devem ser registrados em logs:

- credenciais;
- tokens;
- cabeçalhos de autenticação;
- dados pessoais desnecessários;
- payloads integrais quando não forem necessários.

7. Revisão de segurança

> Antes da utilização em produção, revisar:

- nonces nos formulários;
- capabilities administrativas;
- sanitização das entradas;
- escaping das saídas;
- consultas SQL preparadas;
- proteção da página pública;
- validação dos tokens públicos;
- logs sem credenciais;
- retenção de payloads;
- prevenção de requisições duplicadas.

* Autor: Bruno Elias Ferreira
* Licença: Este projeto é distribuído sob a licença GNU General Public License v2.0 ou posterior (GPL-2.0-or-later).

Copyright © 2026 Bruno Elias Ferreira.