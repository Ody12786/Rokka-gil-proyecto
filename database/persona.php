<?php

include("connect_db.php");

// if($conex){
//        echo "todo bien";
//     }

 

    $nombre=trim($_POST['nombre']);
    $apellido=trim($_POST['apellido']);
    $cedula=trim($_POST['cedula']);
    $sexo=trim($_POST['sexo']);
    $fecha=trim($_POST['fecha']);
    $carnet=trim($_POST['carnet']);


    $validar=mysqli_query($conex, "SELECT * FROM persona 
    WHERE Ci ='$cedula'");
    if(mysqli_num_rows($validar) > 0){

        echo 5;//esto quiere decir que ya esta registrada 
        //la cedula en el sistema y es un dato que no se puede repetir.
        exit();
    }else{

        $verificar=mysqli_query($conex, "SELECT * FROM empleado 
        WHERE carnet ='$carnet'");
        if(mysqli_num_rows($verificar) > 0){
    
            echo 4;//esto quiere decir que ya esta registrada 
            //el carnet en el sistema y es un dato que no se puede repetir.
            exit();
        }else{
            
            $consulta = "INSERT INTO persona (Ci,Nombre_p,Apellido,Sexo,F_nac)
            VALUES('$cedula','$nombre','$apellido','$sexo','$fecha')";

            $resultado= mysqli_query($conex,$consulta);
            if($resultado){

                           $consultad = "INSERT INTO empleado (cedula,carnet)
                            VALUES('$cedula','$carnet')";
                            $resultadod= mysqli_query($conex,$consultad);

                            echo 1;//registro correctamente

                            exit();
                            }else{

                                echo 2;//esto quiere decir que no registro los datos del empleado
                                exit(); 
                            }
            
            

            }
    }


        





   




    // $consulta = "INSERT INTO persona (Ci,Nombre,Apellido,Sexo,F_nac)
    //  VALUES('$cedula','$nombre','$apellido','$sexo','$fecha')";

    // $resultado= mysqli_query($conex,$consulta);
    // if($resultado){   
    //      $consultad = "INSERT INTO empleado (cedula,carnet)
    //     VALUES('$cedula','$carnet')";
    //         $resultadod= mysqli_query($conex,$consultad);

    //             echo 1;

    //             exit();
    //         }else{

    //         echo 3;
    //         exit();

    //   }


?>