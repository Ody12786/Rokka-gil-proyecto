<?php

include("database/connect_db.php");

// if($conex){
//        echo "todo bien";
//     }

 if(isset($_POST['enviar'])){


    $nombre=trim($_POST['usuario']);
    $pass=trim($_POST['contrasena']);
    $carnet=trim($_POST['carnet']);
    $pregunta=trim($_POST['pregunta']);
    $respuesta=trim($_POST['respuesta']);
    $preguntad=trim($_POST['preguntaDos']);
    $respuestad=trim($_POST['respuestaDos']);
    $tipo=$_POST['tipo'];
   
    $verificar=mysqli_query($conex, "SELECT * FROM Empleado 
    WHERE carnet ='$carnet'");
    if(mysqli_num_rows($verificar) == 0){

        echo 4;//esto quiere decir que el carnet ingresado no existe en el sistema y 
        //no se puede registrar un usuario sin ser empleado de la empresa
        exit();
    }else{




    $consulta = "INSERT INTO Usuario (Nombre,Contrasena,tipo,carnet)
     VALUES('$nombre','$pass','$tipo',$carnet)";
     
    $resultado= mysqli_query($conex,$consulta);
    if($resultado){


        $consultaD = "INSERT INTO Recuperar_contrasena (P1,P2,R1,R2)
        VALUE('$pregunta','$preguntad','$respuesta','$respuestad')";
        $resultadoD= mysqli_query($conex,$consultaD);
        if($resultadoD){
            header("location:login.php"); 
            exit();
        }else{

            echo 2;
            exit();




     }


  }
//     echo 3;
//   }

}
 }
?>