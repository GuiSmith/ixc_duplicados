#!/bin/bash

# Variaveis
NOME_ARQUIVO="cidade.php"
SSH_USER="root"
DESTINO_IP="192.168.29.132"
DESTINO_PORTA="22"
DESTINO_PASTA="/var/www"
SENHA_SSH="Senha123@"

# Perguntando o IP de destino
echo "Iniciando transferencia de arquivos via SCP para $SSH_USER@$DESTINO_IP:$DESTINO_PASTA"

sshpass -p $SENHA_SSH scp -P $DESTINO_PORTA -r $NOME_ARQUIVO $SSH_USER@$DESTINO_IP:$DESTINO_PASTA

if [ $? -eq 0 ]; then
    echo "Transferencia concluida com sucesso. Clique aqui para acessar o arquivo: https://$DESTINO_IP/$NOME_ARQUIVO"
else
    echo "Falha na transferencia, verifique e tente novamente"
fi