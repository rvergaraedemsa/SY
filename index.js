// 📊 DATATABLE
if (document.getElementById("tablaDatos")) {
    new simpleDatatables.DataTable("#tablaDatos");
}
let chart = null;

console.log("🔍 Datos cargados en tabla:", tablaData);
if (tablaData && tablaData.length > 0) {
    console.log("📌 Primeros 5 registros:");
    tablaData.slice(0, 5).forEach((row, i) => {
        console.log(`  [${i}] Medidor: "${row.Medidor}" | Tipo: "${row.Tipo}" | Fecha: "${row.Fecha}" | Valor: ${row.Valor}`);
    });
}

// 🔥 cargar filtros desde tabla
function cargarFiltros() {

    let medidores = new Set();
    let tipos = new Set();

    tablaData.forEach(row => {
        medidores.add(row.Medidor.trim());
        tipos.add(row.Tipo.trim());
    });

    let selM = document.getElementById("filtroMedidor");
    let selT = document.getElementById("filtroTipo");

    // selM.innerHTML = '<option value="">Todos los medidores</option>';
    // selT.innerHTML = '<option value="">Todos los tipos</option>';
    selM.innerHTML = '';
    selT.innerHTML = '';

    medidores.forEach(m => {
        selM.innerHTML += `<option value="${m}">${m}</option>`;
    });

    tipos.forEach(t => {
        selT.innerHTML += `<option value="${t}">${t}</option>`;
    });
}

// 📈 gráfico dinámico
function generarGrafico() {

    let filtroM = selectMedidor ? selectMedidor.getValue() : "";
    let filtroT = selectTipo ? selectTipo.getValue() : [];

    // 🔥 asegurar array
    if (!Array.isArray(filtroT)) {
        filtroT = filtroT ? [filtroT] : [];
    }

    // 🔥 normalizar tipos y filtrar vacíos
    let filtroTipos = filtroT
        .map(t => t.trim().toLowerCase())
        .filter(t => t !== "");

    console.log("🔍 Filtros aplicados - Medidor:", filtroM, "| Tipos:", filtroTipos);

    let datasets = {};
    let contadorRegistros = 0;

    tablaData.forEach(row => {

        let medidor = row.Medidor.trim();
        let tipo = row.Tipo.trim();
        let tipoNormalizado = tipo.toLowerCase();
        let fecha = row.Fecha;
        let valor = parseFloat(row.Valor);
        
        // 🔥 validar valor numérico
        if (isNaN(valor)) {
            return;
        }

        // 🔥 validar fecha
        if (!fecha) return;
        let fechaTime = new Date(fecha).getTime();
        if (isNaN(fechaTime)) {
            console.warn("⚠️ Fecha inválida:", fecha, "| Medidor:", medidor, "| Tipo:", tipo);
            return;
        }

        // 🔥 filtros
        if (filtroM !== "" && medidor !== filtroM) return;
        if (filtroTipos.length && !filtroTipos.includes(tipoNormalizado)) return;

        contadorRegistros++;
        let key = medidor + " | " + tipo;

        if (!datasets[key]) {
            datasets[key] = {
                label: key,
                data: [],
                borderColor: `hsl(${Math.random() * 360},70%,50%)`,
                backgroundColor: "transparent",
                tension: 0.3
            };
        }

        datasets[key].data.push({
            x: fechaTime,
            y: valor
        });
    });

    console.log("✅ Registros después de filtros:", contadorRegistros, "| Series creadas:", Object.keys(datasets).length);
    
    let finalData = Object.values(datasets);

    // 🔥 ordenar datos por fecha
    finalData.forEach(ds => {
        ds.data.sort((a, b) => a.x - b.x);
    });

    if (finalData.length === 0) {
        alert("No hay datos para ese filtro");
        return;
    }

    if (chart) {
        chart.destroy();
    }

    // 🔥 calcular min y max de fechas de los datos
    let allDates = [];
    let allValues = [];
    finalData.forEach(ds => {
        ds.data.forEach(point => {
            if (!isNaN(point.x) && !isNaN(point.y)) {
                allDates.push(point.x);
                allValues.push(point.y);
            }
        });
    });

    // 🔥 validar que hay datos válidos
    if (allDates.length === 0 || allValues.length === 0) {
        console.error("No hay datos válidos para graficar");
        alert("Error: No hay datos válidos con esas fechas");
        return;
    }

    let minX = Math.min(...allDates);
    let maxX = Math.max(...allDates);
    let minY = Math.min(...allValues);
    let maxY = Math.max(...allValues);
    
    // 🔥 agregar margen al eje Y - si minY == maxY, usar margen mínimo
    let margenY;
    if (minY === maxY) {
        // Si los valores son iguales, agregar margen fijo
        if (minY === 0) {
            margenY = 1; // Al menos ±1
        } else {
            margenY = Math.abs(minY) * 0.5; // 50% del valor
        }
    } else {
        margenY = (maxY - minY) * 0.1; // 10% del rango
    }
    minY = minY - margenY;
    maxY = maxY + margenY;
    
    // 🔥 agregar margen al eje X - si minX == maxX, agregar margen temporal
    let margenX = 0;
    if (minX === maxX) {
        margenX = 60 * 60 * 1000; // 1 hora en milisegundos
        minX = minX - margenX;
        maxX = maxX + margenX;
    }
    
    console.log("Gráfico - Datos:", finalData.length, "series | Puntos:", allDates.length, "| MinX:", new Date(minX).toLocaleString(), "| MaxX:", new Date(maxX).toLocaleString(), "| MinY:", minY.toFixed(2), "| MaxY:", maxY.toFixed(2));
    
    // 🔥 mostrar detalle de cada serie
    finalData.forEach((ds, idx) => {
        console.log(`  Serie ${idx + 1}: ${ds.label} - ${ds.data.length} puntos`);
    });

    chart = new Chart(document.getElementById("grafico"), {
        type: "line",
        data: {
            datasets: finalData
        },
        options: {
            parsing: false,
            responsive: true,
            plugins: {
                legend: {
                    display: true
                }
            },
            scales: {
                y: {
                    beginAtZero: false,
                    min: minY,
                    max: maxY
                },
                x: {
                    type: "linear",
                    min: minX,
                    max: maxX,
                    ticks: {
                        callback: function (value) {
                            let d = new Date(value);
                            return d.toLocaleString("es-AR");
                        }
                    }
                }
            }
        }
    });
}

let selectTipo = null;
let selectMedidor = null;

function activarBuscadores() {
    if (selectTipo) {
        selectTipo.destroy();
    }
    selectMedidor = new TomSelect("#filtroMedidor", {
        create: false,
        sortField: { field: "text", direction: "asc" },
        placeholder: "🔍 Buscar medidor...",
        plugins: ['remove_button'],
        allowEmptyOption: true
    });

    selectTipo = new TomSelect("#filtroTipo", {
        maxItems: null,
        create: false,
        sortField: { field: "text", direction: "asc" },
        placeholder: "🔍 Buscar tipo de medición...",
        plugins: ['remove_button'],
        allowEmptyOption: true,
    });

    // 🔥 regenerar gráfico cuando cambian los filtros
    selectMedidor.on("change", generarGrafico);
    selectTipo.on("change", generarGrafico);
    // new TomSelect("#filtroMedidor", {
    //     create: false,
    //     sortField: { field: "text", direction: "asc" },
    //     placeholder: "🔍 Buscar medidor...",
    //     allowEmptyOption: true
    // });

    // new TomSelect("#filtroTipo", {
    //     maxItems: null,
    //     create: false,
    //     sortField: { field: "text", direction: "asc" },
    //     placeholder: "🔍 Buscar tipo de medición...",
    //     allowEmptyOption: true,
    // });
}


// init
cargarFiltros();
// ejecutar después de cargar filtros
setTimeout(() => {
    activarBuscadores();
    generarGrafico();
}, 300);

