<?php

include("database/connect_db.php");

// if($conex){
//        echo "todo bien";
//     }

 if(isset($_POST['enviar'])){


    $nombre=trim($_POST['nombre']);
    $apellido=trim($_POST['apellido']);
    $cedula=trim($_POST['cedula']);
    $sexo=trim($_POST['sexo']);
    $fecha=trim($_POST['fecha']);
    $carnet=trim($_POST['carnet']);





    $consulta = "INSERT INTO persona (Ci,Nombre_p,Apellido,Sexo,F_nac)
     VALUES('$cedula','$nombre','$apellido','$sexo','$fecha')";

    $resultado= mysqli_query($conex,$consulta);
        if($resultado){   
         $consultad = "INSERT INTO empleado (cedula,carnet)
          VALUES('$cedula','$carnet')";
            $resultadod= mysqli_query($conex,$consultad);

            echo 1;
            header("location:usuario.php"); 
            exit(); }
           
        else{

            echo 3;
            exit();

       }



}
?>