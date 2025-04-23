# Descrição
Este script é utilizado para trocar a cidade ou estado no sistema.

## Exemplo de Uso

Por algum motivo, a cidade **Chapecó** foi cadastrada duas vezes. Informe a relação de **ID antigo** e **ID novo**. O script irá:

1. Procurar em todas as tabelas que contêm a cidade como dado.
2. Identificar se há registros com o **ID antigo**.
3. Gerar dois arquivos (se houverem registros a serem alterados):
    - Um arquivo `.sql` para ajustar os registros para a **cidade nova**.
    - Um arquivo `.sql` de backup, para reverter as alterações, caso necessário.

Os arquivos gerados serão salvos na pasta `/tmp/` do servidor do cliente. O script deve ser executado no terminal e pode ser utilizado em qualquer lugar, desde que os parâmetros corretos sejam informados.

## Localização e Execução

- **Localização do arquivo:** `/home/ixcsoft/scripts/ixc_duplicados/README.md`
- **Local de execução:** Deve ser executado diretamente no terminal do servidor.
- **Requisitos:** Certifique-se de ter acesso ao servidor e permissões adequadas para executar scripts e gerar arquivos na pasta `/tmp/`.
- **Parâmetros necessários:** Informe corretamente os IDs antigo e novo para que o script funcione conforme esperado.