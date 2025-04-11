<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once '/var/www/includes/config_load.php';

$db = new DB;

$db->conecta(SERVIDOR,USUARIO,SENHA,BANCO);

if(!$db) {
    echo "Erro ao conectar ao banco de dados.\n";
    exit;
}

// $db->tipo = 'mostra';

$table = 'information_schema.columns';
$columns = ['TABLE_NAME', 'COLUMN_NAME'];
$where = [];
$where[] = ['TB' => 'column_name', 'OP' => 'IN', 'P' => 'cidade,id_cidade'];
$group = '';
$order = '';
$limit = '';

// ID das cidades a serem procuradas
$cidade_antiga = '4376';
$cidade_nova = '3653';

if($cidade_antiga == '' || $cidade_nova == '') {
    echo "Erro: É necessário informar as cidades a serem procuradas.\n";
    exit;
}

try {
    $result = $db->select($table, implode(',',$columns), $where, $group, $order, $limit);

    if (!$result) {
        echo "Erro ao consultar colunas de cidade: " . $db->getError()."\n";
        exit;
    }

    $colunas = [];
    foreach ($result as $row) {
        $colunas[$row['TABLE_NAME']] = $row['COLUMN_NAME'];
    }

    // foreach ($colunas as $tabela => $coluna) {
    //     echo "Tabela: $tabela, Coluna: $coluna\n";
    // }
    // echo "Colunas encontradas:\n";

    echo "Procurando colunas...\n";
    foreach ($colunas as $tabela => $coluna) {
        $onde = [];
        $onde[] = ['TB' => $coluna, 'OP' => '=', 'P' => $cidade_antiga];
        $response = $db->select($tabela, "id, $coluna", $onde, '', '', '');
        if($response->num_rows > 0){
            echo "Tabela: $tabela, Coluna: $coluna\n";
            echo $response->num_rows . " registros encontrados.\n";
        }
    }
} catch (Exception $th) {
    echo "Erro ao realizar consulta: " . $th->getMessage()."\n";
    echo "\n";
    exit;
}