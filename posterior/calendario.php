<?php include "config.php"; ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Calendario ERP-CRM</title>

<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<style>
#calendar {
    max-width: 900px;
    margin: 40px auto;
}
.modal {
    display: none;
    background: #00000080;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
}
.modal-content {
    background: white;
    width: 400px;
    margin: 10% auto;
    padding: 20px;
    border-radius: 10px;
}
</style>
</head>
<body>

<h2 style="text-align:center;">📅 Calendario ERP-CRM</h2>

<div id="calendar"></div>

<!-- Modal de eventos -->
<div id="modal" class="modal">
    <div class="modal-content">
        <h3>Evento</h3>
        <form id="formEvento">
            <input type="hidden" id="id">
            <label>Título</label>
            <input type="text" id="title" required><br><br>

            <label>Inicio</label>
            <input type="datetime-local" id="start" required><br><br>

            <label>Fin</label>
            <input type="datetime-local" id="end"><br><br>

            <label>Categoría</label>
            <input type="text" id="categoria"><br><br>

            <label>Color</label>
            <input type="color" id="color" value="#3788D8"><br><br>

            <label>Descripción</label>
            <textarea id="descripcion"></textarea><br><br>

            <button type="submit">Guardar</button>
            <button type="button" onclick="borrarEvento()">Eliminar</button>
            <button type="button" onclick="cerrarModal()">Cerrar</button>
        </form>
    </div>
</div>

<script>
let calendar;
let modal = document.getElementById("modal");

function abrirModal() { modal.style.display = "block"; }
function cerrarModal() { modal.style.display = "none"; }

document.addEventListener('DOMContentLoaded', function () {
    let calendarEl = document.getElementById('calendar');

    calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        selectable: true,
        events: "geteventos.php",
        
        dateClick(info) {
            document.getElementById("id").value = "";
            document.getElementById("title").value = "";
            document.getElementById("start").value = info.dateStr + "T00:00";
            document.getElementById("end").value = "";
            document.getElementById("categoria").value = "";
            document.getElementById("color").value = "#3788D8";
            document.getElementById("descripcion").value = "";
            abrirModal();
        },

        eventClick(info) {
            let ev = info.event;

            document.getElementById("id").value = ev.id;
            document.getElementById("title").value = ev.title;
            document.getElementById("start").value = ev.startStr.replace(" ", "T");
            document.getElementById("end").value = ev.end ? ev.endStr.replace(" ", "T") : "";
            document.getElementById("categoria").value = ev.extendedProps.categoria;
            document.getElementById("color").value = ev.backgroundColor;
            document.getElementById("descripcion").value = ev.extendedProps.descripcion;

            abrirModal();
        }
    });

    calendar.render();
});

// Enviar formulario
document.getElementById("formEvento").onsubmit = function(e){
    e.preventDefault();

    let form = new FormData();
    form.append("id", document.getElementById("id").value);
    form.append("title", document.getElementById("title").value);
    form.append("start", document.getElementById("start").value);
    form.append("end", document.getElementById("end").value);
    form.append("categoria", document.getElementById("categoria").value);
    form.append("color", document.getElementById("color").value);
    form.append("descripcion", document.getElementById("descripcion").value);

    let url = document.getElementById("id").value === "" ?
              "addevento.php" : "updateevento.php";

    fetch(url, { method:"POST", body:form })
    .then(r => r.text())
    .then(() => {
        cerrarModal();
        calendar.refetchEvents();
    });
};

function borrarEvento() {
    let id = document.getElementById("id").value;
    if(id === "") return;

    let form = new FormData();
    form.append("id", id);

    fetch("deleteevento.php", { method:"POST", body:form })
    .then(() => {
        cerrarModal();
        calendar.refetchEvents();
    });
}
</script>

</body>
</html>
