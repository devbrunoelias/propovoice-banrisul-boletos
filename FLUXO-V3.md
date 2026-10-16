## Fluxo funcional da V3

> A v3 implementa o seguinte fluxo:

1. PROPOVOICE invoice
↓
2. Plugin busca invoice
↓
3. Plugin busca cliente
↓
4. Plugin registra boleto no Banrisul sandbox
↓
5. Plugin salva linha digitável
↓
6. Plugin salva PDF exemplo do sandbox
↓
7. Plugin exibe boleto em página pública protegida por token