/*
 * Código compartido por Liquidar.php, LiquidarEspecialidad.php y LiquidarUsuario.php.
 *
 *  - LiquidacionDatos: lectura tolerante del DataJSON guardado en la tabla `liquidaciones`.
 *    Entiende los dos formatos históricos (individual anidado y especialidad completa plano)
 *    y el formato actual, y devuelve los valores tal como se vieron en la tabla de datos actuales.
 *  - generarPDFLiquidacion(): genera el PDF a partir de la tabla #tblDatos. La tabla de datos
 *    actuales y la del histórico tienen las mismas columnas, así que el PDF sale igual en ambos casos.
 *
 * Requiere jQuery, DataTables, jsPDF y jspdf-autotable cargados antes que este archivo.
 */
(function (global) {
    'use strict';

    // ------------------------------------------------------------------
    // Lectura de valores guardados
    // ------------------------------------------------------------------

    // Convierte "$ 1.234,56", "1234.56", "1234,56" o un número a number. NaN si no hay valor.
    function numero(valor) {
        if (typeof valor === 'number') return isFinite(valor) ? valor : NaN;
        if (valor === null || valor === undefined) return NaN;

        let s = String(valor).replace(/[$\s]/g, '');
        if (s === '' || s === '-') return NaN;

        const coma = s.lastIndexOf(',');
        const punto = s.lastIndexOf('.');
        if (coma >= 0 && punto >= 0) {
            // El separador decimal es el que aparece al final
            s = coma > punto ? s.replace(/\./g, '').replace(',', '.') : s.replace(/,/g, '');
        } else if (coma >= 0) {
            s = s.replace(',', '.');
        }

        const n = parseFloat(s);
        return isNaN(n) ? NaN : n;
    }

    // Formato de moneda de la tabla ("$ 1.234,00"). Acepta varios valores separados por "/"
    // (varias actividades en la misma celda) y devuelve "-" cuando no hay un valor numérico.
    function moneda(valor) {
        if (valor === null || valor === undefined || valor === '') return '-';
        if (typeof valor === 'string' && valor.indexOf('/') !== -1) {
            return valor.split('/').map(function (parte) { return moneda(parte.trim()); }).join(' / ');
        }
        const n = numero(valor);
        if (isNaN(n)) return '-';
        return '$ ' + n.toLocaleString('es-CO', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Suma valores guardados que pueden venir como "a / b" (varias actividades en la misma celda).
    function suma(valor) {
        if (valor === null || valor === undefined || valor === '') return 0;
        return String(valor).split('/').reduce(function (total, parte) {
            const n = numero(parte);
            return total + (isNaN(n) ? 0 : n);
        }, 0);
    }

    // Honorario base como número: los registros de especialidad completa guardaron "0" (texto),
    // que en JavaScript es verdadero y hacía creer que el médico tenía honorario base.
    function honorarioBase(datos) {
        const n = numero((datos || {}).honorarioBase);
        return n > 0 ? n : 0;
    }

    // Un indicador (Interconsultas, GPC o DHC) tal como se mostró en la tabla:
    //  promedio  -> "1.59" ó "-" cuando no había dato
    //  descuento -> "1%", "0%", "-1%" ó "-" tal cual se guardó
    //  ajuste    -> valor del ajuste en pesos
    //  valor     -> promedio numérico (0 si no había dato)
    function indicador(datos, anidado, plano, descuentoVacio) {
        const sub = datos[anidado] || {};
        const textos = datos.textos || {};

        const valor = numero(datos['prom' + plano] !== undefined ? datos['prom' + plano] : sub.promedio);
        const texto = textos['prom' + plano + 'Texto'];
        const promedio = (typeof texto === 'string' && texto.trim() !== '')
            ? texto.trim()
            : (valor > 0 ? valor.toFixed(2) : '-');

        let descuento = datos['desc' + plano];
        if (descuento === undefined || descuento === null) descuento = sub.descuento;
        if (descuento === undefined || descuento === null) descuento = descuentoVacio;

        const ajuste = numero(datos['ajuste' + plano] !== undefined ? datos['ajuste' + plano] : sub.ajuste);

        return {
            promedio: promedio,
            valor: isNaN(valor) ? 0 : valor,
            descuento: String(descuento),
            ajuste: isNaN(ajuste) ? 0 : ajuste
        };
    }

    function indicadores(datos) {
        datos = datos || {};
        return {
            interconsultas: indicador(datos, 'interconsultas', 'Interconsultas', '-'),
            gpc: indicador(datos, 'gpc', 'GPC', '0%'),
            dhc: indicador(datos, 'dhc', 'DHC', '-')
        };
    }

    global.LiquidacionDatos = {
        numero: numero,
        moneda: moneda,
        suma: suma,
        honorarioBase: honorarioBase,
        indicadores: indicadores
    };

    // ------------------------------------------------------------------
    // PDF
    // ------------------------------------------------------------------

    async function generarPDFLiquidacion() {
        const $ = global.jQuery;

        // Mostrar spinner mientras se genera el PDF
        $('#spinner-overlay').show();
        $('#loading-spinner').show();

        try {
            const { jsPDF } = global.jspdf;
            const meses = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
                           'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

            // Obtener datos del formulario
            const mes = $('#mes').val();
            const anio = $('#anio').val();
            const nombreMes = mes ? meses[parseInt(mes) - 1] : 'Todos los meses';

            const doc = new jsPDF({
                orientation: 'portrait',
                unit: 'mm',
                format: 'a4',
                margins: { bottom: 5 }
            });

            // Configuración de márgenes y diseño
            const marginLeft = 15;
            const marginRight = 15;
            const pageWidth = doc.internal.pageSize.getWidth() - marginLeft - marginRight;
            const lineHeight = 5;
            let currentY = 4;

            // Nombre fijo de quien firma por la Subgerencia Científica
            const NOMBRE_SUBGERENCIA = 'KARIN MILENA GAMBA FERRUCHO';

            const styles = {
                title:     { fontSize: 16, fontStyle: 'bold',   textColor: [2, 165, 103] },
                subtitle:  { fontSize: 12, fontStyle: 'normal', textColor: [100, 100, 100] },
                header:    { fontSize: 10, fontStyle: 'bold',   textColor: [0, 0, 0] },
                normal:    { fontSize: 10, fontStyle: 'normal', textColor: [0, 0, 0] },
                small:     { fontSize:  8, fontStyle: 'normal', textColor: [100, 100, 100] },
                highlight: { fontSize: 10, fontStyle: 'bold',   textColor: [2, 165, 103] },
                footer:    { fontSize:  8, fontStyle: 'normal', textColor: [100, 100, 100] }
            };

            // Función para agregar texto con estilo
            const addText = (text, x, y, style, align = 'left') => {
                doc.setFontSize(style.fontSize);
                doc.setFont('helvetica', style.fontStyle);
                doc.setTextColor(style.textColor[0], style.textColor[1], style.textColor[2]);
                doc.text(text, x, y, { align: align });
            };

            // Función para convertir URL a Base64 (para el logo)
            const getBase64FromUrl = async (url) => {
                const response = await fetch(url);
                const blob = await response.blob();
                return new Promise((resolve) => {
                    const reader = new FileReader();
                    reader.readAsDataURL(blob);
                    reader.onloadend = () => resolve(reader.result);
                });
            };

            // Precargar el logo del hospital UNA sola vez
            let logoData = null;
            try {
                logoData = await getBase64FromUrl('../Assets/ICON/hrm-black.png');
            } catch (error) {
                console.warn('No se pudo cargar el logo:', error);
            }

            // Encabezado del documento (logo + título + período + fecha + separador)
            const generarEncabezadoDocumento = () => {
                if (logoData) {
                    doc.addImage(logoData, 'PNG', marginLeft, currentY, 30, 25);
                }

                currentY += 10;
                addText('LIQUIDACIÓN DE HONORARIOS MÉDICOS', marginLeft + (pageWidth / 2), currentY, styles.title, 'center');

                currentY += lineHeight;
                addText(`Período: ${nombreMes} ${anio}`, marginLeft + (pageWidth / 2), currentY, styles.subtitle, 'center');
                currentY += lineHeight * 2;
                addText(`Generado el: ${new Date().toLocaleDateString('es-ES', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                })}`, marginLeft + pageWidth, currentY, styles.small, 'right');

                doc.setDrawColor(200, 200, 200);
                doc.line(marginLeft, currentY, marginLeft + pageWidth, currentY);
            };

            // Obtener datos de la tabla DataTable
            const tabla = $('#tblDatos').DataTable();
            const datos = tabla.data().toArray();

            // El desplazamiento de columnas depende de la TABLA (no del médico): si algún médico tiene
            // honorario base, la tabla completa trae la columna "Honorario Base" (índice 27) y todas las
            // columnas posteriores se corren una posición para todas las filas por igual.
            const tablaConHonorarioBase = $('#tblDatos thead th').filter(function () {
                return $(this).text().trim() === 'Honorario Base';
            }).length > 0;
            const offset = tablaConHonorarioBase ? 1 : 0;

            const formatCurrencyPDF = (value) => moneda(value);

            // Procesar datos para el PDF
            const medicos = {};
            const areasPorMedico = {};

            datos.forEach(row => {
                if (!row[0]) return;

                const nombreMedico = row[0];
                const area = row[2];

                if (!medicos[nombreMedico]) {
                    medicos[nombreMedico] = { areas: {}, honorarioBase: 0 };
                    areasPorMedico[nombreMedico] = new Set();
                }

                // El Honorario Base solo se pinta en la primera fila del médico
                if (tablaConHonorarioBase && !medicos[nombreMedico].honorarioBase) {
                    const hb = numero(row[27]);
                    if (hb > 0) medicos[nombreMedico].honorarioBase = hb;
                }

                areasPorMedico[nombreMedico].add(area);

                medicos[nombreMedico].areas[area] = {
                    especialidad: row[1],
                    turnos: {
                        valorHora: formatCurrencyPDF(row[3]),
                        cantidad: row[4] || '-',
                        horas: row[5] || '-',
                        total: formatCurrencyPDF(row[6])
                    },
                    // Disponibilidad, Consulta Externa, Jornada QX y Total Turnos van ANTES de la
                    // columna Honorario Base, por eso no llevan offset
                    disponibilidad: {
                        valorHora: formatCurrencyPDF(row[8]),
                        cantidad: row[9] || '-',
                        horas: row[10] || '-',
                        total: formatCurrencyPDF(row[11]),
                        horasEfectivas: row[12] || '-',
                        valorHoraEfectiva: formatCurrencyPDF(row[13]),
                        totalEfectivo: formatCurrencyPDF(row[14])
                    },
                    consultaExterna: {
                        valorHora: formatCurrencyPDF(row[16]),
                        cantidad: row[17] || '-',
                        horas: row[18] || '-',
                        total: formatCurrencyPDF(row[19])
                    },
                    jornadaQX: {
                        valorHora: formatCurrencyPDF(row[21]),
                        cantidad: row[22] || '-',
                        horas: row[23] || '-',
                        total: formatCurrencyPDF(row[24])
                    },
                    interconsultas: {
                        promedio: row[27 + offset] || '-',
                        descuento: row[28 + offset] || '-',
                        ajuste: formatCurrencyPDF(row[29 + offset])
                    },
                    gpc: {
                        promedio: row[31 + offset] || '-',
                        descuento: row[32 + offset] || '-',
                        ajuste: formatCurrencyPDF(row[33 + offset])
                    },
                    dhc: {
                        promedio: row[35 + offset] || '-',
                        descuento: row[36 + offset] || '-',
                        ajuste: formatCurrencyPDF(row[37 + offset])
                    },
                    administrativo: {
                        valorHora: formatCurrencyPDF(row[40 + offset]),
                        registros: row[41 + offset] || '-',
                        horas: row[42 + offset] || '-',
                        total: formatCurrencyPDF(row[43 + offset])
                    },
                    totalActividad: formatCurrencyPDF(row[26]),
                    totalActividadDesc: formatCurrencyPDF(
                        (numero(row[29 + offset]) || 0) +
                        (numero(row[33 + offset]) || 0) +
                        (numero(row[37 + offset]) || 0)
                    ),
                    honorarios: formatCurrencyPDF(row[45 + offset]),
                    totalLiquidar: formatCurrencyPDF(row[47 + offset])
                };
            });

            const checkPageBreak = (neededSpace) => {
                const pageHeight = doc.internal.pageSize.getHeight();
                const marginBottom = 2;
                if (currentY + neededSpace > pageHeight - marginBottom) {
                    doc.addPage();
                    currentY = 15;
                    return true;
                }
                return false;
            };

            // Encabezado individual del médico
            const generarHeaderMedico = (medico, nombreMedico) => {
                const primeraArea = Object.values(medico.areas)[0];

                currentY += 5;
                addText(`Médico: ${nombreMedico}`, marginLeft, currentY, styles.header);
                addText(`Especialidad: ${primeraArea.especialidad}`, marginLeft + 100, currentY, styles.normal);

                currentY += 5;
                addText(
                    medico.honorarioBase > 0 ? 'Tipo: CONTRATO CON HONORARIO BASE' : 'Tipo: CONTRATO POR ACTIVIDAD',
                    marginLeft, currentY, styles.highlight
                );
            };

            // Un concepto sin actividad (cantidad/horas en 0, '0.00', '-' o vacío) no se imprime
            const esVacio = (valor) => {
                if (valor === null || valor === undefined) return true;
                const str = String(valor).trim();
                if (str === '' || str === '-') return true;
                const n = Number(str.replace(',', '.'));
                return !isNaN(n) && n === 0;
            };

            const generarTablasTurnos = (medicoObj, nombreMedico) => {
                const areas = Array.from(areasPorMedico[nombreMedico]);

                currentY += lineHeight * 0.7;
                addText('AGENDAS POR ÁREA', marginLeft, currentY, styles.highlight);
                currentY += lineHeight;

                const body = [];

                areas.forEach(area => {
                    const areaData = medicoObj.areas[area];

                    body.push([
                        { content: area, styles: { fontStyle: 'bold', textColor: [50, 50, 150] } },
                        '', '', '', ''
                    ]);

                    if (!esVacio(areaData.turnos.cantidad)) {
                        body.push([
                            'Agenda regular',
                            areaData.turnos.valorHora,
                            areaData.turnos.cantidad,
                            areaData.turnos.horas,
                            areaData.turnos.total
                        ]);
                    }

                    if (!esVacio(areaData.disponibilidad.cantidad)) {
                        body.push([
                            'Disponibilidad',
                            areaData.disponibilidad.valorHora,
                            areaData.disponibilidad.cantidad,
                            areaData.disponibilidad.horas,
                            areaData.disponibilidad.total
                        ]);
                    }

                    if (!esVacio(areaData.disponibilidad.horasEfectivas)) {
                        body.push([
                            'Horas efectivas',
                            areaData.disponibilidad.valorHoraEfectiva,
                            '-',
                            areaData.disponibilidad.horasEfectivas,
                            areaData.disponibilidad.totalEfectivo
                        ]);
                    }

                    if (!esVacio(areaData.consultaExterna.cantidad)) {
                        body.push([
                            'Consulta externa',
                            areaData.consultaExterna.valorHora,
                            areaData.consultaExterna.cantidad,
                            areaData.consultaExterna.horas,
                            areaData.consultaExterna.total
                        ]);
                    }

                    if (!esVacio(areaData.jornadaQX.cantidad)) {
                        body.push([
                            'Jornada QX',
                            areaData.jornadaQX.valorHora,
                            areaData.jornadaQX.cantidad,
                            areaData.jornadaQX.horas,
                            areaData.jornadaQX.total
                        ]);
                    }

                    if (areas.indexOf(area) < areas.length - 1) {
                        body.push([
                            { content: '──────────', colSpan: 5, styles: { textColor: [200, 200, 200] } },
                            '', '', '', ''
                        ]);
                    }
                });

                doc.autoTable({
                    startY: currentY,
                    head: [['Concepto', 'Valor Hora', 'Cantidad', 'Horas', 'Total']],
                    body: body,
                    margin: { left: marginLeft, right: marginRight },
                    styles: {
                        fontSize: 8,
                        cellPadding: 2,
                        overflow: 'linebreak'
                    },
                    headStyles: {
                        fillColor: [240, 240, 240],
                        textColor: [0, 0, 0],
                        fontStyle: 'bold'
                    }
                });

                currentY = doc.lastAutoTable.finalY + 5;
            };

            // Resumen del médico. Los indicadores, lo administrativo y los honorarios son valores a nivel
            // médico: la tabla los pinta solo en la primera fila de área.
            const generarResumenGeneral = (medicoObj) => {
                const primeraArea = Object.values(medicoObj.areas)[0];
                const tieneHonorarioBase = medicoObj.honorarioBase > 0;

                if (!tieneHonorarioBase) {
                    addText('AJUSTES POR INDICADORES', marginLeft, currentY, styles.highlight);
                    currentY += lineHeight;

                    doc.autoTable({
                        head: [['Indicador', 'Promedio', 'Descuento/Bonif.', 'Ajuste']],
                        body: [
                            ['Interconsultas', primeraArea.interconsultas.promedio, primeraArea.interconsultas.descuento, primeraArea.interconsultas.ajuste],
                            ['GPC', primeraArea.gpc.promedio, primeraArea.gpc.descuento, primeraArea.gpc.ajuste],
                            ['DHC', primeraArea.dhc.promedio, primeraArea.dhc.descuento, primeraArea.dhc.ajuste]
                        ],
                        margin: { left: marginLeft, right: marginRight },
                        styles: { fontSize: 8, cellPadding: 2, textColor: [0, 0, 0] },
                        headStyles: { fillColor: [240, 240, 240], textColor: [0, 0, 0], fontStyle: 'bold' },
                        columnStyles: {
                            0: { cellWidth: 40 },
                            1: { cellWidth: 25 },
                            2: { cellWidth: 30 },
                            3: { cellWidth: 25 }
                        }
                    });

                    currentY = doc.lastAutoTable.finalY + 5;

                    addText('ACTIVIDADES ADMINISTRATIVAS', marginLeft, currentY, styles.highlight);
                    currentY += lineHeight;

                    doc.autoTable({
                        head: [['Concepto', 'Valor Hora', 'Registros', 'Horas', 'Total']],
                        body: [
                            ['Administrativo',
                             primeraArea.administrativo.valorHora,
                             primeraArea.administrativo.registros,
                             primeraArea.administrativo.horas,
                             primeraArea.administrativo.total]
                        ],
                        margin: { left: marginLeft, right: marginRight },
                        styles: { fontSize: 8, cellPadding: 2, textColor: [0, 0, 0] },
                        headStyles: { fillColor: [240, 240, 240], textColor: [0, 0, 0], fontStyle: 'bold' },
                        columnStyles: {
                            0: { cellWidth: 40 },
                            1: { cellWidth: 25 },
                            2: { cellWidth: 20 },
                            3: { cellWidth: 20 },
                            4: { cellWidth: 25 }
                        }
                    });

                    currentY = doc.lastAutoTable.finalY + 5;

                    addText('HONORARIOS QUIRÚRGICOS', marginLeft, currentY, styles.highlight);
                    currentY += lineHeight;

                    doc.autoTable({
                        head: [['Concepto', 'Valor']],
                        body: [
                            ['Honorarios por cirugías', primeraArea.honorarios]
                        ],
                        margin: { left: marginLeft, right: marginRight },
                        styles: { fontSize: 8, cellPadding: 2, textColor: [0, 0, 0] },
                        headStyles: { fillColor: [240, 240, 240], textColor: [0, 0, 0], fontStyle: 'bold' },
                        columnStyles: {
                            0: { cellWidth: 60 },
                            1: { cellWidth: 50 }
                        }
                    });

                    currentY = doc.lastAutoTable.finalY + 5;
                }

                addText('RESUMEN FINAL', marginLeft, currentY, styles.highlight);
                currentY += lineHeight;

                let body = [];
                if (tieneHonorarioBase) {
                    body = [
                        ['Honorario Base', formatCurrencyPDF(medicoObj.honorarioBase)],
                        ['TOTAL A LIQUIDAR', formatCurrencyPDF(medicoObj.honorarioBase)]
                    ];
                } else {
                    body = [
                        ['Total por agendas y actividades', primeraArea.totalActividad],
                        ['Total descuento/bonificación', primeraArea.totalActividadDesc],
                        ['Total actividades administrativas', primeraArea.administrativo.total],
                        ['Total honorarios quirúrgicos', primeraArea.honorarios],
                        ['TOTAL A LIQUIDAR', primeraArea.totalLiquidar]
                    ];
                }

                doc.autoTable({
                    head: [['Concepto', 'Valor']],
                    body: body,
                    margin: { left: marginLeft, right: marginRight },
                    styles: { fontSize: 8, cellPadding: 2, textColor: [0, 0, 0] },
                    headStyles: { fillColor: [240, 240, 240], textColor: [0, 0, 0], fontStyle: 'bold' },
                    bodyStyles: { fontStyle: 'bold' },
                    columnStyles: {
                        0: { cellWidth: 60 },
                        1: { cellWidth: 50 }
                    }
                });

                currentY = doc.lastAutoTable.finalY + 8;
            };

            const generarFirmasMedico = (nombreMedico) => {
                checkPageBreak(35);

                doc.setDrawColor(200, 200, 200);
                currentY += 5;

                // Firma izquierda — médico
                addText('_________________________________________',
                       marginLeft + (pageWidth / 4), currentY + 10, styles.normal, 'center');
                addText(nombreMedico,
                       marginLeft + (pageWidth / 4), currentY + 15, styles.small, 'center');
                addText('Médico',
                       marginLeft + (pageWidth / 4), currentY + 20, styles.small, 'center');

                // Firma derecha — subgerencia
                addText('_________________________________________',
                       marginLeft + (pageWidth * 3 / 4), currentY + 10, styles.normal, 'center');
                addText(NOMBRE_SUBGERENCIA,
                       marginLeft + (pageWidth * 3 / 4), currentY + 15, styles.small, 'center');
                addText('Subgerencia Científica',
                       marginLeft + (pageWidth * 3 / 4), currentY + 20, styles.small, 'center');

                currentY += 1;
            };

            // Generar contenido para cada médico
            Object.keys(medicos).forEach((nombreMedico, index) => {
                const medico = medicos[nombreMedico];

                if (index > 0) {
                    doc.addPage();
                    currentY = 5;
                }

                generarEncabezadoDocumento();
                generarHeaderMedico(medico, nombreMedico);
                generarTablasTurnos(medico, nombreMedico);
                generarResumenGeneral(medico);
                generarFirmasMedico(nombreMedico);
            });

            // Pie de página institucional (última página del documento)
            checkPageBreak(15);
            currentY += 27;
            addText('Hospital Regional de Moniquira E.S.E',
                   marginLeft + (pageWidth / 2), currentY, styles.footer, 'center');

            doc.save(`Liquidacion_Honorarios_${nombreMes}_${anio}.pdf`);

        } catch (error) {
            console.error('Error al generar PDF:', error);
            alert('Ocurrió un error al generar el PDF: ' + error.message);
        } finally {
            $('#spinner-overlay').hide();
            $('#loading-spinner').hide();
        }
    }

    global.generarPDFLiquidacion = generarPDFLiquidacion;
})(window);
