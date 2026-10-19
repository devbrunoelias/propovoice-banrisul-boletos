## Gerar por Invoice
- → POST admin-post.php
- → handle_generate_boleto()
- → build_banrisul_payload()
- → Registrar boleto

## Prioridade GERAL

1. Juros, multa e baixa
2. Testar no Sandbox
3. Teste real controlado
4. Upload protegido da NFe
5. Página única com boleto e nota
6. Integração com o e-mail do Propovoice


### O payload_envio confirma que o plugin enviou corretamente:

- No teste realizado nesta versão, o payload utilizou ambiente = T, correspondente ao cenário de teste adotado durante o desenvolvimento.
- juros mensais de 5,20%;
- multa de 2,0%;
- baixa automática após 5 dias corridos;
- sem datas explícitas, então juros e multa começam no dia seguinte ao vencimento, conforme o schema do Banrisul.

______________________________________________________

* Autor: Bruno Elias Ferreira
* Licença: Este projeto é distribuído sob a licença GNU General Public License v2.0 ou posterior (GPL-2.0-or-later).

Copyright © 2026 Bruno Elias Ferreira.