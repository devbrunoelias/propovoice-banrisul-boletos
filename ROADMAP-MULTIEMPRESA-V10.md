## Roadmap multiempresa após a V10
- Este documento registra os requisitos planejados para permitir que o plugin opere com mais de uma empresa e mais de um beneficiário.

Os itens abaixo não devem ser considerados funcionalidades concluídas na V10 sem confirmação no código-fonte.

## Objetivo
- Permitir que cada empresa utilize sua própria configuração de integração, aplicação, beneficiário e ambiente, evitando o compartilhamento indevido de credenciais ou identificadores.

## Requisitos planejados
1. Confirmar com o Banrisul se cada beneficiário exige uma aplicação própria.
2. Definir identificadores internos estáveis para as empresas.
3. Adicionar à tabela do plugin:
    -    empresa_id;
    -    beneficiario_codigo.
4. Criar uma configuração centralizada de empresas.
5. Adicionar seleção da empresa no formulário de geração.
6. Adaptar PBB_Banrisul_API para utilizar a configuração da empresa selecionada.
7. Adaptar o registro, a emissão e o armazenamento do PDF.
8. Adaptar a consulta manual para múltiplas empresas.
9. Adaptar o WP-Cron para utilizar a empresa armazenada no registro do boleto.
10. Preparar um endpoint HTTPS para o webhook.
11. Identificar a empresa associada ao evento pelo código do beneficiário.
12. Definir os controles de autenticação e origem do webhook conforme orientação oficial do Banrisul.
13. Testar o webhook no ambiente sandbox ou de homologação.
14. Solicitar o cadastro produtivo dos beneficiários.
15. Criar um ambiente separado de homologação.
16. Homologar cada empresa conforme as orientações vigentes do Banrisul.

## Requisitos de segurança
- Cada empresa deve possuir configurações e credenciais independentes.
- Um boleto deve permanecer vinculado à empresa que o originou.
- Consultas manuais e automáticas devem utilizar a empresa vinculada ao boleto, e não a seleção atual do painel.
- O webhook não deve confiar somente em campos enviados no payload.
- A autenticação e a origem do webhook devem ser validadas conforme a especificação oficial vigente.
- Credenciais reais e códigos reais de beneficiários não devem ser incluídos neste documento.


* Autor: Bruno Elias Ferreira
* Licença: Este projeto é distribuído sob a licença GNU General Public License v2.0 ou posterior (GPL-2.0-or-later).

Copyright © 2026 Bruno Elias Ferreira.