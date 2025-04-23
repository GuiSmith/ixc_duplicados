<?php

// Configurações PHP para mostrar erros na tela
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Conectando com o banco de dados
$config_file = '/var/www/includes/config_load.php';
if(file_exists($config_file)){
    require_once $config_file;
}else{
    echo "Arquivo de configuração não encontrado: $config_file\n";
    exit;
}

try {
    $db = new DB;

    $db->conecta(SERVIDOR,USUARIO,SENHA,BANCO);

    if(!$db) {
        echo "Não foi possível conectar no banco de dados.\n";
        exit;
    }
} catch (DBException $e) {
    echo "Erro ao conectar ao banco de dados: " . $e->getMessage() . "\n";
    exit;
}

// Consulta que checa todas as tabelas do banco de dados que contém alguma coluna referenciando estado
$sql_uf =
    "SELECT
        table_name, 
        column_name
    FROM information_schema.columns
    WHERE table_schema = 'ixcprovedor'
        AND data_type = 'int'
        AND (column_name IN ('uf', 'id_uf')
            OR column_name LIKE 'uf\_%'
            OR column_name LIKE '%\_uf'
            OR column_name LIKE '%\_uf\_%');";

// Consulta que checa todas as tabelas do banco de dados que contém alguma coluna referenciando cidade
$sql_cidade =
    "SELECT
        table_name, 
        column_name
    FROM information_schema.columns
    WHERE table_schema = 'ixcprovedor'
        AND data_type = 'int'
        AND (column_name IN ('cidade', 'id_cidade')
            OR column_name LIKE 'cidade\_%'
            OR column_name LIKE '%\_cidade'
            OR column_name LIKE '%\_cidade\_%');";

// Define se a busca será de cidades ou de estados
// 'cidade' ou 'uf'
$procura = '';

// Relação de IDs antigos e novos
$relacao = [];

// Verificando se a relação foi informada
if(empty($relacao)) {
    echo "É necessário informar a relacao a ser procurada.\n";
    exit;
}else{
    echo "Relacao:\n";
    foreach ($relacao as $item) {
        echo "[{$item['antiga']} => {$item['nova']}]\n";
    }
    echo "\n";
}

// Buscando colunas
try {
    echo "Buscando colunas...\n\n";
    switch($procura){
        case 'cidade':
            $sql_consulta = $sql_cidade;
            break;
        case 'uf':
            $sql_consulta = $sql_uf;
            break;
        default:
            echo "Tipo de procura inválido. Use 'cidade' ou 'uf'.\n";
            exit;
    }
    $schemas_result = $db->query($sql_consulta);

    $schemas = [];

    foreach ($schemas_result as $schema) {
        $schemas[] = [
            'table_name' => $schema['table_name'],
            'column_name' => $schema['column_name']
        ];
        echo "[{$schema['table_name']} => {$schema['column_name']}]\n";
    }
    echo "\n";

    $bkp_file_content = '';
    $ajuste_file_content = '';
    $total_registros = 0;

    // Verificar em cada tabela-coluna se existe registros com os IDs antigos
    foreach ($schemas as $schema) {
        $ids_antigas = implode(',', array_map(fn($item) => $item['antiga'], $relacao));

        $registros_query = "SELECT id, {$schema['column_name']} FROM {$schema['table_name']} WHERE {$schema['column_name']} IN ($ids_antigas)";
        
        try {
            echo "Consultando {$schema['table_name']}...\n";
            $registros_result = $db->query($registros_query);
        } catch (Exception $e) {
            echo "Erro ao consultar registros na tabela {$schema['table_name']}: " . $db->getError() . "\n";
            exit;
        }

        echo "Quantidade: {$registros_result->num_rows}\n";
        if($registros_result->num_rows > 0){
            $ajuste_file_content .= "\n-- Tabela: {$schema['table_name']}\n";
            $bkp_file_content .= "\n-- Tabela: {$schema['table_name']}\n";
            $total_registros += $registros_result->num_rows;
            
            $case_string = implode(' ', array_map(fn($item) => "WHEN {$item['antiga']} THEN {$item['nova']}", $relacao));
            
            $ajuste_file_content .=
                "UPDATE {$schema['table_name']} SET {$schema['column_name']} = CASE {$schema['column_name']} $case_string END WHERE {$schema['column_name']} IN ($ids_antigas)\n";
            
            foreach ($registros_result as $registro) {
                $bkp_file_content .=
                    "UPDATE {$schema['table_name']} SET {$schema['column_name']} = {$registro[$schema['column_name']]} WHERE id = {$registro['id']};\n";
            }
        }
    }

    // Criando variáveis para criação de arquivos
    echo "\n\nTotal registros: $total_registros\n";
    if($total_registros == 0){
        echo "Nenhum registro encontrado.\n";
    }else{
        $caminho = "/tmp/";
        $nome_arquivo_ajuste = "ajuste_sup_banco.sql";
        $nome_arquivo_bkp = "bkp_sup_banco.sql";
        $data_hoje = date('Y_m_d_H_i_s');

        // Criando arquivo de ajuste
        if(!file_exists($caminho.$nome_arquivo_ajuste)){
            file_put_contents($caminho.$nome_arquivo_ajuste, $ajuste_file_content);
            if(file_exists($caminho.$nome_arquivo_ajuste)){
                echo "Arquivo ".$caminho.$nome_arquivo_ajuste." criado com sucesso...\n";
            }else{
                echo "Erro ao criar arquivo ".$caminho.$nome_arquivo_ajuste."...\n";
            }
        }else{
            file_put_contents($caminho.$data_hoje.$nome_arquivo_ajuste, $ajuste_file_content);
            if(file_exists($caminho.$data_hoje.$nome_arquivo_ajuste)){
                echo "Arquivo ".$caminho.$data_hoje.$nome_arquivo_ajuste." criado com sucesso...\n";
            }else{
                echo "Erro ao criar arquivo ".$caminho.$data_hoje.$nome_arquivo_ajuste."...\n";
            }
        }

        // Criando arquivo de backup
        if(!file_exists($caminho.$nome_arquivo_bkp)){
            file_put_contents($caminho.$nome_arquivo_bkp, $ajuste_file_content);
            if(file_exists($caminho.$nome_arquivo_bkp)){
                echo "Arquivo ".$caminho.$nome_arquivo_bkp." criado com sucesso...\n";
            }else{
                echo "Erro ao criar arquivo ".$caminho.$nome_arquivo_bkp."...\n";
            }
        }else{
            file_put_contents($caminho.$data_hoje.$nome_arquivo_bkp, $bkp_file_content);
            if(file_exists($caminho.$data_hoje.$nome_arquivo_bkp)){
                echo "Arquivo ".$caminho.$data_hoje.$nome_arquivo_bkp." criado com sucesso...\n";
            }else{
                echo "Erro ao criar arquivo ".$caminho.$data_hoje.$nome_arquivo_bkp."...\n";
            }
        }

        // Fim
        echo "Arquivos criados com sucesso:\n";
    }
    echo "Fim do script. Desenvolvido por Guilherme Smith :)\n";
} catch (Exception $th) {
    echo "Erro ao realizar consulta: " . $th->getMessage()."\n";
    echo "\n";
    exit;
}