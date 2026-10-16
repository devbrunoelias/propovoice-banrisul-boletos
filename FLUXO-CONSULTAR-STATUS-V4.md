## Fluxo de consulta manual de status da V4
- A V4 adiciona o botão Consultar status à tabela Últimos boletos gerados.

> Fluxo

# O botão Consultar status deve ficar na tabela Últimos boletos gerados, em cada linha, e ao clicar ele vai:

1. pegar o id do boleto salvo na tabela do plugin;
            ↓
2. buscar o boleto no banco;
            ↓
3. usar o codigo_barras;
            ↓
4. chamar GET Consultar boleto no Banrisul;
            ↓
5. atualizar: situacao_banrisul, situacao_cip, situacao_pagamento e status_local.