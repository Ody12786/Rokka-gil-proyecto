// registro proveedor

// let apagado=document.querySelector('.aburido');
//     apagado.style.display="none";

let mensaje= document.querySelector('.mensajes');


// registro persona desde el modulo usuario



$('#avanzar').click(function(){

    let form=document.getElementById('formulario');
    // Capturando inputs del formulario <<registro persona>>
    let datos= $('#formulario').serialize();

    let nombre=document.getElementById('nombre');
    let apellido=document.getElementById('apellido');
    let cedula=document.getElementById('cedula');
    let carnet=document.getElementById('carnet');
    let genero=document.getElementById('genero');
    let fecha=document.getElementById('fecha');

    

    if(cedula.value === ""  || nombre.value==="" || carnet.value === ""  || apellido.value===""){
        mensaje.innerHTML = `<div class="error">
        <p >Ingrese los datos</p></div>`;
        return;
    
    }else if(nombre.value.length <= 2 ){
       
        mensaje.innerHTML = `<div class="error">
        <p >Ingrese un nombre valido</p></div>`;
        return;
        
    } else if(carnet.value.length != 6){

        mensaje.innerHTML = `<div class="error">
        <p >El carnet debe tener 6 digitos</p></div>`;
        return;   
    }
    else if(carnet.value  <= 99999){

        mensaje.innerHTML = `<div class="error">
        <p >Carnet Invalido, no puede iniciar en cero</p></div>`;
        return;   
    }else if(genero.value==="" ){
      
        mensaje.innerHTML = `<div class="error">
        <p >Seleccione el genero</p></div>`;
        return;
        
    }else if(apellido.value.length <= 2 ){

        mensaje.innerHTML = `<div class="error">
        <p >Ingrese un apellido valido</p></div>`;
        return;
        
    }else if(cedula.value.length <= 6 || cedula.value.length >= 9){
            // alert('Ingrese una cedula Correcta')
            mensaje.innerHTML = `<div class="error">
            <p >Ingrese una cedula correcta</p></div>`;
            return;
 
    }
    else if(cedula.value <= 1500000){
        // alert('Ingrese una cedula Correcta')
        mensaje.innerHTML = `<div class="error">
        <p >Numero de Cedula invalido </p></div>`;
        return;

}
    else if(fecha.value === ""   ){

        mensaje.innerHTML = `<div class="error">
        <p >Ingrese una fecha valida</p></div>`;
        return;
 
    }else{


    $.ajax({
        url:'../database/persona.php',
        type:'POST',
        data: datos,

        success:function(vs){



            if(vs == 1){
                
                mensaje.innerHTML = `<div class="exito">
                <p >Registro exitoso</p></div>`;

                form.reset();
                // $(buscarc());
                return;
            
            } else if(vs == 2){
                mensaje.innerHTML = `<div class="error">
                <p >Error al Registrar los datos</p></div>`;
                return;
                
            }    else if(vs == 4){
      
                mensaje.innerHTML = `<div class="error">
                <p >Carnet ya registrado</p></div>`;
                return;

            }else if(vs == 5){
     
                mensaje.innerHTML = `<div class="error">
                <p >Cedula ya registrada</p></div>`;
                return;
            }

        }


})}
})



// registro usuario desde el modulo usuario


$('#registra').click(function(){
    // let formu=document.getElementById('formulario_usuario');
    let formull=document.getElementById('formulario_usuario');
    let datos= $('#formulario_usuario').serialize();


    let usuario=document.getElementById('usuario');
    let pass=document.getElementById('contrasena');
    let confir=document.getElementById('confirm');
    let carnett=document.getElementById('carnett');
    let pregunta=document.getElementById('pregunta');
    let respuesta=document.getElementById('respuesta');
    let preguntaD=document.getElementById('preguntaDos');
    let respuestad=document.getElementById('respuestaDos');

    

    if(usuario.value === ""  || pass.value==="" || carnett.value === ""  || confir.value===""){
       
        mensaje.innerHTML = `<div class="error">
                <p >Ingrese los datos</p></div>`;
                return;
    
    }else if(usuario.value.length <= 2 ){
        
        mensaje.innerHTML = `<div class="error">
                <p >Ingrese un nombre de usuario valido</p></div>`;
                return; 
    }
    else if(pass.value.length <= 6  || confir.value.length <= 6 ){
        
        mensaje.innerHTML = `<div class="error">
                <p >La contraseña debe tener mas de 6 Caracteres</p></div>`;
                return;
    }
    else if(carnett.value.length <= 4  && carnett.value.length <= 8){
   
        mensaje.innerHTML = `<div class="error">
                <p >el carnet debe tener mas de 4 digitos y menos de 8'</p></div>`;
                return;   
    }
    else if(carnett.value  <= 99999){

        mensaje.innerHTML = `<div class="error">
        <p >Carnet Invalido</p></div>`;
        return;   }
        else if(pass.value === ""  || confir.value==="" ){

        mensaje.innerHTML = `<div class="error">
        <p >Debe ingresar y confirmar su contraseña'</p></div>`;
         return;  
    }
    else if(pass.value  != confir.value ){
        
        mensaje.innerHTML = `<div class="error">
        <p >Debe confirar su contraseña'</p></div>`;
        return;  
    }
    if(pregunta.value === ""  || respuestad.value==="" || preguntaD.value === ""  || respuesta.value===""){
        
        mensaje.innerHTML = `<div class="error">
        <p >Debe completar los campos de preguntas de seguridad'</p></div>`;
        return;  
    
    }
    if(pregunta.value ===  preguntaD.value){
       
        mensaje.innerHTML = `<div class="error">
        <p >Debe usar dos preguntas distintas</p></div>`;
        return;
    
    }


    $.ajax({
        url:'../database/usuario.php',
        type:'POST',
        data: datos,

        success:function(vs){
            if(vs == 1){
                
                mensaje.innerHTML = `<div class="exito">
                <p >Registro exitoso</p></div>`;
                
                $(buscaru());
                formull.reset();
                return;
            }
            else if(vs == 2){
                // alert('no se realizo el registro completo...')
                mensaje.innerHTML = `<div class="error">
        <p >no se realizo el registro completo..'</p></div>`;
        return;
            }
            else if(vs == 3){
                mensaje.innerHTML = `<div class="error">
        <p >Ocurrio algo inesperado..'</p></div>`;
        return;
               
            }    else if(vs == 4 ){
              
                mensaje.innerHTML = `<div class="error">
        <p >Carnet Inexistente, proceda a registrar el empleado..'</p></div>`;
        return;

            }
            else if(vs == 5 ){
                
                mensaje.innerHTML = `<div class="error">
                <p >nombre de usuario ya existe en el sistema, intente con uno diferente...'</p></div>`;
                return;
            }

        }


})
})



$('#clientes').click(function(){
    // let formu=document.getElementById('formulario_usuario');
    let clientes=document.getElementById('clientesForm');
    let datos= $('#clientesForm').serialize();


    let nombre=document.getElementById('nombre');
    let apellido=document.getElementById('apellido');
    let cedula=document.getElementById('cedula');
    let genero=document.getElementById('genero');
    // let afiliacion=document.getElementById('afiliacion');


    

    if(nombre.value === ""  || apellido.value==="" || cedula.value === ""  || genero.value===""){
       
        mensaje.innerHTML = `<div class="error">
                <p >Ingrese los datos</p></div>`;
                return;
    
    }else if(nombre.value.length <= 2 ){
        
        mensaje.innerHTML = `<div class="error">
                <p >Ingrese un nombre de valido</p></div>`;
                return; 
    }
    else if(apellido.value.length <= 2 ){
        
        mensaje.innerHTML = `<div class="error">
                <p >Ingrese un apellido de valido</p></div>`;
                return;
    }else if(cedula.value.length <= 6 || cedula.value.length >= 9){
        // alert('Ingrese una cedula Correcta')
        mensaje.innerHTML = `<div class="error">
        <p >Ingrese una cedula correcta</p></div>`;
        return;

}



    $.ajax({
        url:'../database/clientes.php',
        type:'POST',
        data: datos,

        success:function(vs){
            if(vs == 1){
                
                mensaje.innerHTML = `<div class="exito">
                <p >Registro exitoso</p></div>`;
                
                $(buscarcli());
                clientes.reset();
                return;
            }
            else if(vs == 2){
                // alert('no se realizo el registro completo...')
                mensaje.innerHTML = `<div class="error">
        <p >no se realizo el registro completo..'</p></div>`;
        return;
           
            }    else if(vs == 4 ){
              
                mensaje.innerHTML = `<div class="error">
        <p >Numero de afiliado ya existe.'</p></div>`;
        return;

            }
            else if(vs == 5 ){
                
                mensaje.innerHTML = `<div class="error">
                <p >Cedula ya registrada en el sistema.'</p></div>`;
                return;
            }

        }


})
})

















