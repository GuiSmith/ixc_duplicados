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

$sql =
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

// ID das cidades a serem procuradas
$cidade_antiga = '4376';
$cidade_nova = '3653';

if($cidade_antiga == '' || $cidade_nova == '') {
    echo "Erro: É necessário informar as cidades a serem procuradas.\n";
    exit;
}

echo "Cidade antiga: $cidade_antiga\n";
echo "Cidade nova: $cidade_nova\n\n";

try {
    $result = $db->query($sql);

    if (!$result) {
        echo "Erro ao consultar colunas de cidade: " . $db->getError()."\n";
        exit;
    }

    $colunas = [];
    $bkp_file_content = '';
    $ajuste_file_content = '';
    $total_registros = 0;
    foreach ($result as $row) {
        $registros = $db->select($row['TABLE_NAME'], 'id,'.$row['COLUMN_NAME'], [['TB' => $row['COLUMN_NAME'], 'OP' => '=', 'P' => $cidade_antiga]], '', '', '');
        if($registros->num_rows > 0){
            echo "Registros encontrados na tabela {$row['TABLE_NAME']}: ";
            echo print_r($registros->num_rows, true);
            echo "\n";

            $bkp_file_content .= "-- Tabela: {$row['TABLE_NAME']}\n";
            $ajuste_file_content .= "-- Tabela: {$row['TABLE_NAME']}\n";
            foreach ($registros as $registro) {
                $bkp_file_content .= "UPDATE {$row['TABLE_NAME']} SET {$row['COLUMN_NAME']} = {$registro[$row['COLUMN_NAME']]} WHERE id = {$registro['id']};\n";
                $ajuste_file_content .= "UPDATE {$row['TABLE_NAME']} SET {$row['COLUMN_NAME']} = {$cidade_nova} WHERE id = {$registro['id']};\n";
            }
            echo "\n";
        }
    }
    echo "\n\nBKP:\n$bkp_file_content";
    echo "\n\nBKP:\n$ajuste_file_content";
} catch (Exception $th) {
    echo "Erro ao realizar consulta: " . $th->getMessage()."\n";
    echo "\n";
    exit;
}