## Sanitização do histórico
- As versões V1 a V13 foram reconstruídas a partir de cópias locais e sanitizadas antes da publicação.

## V1 a V5
- As credenciais originalmente mantidas no código-fonte foram substituídas por marcadores não funcionais.

## V6 em diante
- As credenciais passaram a ser definidas externamente no wp-config.php da instalação WordPress.

> Os arquivos históricos foram renomeados para wp-config.example.php e contêm somente constantes com valores fictícios ou vazios.
    > Conteúdo removido
1.      Client IDs e Client Secrets reais;
2.      usuários e Application Passwords;
3.      chaves e salts do WordPress;
4.      credenciais do banco de dados;
5.      tokens OAuth2;
6.      arquivos Swagger restritos;
7.      dados pessoais de clientes;
8.      boletos e PDFs reais;
9.      logs, backups e respostas sensíveis das APIs.