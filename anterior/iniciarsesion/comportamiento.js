document.addEventListener('DOMContentLoaded', function() {
    document.querySelector("button").addEventListener('click', function() {
        let usuario = document.querySelector("#usuario").value;
        let contrasena = document.querySelector("#contrasena").value; 
        
        if (!usuario || !contrasena) {
            document.querySelector("#estado").textContent = "Por favor, complete todos los campos";
            return;
        }

        const loginData = {
            "usuario": usuario,
            "contrasena": contrasena,
        };

        console.log('Enviando datos de login:', loginData);

        fetch('../../posterior/iniciarsesion.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(loginData)
        })
        .then(response => {
            console.log('Status HTTP:', response.status);
            
            if (!response.ok) {
                throw new Error('Error HTTP: ' + response.status);
            }
            
            return response.text().then(text => {
                console.log('Respuesta cruda del servidor:', text);
                
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('No es JSON válido:', text);
                    throw new Error('Respuesta del servidor inválida');
                }
            });
        })
        .then(data => {
            console.log('Datos parseados:', data);
            if (data.success) {
                window.location.href = "../";
            } else { 
                document.querySelector("#estado").textContent = "Error: " + (data.message || 'Error desconocido');
            }
        })
        .catch(error => {
            console.error('Error completo:', error);
            document.querySelector("#estado").textContent = "Error de conexión: " + error.message;
        });
    });

    // Permitir login con Enter
    document.querySelector("#contrasena").addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            document.querySelector("button").click();
        }
    });
});