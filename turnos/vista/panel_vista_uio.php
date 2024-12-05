<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Turnos</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
<div class="container mx-auto py-8">
    <div class="grid grid-cols-2 gap-8">
        <!-- Columna izquierda para la imagen -->
        <div class="flex items-center justify-center w-full md:max-w-md lg:max-w-lg mx-auto">
    <img src="../img/art1.png" alt="Imagen" class="max-w-full h-auto object-contain">
</div>

        <!-- Columna derecha para las tablas de turnos -->
        <div>
            <h1 class="text-3xl font-bold mb-4">Turnos Asignados</h1>
            <div class="overflow-x-auto mb-8">
                <table id="turnosAsignadosTable" class="table-auto w-full bg-white shadow-md rounded-lg overflow-hidden">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-500">Turno</th>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-500">Nombre</th>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-500">Módulo</th>
                        </tr>
                    </thead>
                    <tbody><!-- Aquí se agregarán los datos de los turnos asignados --></tbody>
                </table>
            </div>
            <h1 class="text-3xl font-bold mb-4">Turnos Pendientes</h1>
            <div class="overflow-x-auto">
                <table id="turnosPendientesTable" class="table-auto w-full bg-white shadow-md rounded-lg overflow-hidden">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-500">Turno</th>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-500">Nombre</th>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-500">Estado</th>
                        </tr>
                    </thead>
                    <tbody><!-- Aquí se agregarán los datos de los turnos pendientes --></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    
    // Función para cargar los turnos
function cargarTurnos() {
    fetch(`../php/obtener_turnos.php?ciudad=Quito`)
        .then(response => response.json())
        .then(data => {
                const turnosAsignadosTable = document.getElementById('turnosAsignadosTable').querySelector('tbody');
                const turnosPendientesTable = document.getElementById('turnosPendientesTable').querySelector('tbody');

                turnosAsignadosTable.innerHTML = ''; 
                turnosPendientesTable.innerHTML = ''; 

                data.asignados.forEach(turno => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="px-4 py-2 whitespace-nowrap">${turno.tu_nro_tur}</td>
                        <td class="px-4 py-2 whitespace-nowrap">${turno.tu_nombres}</td>
                        <td class="px-4 py-2 whitespace-nowrap">${turno.as_caja}</td>
                    `;
                    turnosAsignadosTable.appendChild(row);
                });

                data.pendientes.forEach(turno => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td class="px-4 py-2 whitespace-nowrap">${turno.tu_nro_tur}</td>
                        <td class="px-4 py-2 whitespace-nowrap">${turno.tu_nombres}</td>
                        <td class="px-4 py-2 whitespace-nowrap">
                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                        Pendiente
                        </span>
                        </td>

                    `;
                    turnosPendientesTable.appendChild(row);
                });
            })
            .catch(error => {
                console.error('Error al cargar los turnos:', error);
            });
    }

    // Cargar los turnos al cargar la página
    cargarTurnos();

    // Actualizar los turnos cada 5 segundos
    setInterval(cargarTurnos, 5000);
</script>
</body>
</html>
