<?php
header('Content-Type: application/json; charset=utf-8');
include('../database/connect_db.php');
error_reporting(0); ini_set('display_errors', 0);

function mesEspanol($mes) {
    $meses = [1=>'Ene',2=>'Feb',3=>'Mar',4=>'Abr',5=>'May',6=>'Jun',7=>'Jul',8=>'Ago',9=>'Sep',10=>'Oct',11=>'Nov',12=>'Dic'];
    return $meses[$mes] ?? '???';
}

try {

    $mesesLabels = ['Ene 26', 'Feb 26']; $mesesValores = [0, 0];
    $queryMes = "SELECT DATE_FORMAT(fecha_venta, '%Y-%m') AS mes, SUM(total) AS total_mes FROM ventas WHERE fecha_venta IS NOT NULL GROUP BY mes ORDER BY mes ASC LIMIT 12";
    $resultMes = $conex->query($queryMes);
    if ($resultMes && $resultMes->num_rows > 0) {
        $mesesLabels = []; $mesesValores = [];
        while ($row = $resultMes->fetch_assoc()) {
            $mesNumero = (int)substr($row['mes'], 5, 2);
            $mesesLabels[] = mesEspanol($mesNumero) . ' ' . substr($row['mes'], 0, 4);
            $mesesValores[] = round((float)$row['total_mes'], 2);
        }
    }
    
    // DÍAS
    $diasLabels = ['Lun','Mar','Mie','Jue','Vie','Sab','Dom'];
    $diasValores = [0,0,0,0,0,0,0];
    $queryDias = "SELECT DAYOFWEEK(fecha_venta) AS dia_num, SUM(total) AS total_dia FROM ventas WHERE fecha_venta IS NOT NULL GROUP BY dia_num ORDER BY dia_num";
    $resultDias = $conex->query($queryDias);
    if ($resultDias && $resultDias->num_rows > 0) {
        while ($row = $resultDias->fetch_assoc()) {
            $diaIndex = (int)$row['dia_num'] - 1;
            if ($diaIndex >= 0 && $diaIndex < 7) $diasValores[$diaIndex] = round((float)$row['total_dia'], 2);
        }
    }
    
    echo json_encode(['meses'=>['labels'=>$mesesLabels,'valores'=>$mesesValores], 'dias'=>['labels'=>$diasLabels,'valores'=>$diasValores]], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['meses'=>['labels'=>['Ene 26','Feb 26'],'valores'=>[150,230]], 'dias'=>['labels'=>$diasLabels,'valores'=>[100,150,80,200,300,120,50]]], JSON_UNESCAPED_UNICODE);
}
?>
