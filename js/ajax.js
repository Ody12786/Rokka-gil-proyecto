

console.log('hola')

let btn= document.getElementById('avanzar')

btn.addEventListener('click',e=>{
    console.log('diste click')
        let datar= $('#formulario').serialize();

        datar

        $.ajax({
            url:'registro/persona.php',
            type:'POST',
            data: datar,

            success:function(vs){

                if(vs == 1){
                    alert('listo')
                    return;
                }
                if(vs == 2){
                    alert('nada')
                    return;
                }
            }
        
        })

})

