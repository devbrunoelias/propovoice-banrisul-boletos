1. Separar totalmente Sandbox e Produção

Hoje existe um conjunto único de credenciais. O ideal é salvar dois conjuntos:

Sandbox
- Client ID
- Client Secret
- URL OAuth2
- URL Base
- Código beneficiário

Produção
- Client ID
- Client Secret
- URL OAuth2
- URL Base
- Código beneficiário

O campo Ambiente define qual conjunto usar. Isso evita sobrescrever credenciais e reduz o risco de usar produção por engano.
____________________________________________________________________________________________________________________________

2. Criar modo de homologação segura

Quando o ambiente for Produção:

- exibir aviso vermelho no painel;
- pedir confirmação antes de registrar;
- remover automaticamente o código de barras fixo do sandbox;
- impedir o PDF exemplo;
- usar exclusivamente os dados retornados pelo boleto real;
- consultar o código de barras real.

AMBIENTE DE PRODUÇÃO ATIVO
Esta operação registrará uma cobrança real.
____________________________________________________________________________________________________________________________

3. Registrar dados de liquidação

Agora sabemos que:

- situacao_banrisul = L
representa boleto liquidado, e o retorno inclui:
- data_pagamento
- data_credito
- valor_pagamento
- valor_cobrado
- valor_creditado_debitado

O próximo refinamento é adicionar esses campos à tabela do plugin:
- data_pagamento
- data_credito
- valor_pagamento
- valor_cobrado

Assim o plugin terá histórico financeiro suficiente para conciliação.
____________________________________________________________________________________________________________________________

4. Automatizar apenas a situação local

O cron pode atualizar:
1. A → Aguardando pagamento
2. L → Pago
3. B → Boleto baixado
4. CIP R → Boleto rejeitado

Por enquanto, ele não deve alterar automaticamente a invoice do PROPOVOICE. 
Primeiro validar pelo menos um boleto real liquidado em homologação/produção.
____________________________________________________________________________________________________________________________

5. Preparar o webhook

Depois disso, crie a rota:
/wp-json/pbb/v1/webhook/banrisul

Inicialmente ela deve apenas:

- receber o POST;
- registrar o JSON;
- responder HTTP 200;
- guardar data e headers;
- não alterar a invoice automaticamente.

O webhook complementará o cron:
- Webhook → atualização imediata
- Cron → conferência periódica e recuperação de falhas
- Botão manual → consulta administrativa
____________________________________________________________________________________________________________________________

6. Solicitar produção ao Banrisul

Já faz sentido iniciar a solicitação, pois o plugin já validou:

- OAuth2 com escopo boletos;
- registro;
- emissão;
- consulta;
- baixa;
- estados ativo, rejeitado, baixado e liquidado;
- PDF Base64;
- integração com PROPOVOICE;
- rotina automática preparada.
____________________________________________________________________________________________________________________________

Peça ao Banrisul:

- credenciais de produção;
- URLs de produção;
- beneficiário/convênio real;
- requisitos de homologação;
- formato e autenticação do webhook;
- confirmação dos eventos enviados pelo webhook;
- confirmação sobre atualização da invoice após situacao_banrisul = L.


* Autor: Bruno Elias Ferreira
* Licença: Este projeto é distribuído sob a licença GNU General Public License v2.0 ou posterior (GPL-2.0-or-later).

Copyright © 2026 Bruno Elias Ferreira.