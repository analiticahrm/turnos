<?php
class Cirugia{
    private $_conexion;
    private $_IdCirugia;
    private $_FechaCirugia;
    private $_FechaFactura;
    private $_OIDCirugia;
    private $_Documento;
    private $_Nombre;
    private $_EPS;
    private $_Liquidar;
    private $_OIDEPS;
    private $_Procedimiento;
    private $_OIDProcedimiento;
    private $_GrupoQX;
    private $_ClaseQxProg;
    private $_Estado;
    private $_Ingreso;
    private $_ValorFacturacion;
    private $_Medico;
    private $_DocumentoMedico;
    private $_Anestesiologo;
    private $_DocumentoMedicoAnes;
    private $_Especialidad;

    function __construct($_conexion, $_IdCirugia, $_FechaCirugia, $_FechaFactura, $_OIDCirugia, $_Documento, $_Nombre, $_EPS, $_Liquidar, $_OIDEPS, $_Procedimiento, $_OIDProcedimiento, $_GrupoQX, $_ClaseQxProg, $_Estado, $_Ingreso, $_ValorFacturacion, $_Medico, $_DocumentoMedico, $_Anestesiologo, $_DocumentoMedicoAnes, $_Especialidad){
        $this->_conexion = $_conexion;
        $this->_IdCirugia = $_IdCirugia;
        $this->_FechaCirugia = $_FechaCirugia;
        $this->_FechaFactura = $_FechaFactura;
        $this->_OIDCirugia = $_OIDCirugia;
        $this->_Documento = $_Documento;
        $this->_Nombre = $_Nombre;
        $this->_EPS = $_EPS;
        $this->_Liquidar = $_Liquidar;
        $this->_OIDEPS = $_OIDEPS;
        $this->_Procedimiento = $_Procedimiento;
        $this->_OIDProcedimiento = $_OIDProcedimiento;
        $this->_GrupoQX= $_GrupoQX;
        $this->_ClaseQxProg= $_ClaseQxProg;
        $this->_Estado= $_Estado;
        $this->_Ingreso= $_Ingreso;
        $this->_ValorFacturacion= $_ValorFacturacion;
        $this->_Medico= $_Medico;
        $this->_DocumentoMedico= $_DocumentoMedico;
        $this->_Anestesiologo= $_Anestesiologo;
        $this->_DocumentoMedicoAnes= $_DocumentoMedicoAnes;
        $this->_Especialidad= $_Especialidad;
    }
    
    function _get($k)
    {
        return $this->$k;
    }
    
    function _set($k, $v)
    {
        $this->$k = $v;
    }

    /**
     * Calcula honorarios para un médico en un ingreso específico
     */
    function calcularHonorariosPorIngreso($ingreso, $tipoMedico = 'cirujano') {
        // Obtener todas las cirugías para este ingreso usando FechaFactura
        $sql = "SELECT * FROM cirugias WHERE Ingreso = ? ";
        $stmt = $this->_conexion->prepare($sql);
        $stmt->execute([$ingreso]);
        $cirugias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($cirugias)) return 0;

        // Obtener datos del salario mínimo actual
        $salarioMinimo = $this->obtenerSalarioMinimoActual();
        $valorDia = $salarioMinimo['GSAVALSAL'] / 30;
        
        // Ordenar cirugías por GrupoQX descendente
        usort($cirugias, function($a, $b) {
            return $b['GrupoQX'] - $a['GrupoQX'];
        });
        
        $total = 0;
        
        foreach ($cirugias as $index => $cirugia) {
            $factor = $this->obtenerFactor($cirugia['Liquidar'], $cirugia['GrupoQX'], $tipoMedico);
            
            if (strpos($cirugia['Liquidar'], 'SOAT') !== false) {
                // Cálculo para SOAT
                $honorario = $valorDia * $factor;
            } else {
                // Cálculo para UVB/UVT
                $valorBase = ($cirugia['Liquidar'] == 'UVB') ? $salarioMinimo['GSAVALUVB'] : $salarioMinimo['GSAVALUVT'];
                $honorario = $factor * $valorBase;
            }
            
            // Aplicar porcentajes según posición para cirujanos
            if ($tipoMedico == 'cirujano') {
                if ($index == 0) {
                    $total += $honorario * 0.1; // 100% para el primero
                } elseif ($index == 1) {
                    $total += ($honorario * 0.75) * 0.1; // 75% para el segundo
                }
                // A partir del cuarto en adelante no se suma nada
            } else {
                // Anestesiólogo solo cobra el más alto
                if ($index == 0) {
                    $total = $honorario * 0.1;
                    break;
                }
            }
        }
        
        return round($total, 2);
    }
    
    /**
     * Obtiene el factor correcto según tipo de liquidación y grupo QX
     */
    private function obtenerFactor($liquidar, $grupoQX, $tipoMedico) {
        $columna = ($tipoMedico == 'cirujano') ? 'CIRUGIA' : 'ANESTECIA';
        $tipoSOAT = $liquidar; // Valor por defecto
        // Para UVB/UVT usamos las columnas específicas
        if ($liquidar == 'UVB' || $liquidar == 'UVT') {
            $columna .= $liquidar;
        }
            if ($liquidar == 'SOAT -10%') {
            // Convertir "SOAT -X%" a "SOAT-X%" (sin espacios)
            $tipoSOAT = str_replace('SOAT -', 'SOAT-', $liquidar);
        }else if($liquidar == 'UVB' || $liquidar == 'UVT'){
            $tipoSOAT = "SOAT PLENO"; // Valor por defecto
        }else{
             $tipoSOAT = $liquidar; // Valor por defecto
        }
        // Consulta SQL con LIKE para mayor flexibilidad
        $sql = "SELECT $columna FROM factoressoat WHERE SOAT LIKE ? AND GRUPO = ?";
        $stmt = $this->_conexion->prepare($sql);
        $stmt->execute([$tipoSOAT, $grupoQX]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
       
        return $resultado[$columna] ?? 0;
    }
    
    /**
     * Obtiene el salario mínimo actual
     */
    private function obtenerSalarioMinimoActual() {
        $sql = "SELECT * FROM salariominimo ORDER BY IdSalarioMinimo DESC LIMIT 1";
        $stmt = $this->_conexion->query($sql);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Obtiene honorarios por médico
     */
    function obtenerHonorariosPorMedico($documentoMedico, $fechaInicio = null, $fechaFin = null, $mes = null, $anio = null) {
        // Añadir horas a las fechas si están presentes (usando FechaFactura)
        if ($fechaInicio && $fechaFin) {
            $fechaInicioConHora = $fechaInicio . ' 00:00:00';
            $fechaFinConHora = $fechaFin . ' 23:59:59';
        }
    
        // Construir la condición de fecha/mes/año usando FechaFactura
        $condicionFecha = '';
        if ($fechaInicio && $fechaFin) {
            $condicionFecha = " AND FechaFactura BETWEEN ? AND ?";
        } elseif ($mes && $anio) {
            $condicionFecha = " AND MONTH(FechaFactura) = ? AND YEAR(FechaFactura) = ?";
        }
    
        // Consulta optimizada que determina el rol del médico en cada ingreso
        $sql = "SELECT 
                i.Ingreso,
                i.FechaCirugia,
                i.FechaFactura,
                MAX(CASE WHEN c.DocumentoMedico = ? THEN 1 ELSE 0 END) as EsCirujano,
                MAX(CASE WHEN c.DocumentoMedicoAnes = ? THEN 1 ELSE 0 END) as EsAnestesiologo
                FROM (
                    SELECT DISTINCT Ingreso, FechaCirugia, FechaFactura 
                    FROM cirugias 
                    WHERE (DocumentoMedico = ? OR DocumentoMedicoAnes = ?)
                    " . $condicionFecha . "
                ) i
                JOIN cirugias c ON i.Ingreso = c.Ingreso
                GROUP BY i.Ingreso, DATE(i.FechaFactura)
                ORDER BY i.FechaFactura DESC";
        
        $params = [$documentoMedico, $documentoMedico, $documentoMedico, $documentoMedico];
        
        if ($fechaInicio && $fechaFin) {
            array_push($params, $fechaInicioConHora, $fechaFinConHora);
        } elseif ($mes && $anio) {
            array_push($params, $mes, $anio);
        }
    
        $stmt = $this->_conexion->prepare($sql);
        $stmt->execute($params);
        $ingresos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        $totalCirujano = 0;
        $totalAnestesiologo = 0;
        
        foreach ($ingresos as $ingreso) {
            // Obtenemos las cirugías del ingreso filtradas por el médico y FechaFactura
            $sqlCirugias = "SELECT A.* FROM cirugias A
            LEFT JOIN cupsevento B ON A.DocumentoMedico = B.DocumentoMedico AND B.Cups = A.OIDProcedimiento
            LEFT JOIN cupsevento C ON A.DocumentoMedicoAnes = C.DocumentoMedico AND C.Cups = A.OIDProcedimiento
            WHERE A.Ingreso = ? 
            AND (A.DocumentoMedico = ? OR A.DocumentoMedicoAnes = ?) 
            AND (B.IdCupsEvento IS NULL OR A.DocumentoMedico != ?)
            AND (C.IdCupsEvento IS NULL OR A.DocumentoMedicoAnes != ?)
            " . $condicionFecha;
            
            $paramsCirugias = [
                $ingreso['Ingreso'], 
                $documentoMedico, 
                $documentoMedico,
                $documentoMedico,  // Para la condición A.DocumentoMedico != ?
                $documentoMedico   // Para la condición A.DocumentoMedicoAnes != ?
            ];
            
            if ($fechaInicio && $fechaFin) {
                array_push($paramsCirugias, $fechaInicioConHora, $fechaFinConHora);
            } elseif ($mes && $anio) {
                array_push($paramsCirugias, $mes, $anio);
            }
            
            $stmt = $this->_conexion->prepare($sqlCirugias);
            $stmt->execute($paramsCirugias);
            $cirugias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($ingreso['EsCirujano']) {
                $totalCirujano += $this->calcularHonorariosCirujano($cirugias);
            } 
            
            if ($ingreso['EsAnestesiologo']) {
                $totalAnestesiologo += $this->calcularHonorariosAnestesiologo($cirugias);
            }
        }
    
        return [
            'cirujano' => round($totalCirujano, 2),
            'anestesiologo' => round($totalAnestesiologo, 2),
            'total' => round($totalCirujano + $totalAnestesiologo, 2),
            'ingresos' => count($ingresos)
        ];
    }

    public function ListarCirugiasConHonorarios($filtros = []) {
        // Obtener las cirugías usando la función existente
        $cirugias = $this->ListarCirugias($filtros);
        
        if (empty($cirugias)) {
            error_log("No se encontraron cirugías para los filtros: " . json_encode($filtros));
            return [];
        }

        // Valores fijos según la tabla de salarios
        $salarioMinimo = $this->obtenerSalarioMinimoActual();
        $valorDia = $salarioMinimo['GSAVALSAL'] / 30;

        // Agrupar cirugías por Ingreso y FechaFactura (pero mostrar FechaCirugia)
        $cirugiasPorIngresoYFactura = [];
        foreach ($cirugias as $cirugia) {
            if (!isset($cirugia['FechaFactura']) || !isset($cirugia['Ingreso'])) {
                error_log("Error: Cirugía sin FechaFactura o Ingreso: " . json_encode($cirugia));
                continue;
            }
            $fechaFactura = date('Y-m-d', strtotime($cirugia['FechaCirugia']));
            $ingreso = $cirugia['Ingreso'];
            $cirugiasPorIngresoYFactura[$ingreso][$fechaFactura][] = $cirugia;
        }

        error_log("Cirugías agrupadas por Ingreso y FechaFactura: " . json_encode(array_keys($cirugiasPorIngresoYFactura)));

        // Procesar honorarios para cada grupo
        $cirugiasConHonorarios = [];
        $documentoMedico = $filtros['documentoMedico'] ?? '';

        foreach ($cirugiasPorIngresoYFactura as $ingreso => $cirugiasPorFactura) {
            foreach ($cirugiasPorFactura as $fechaFactura => $cirugiasDelDia) {
                // Log del grupo actual
                error_log("Procesando grupo: Ingreso=$ingreso, FechaFactura=$fechaFactura, Total cirugías=" . count($cirugiasDelDia));

                // Ordenar por GrupoQX descendente, manejando valores no numéricos
                usort($cirugiasDelDia, function($a, $b) {
                    $grupoA = is_numeric($a['GrupoQX']) ? (int)$a['GrupoQX'] : 0;
                    $grupoB = is_numeric($b['GrupoQX']) ? (int)$b['GrupoQX'] : 0;
                    return $grupoB - $grupoA;
                });

                // Log de cirugías ordenadas
                $cirugiasLog = array_map(function($c) {
                    return [
                        'IdCirugia' => $c['IdCirugia'],
                        'GrupoQX' => $c['GrupoQX'],
                        'Procedimiento' => $c['Procedimiento'],
                        'FechaCirugia' => $c['FechaCirugia'],
                        'FechaFactura' => $c['FechaFactura']
                    ];
                }, $cirugiasDelDia);
                error_log("Cirugías ordenadas (Ingreso=$ingreso, FechaFactura=$fechaFactura): " . json_encode($cirugiasLog));

                // Determinar si el médico es cirujano o anestesiólogo
                $esCirujano = false;
                if (!empty($documentoMedico) && !empty($cirugiasDelDia)) {
                    $esCirujano = $cirugiasDelDia[0]['DocumentoMedico'] === $documentoMedico;
                }
                error_log("Rol del médico: " . ($esCirujano ? 'Cirujano' : 'Anestesiólogo'));

                if ($esCirujano) {
                    // Cirujano: Incluir todas las cirugías con porcentajes 100%, 75%, 50%
                    foreach ($cirugiasDelDia as $index => $cirugia) {
                        
                        if ($index > 1) {
                            break; // Salir del foreach después de la tercera cirugía
                        }
                                        
                        
                        $grupoQX = ($cirugia['GrupoQX']) ? $cirugia['GrupoQX'] : 0;
                        $Liquidar = $cirugia['Liquidar'];
                        $procedimiento = $cirugia['Procedimiento'];

                        // Obtener factor para cirujano
                        $factor = $this->obtenerFactor($Liquidar, $grupoQX, 'cirujano');

                        if ($factor == 0) {
                            error_log("Error: Factor no encontrado para Liquidar=$Liquidar, GrupoQX=$grupoQX, Rol=cirujano, IdCirugia={$cirugia['IdCirugia']}");
                            $cirugia['Honorario'] = 0;
                        } else {
                            // Calcular honorario base
                            $honorario = $this->calcularHonorarioIndividual($Liquidar, $factor, $salarioMinimo, $valorDia);

                            // Aplicar porcentaje según posición
                            $porcentaje = $index == 0 ? 1.0 : ($index == 1 ? 0.75 : 0);
                            $honorarioAjustado = $honorario * $porcentaje;
                            $cirugia['Honorario'] = round($honorarioAjustado, 2);

                            // Log del cálculo
                            error_log("Cirujano - IdCirugia={$cirugia['IdCirugia']}, Index=$index, GrupoQX=$grupoQX, Liquidar=$Liquidar, Factor=$factor, Honorario Base=$honorario, Porcentaje=" . ($porcentaje * 100) . "%, Honorario Ajustado={$cirugia['Honorario']}");
                        }

                        // Agregar la cirugía al resultado
                        $cirugiasConHonorarios[] = $cirugia;
                    }
                } else {
                    // Anestesiólogo: Incluir solo la cirugía con mayor GrupoQX
                    $cirugia = $cirugiasDelDia[0]; // Mayor GrupoQX
                    $grupoQX = is_numeric($cirugia['GrupoQX']) ? (int)$cirugia['GrupoQX'] : 0;
                    $Liquidar = $cirugia['Liquidar'];
                    $procedimiento = $cirugia['Procedimiento'];

                    // Obtener factor para anestesiólogo
                    $factor = $this->obtenerFactor($Liquidar, $grupoQX, 'anestesiologo');

                    if ($factor == 0) {
                        error_log("Error: Factor no encontrado para Liquidar=$Liquidar, GrupoQX=$grupoQX, Rol=anestesiologo, IdCirugia={$cirugia['IdCirugia']}");
                        $cirugia['Honorario'] = 0;
                    } else {
                        // Calcular honorario base (100% para la mayor)
                        $honorario = $this->calcularHonorarioIndividual($Liquidar, $factor, $salarioMinimo, $valorDia);
                        $cirugia['Honorario'] = round($honorario, 2);

                        // Log del cálculo
                        error_log("Anestesiólogo - IdCirugia={$cirugia['IdCirugia']}, GrupoQX=$grupoQX, Liquidar=$Liquidar, Factor=$factor, Honorario={$cirugia['Honorario']}");
                    }

                    // Agregar la cirugía al resultado
                    $cirugiasConHonorarios[] = $cirugia;
                }
            }
        }

        // Log de cirugías procesadas
        error_log("Cirugías con honorarios procesadas: " . json_encode($cirugiasConHonorarios));
        return $cirugiasConHonorarios;
    }

    /**
     * Calcula honorarios específicos para cirujano
     */
    private function calcularHonorariosCirujano($cirugias) {
               
        $salarioMinimo = $this->obtenerSalarioMinimoActual();
        $valorDia = $salarioMinimo['GSAVALSAL'] / 30;
    
        $cirugiasArray = [];
        if (isset($cirugias[0]) && is_array($cirugias[0])) {
            // Caso: $cirugias ya es un array plano de cirugías
            $cirugiasArray = $cirugias;
        } else {
            // Caso: $cirugias está anidado por ingreso y fecha
            foreach ($cirugias as $ingreso => $fechas) {
                foreach ($fechas as $fecha => $cirugiasDelDia) {
                    foreach ($cirugiasDelDia as $cirugia) {
                        $cirugiasArray[] = $cirugia;
                    }
                }
            }
        }

        $cirugiasUnicas = [];
        $idsProcesados = [];
        foreach ($cirugiasArray as $cirugia) {
            $cirugiasUnicas[] = $cirugia;
            $idsProcesados[] = $cirugia['IdCirugia'];
        }
    
        // Agrupar cirugías por Ingreso y FechaFactura
        $cirugiasPorIngresoYFactura = [];
        foreach ($cirugiasUnicas as $cirugia) {
            $fechaFactura = date('Y-m-d', strtotime($cirugia['FechaCirugia']));
            $ingreso = $cirugia['Ingreso'];
            $cirugiasPorIngresoYFactura[$ingreso][$fechaFactura][] = $cirugia;
        }

        $honorarioTotal = 0;
        $logCalculos = [];
        $calculoIndex = 1;
    
        foreach ($cirugiasPorIngresoYFactura as $ingreso => $cirugiasPorFactura) {
            foreach ($cirugiasPorFactura as $fechaFactura => $cirugiasDelDia) {
                // Ordenar por GrupoQX descendente
                usort($cirugiasDelDia, function($a, $b) {
                    return $b['GrupoQX'] - $a['GrupoQX'];
                });

                foreach ($cirugiasDelDia as $index => $cirugia) {
                    
                    if ($index > 1) {
                        break; // Salir del foreach después de la tercera cirugía
                    }
                    
                    $grupoQX = $cirugia['GrupoQX'];
                    $Liquidar = $cirugia['Liquidar'];
                    $procedimiento = $cirugia['Procedimiento'];
                    $factor = $this->obtenerFactor($Liquidar, $grupoQX, 'cirujano');
                    $honorario = $this->calcularHonorarioIndividual(
                        $Liquidar,
                        $factor,
                        $salarioMinimo,
                        $valorDia
                    );
    
                    // Aplicar porcentaje según posición
                    if ($index == 0) {
                        $porcentaje = 1.0; // 100% para el mayor GrupoQX
                    } elseif ($index == 1) {
                        $porcentaje = 0.75; // 75% para el segundo mayor
                    }
    
                    $honorarioAjustado = $honorario * $porcentaje;
                    $honorarioAjustado = round($honorarioAjustado, 2);
                    $honorarioTotal += $honorarioAjustado;
                }
            }
        }
    
        return round($honorarioTotal, 2);
    }
    
    
        /**
     * Calcula honorarios de una cirugia 
     */
    function calcularHonorarioIndividualPorRol($Liquidar, $grupoQX, $rol)
    {
        // Validar rol permitido
        $rolesPermitidos = ['cirujano', 'anestesiologo'];
    
        if (!in_array($rol, $rolesPermitidos)) {
            throw new InvalidArgumentException("Rol no válido: $rol");
        }
    
        // 1. Salario mínimo actual
        $salarioMinimo = $this->obtenerSalarioMinimoActual();
    
        // 2. Valor día

        $valorDia = $salarioMinimo['GSAVALSAL'] / 30;
    
        // 3. Factor según rol
        $factor = $this->obtenerFactor($Liquidar, $grupoQX, $rol);
    
        // Validación de seguridad
        if ($factor === null || $factor === 0) {
            return 0;
        }
    
        // 4. Cálculo del honorario
        $honorario = $this->calcularHonorarioIndividualfull(
            $Liquidar,
            $factor,
            $salarioMinimo,
            $valorDia
        );
    
        return round($honorario, 2);
    }



    /**
     * Calcula honorarios específicos para anestesiólogo
     */
    private function calcularHonorariosAnestesiologo($cirugias) {
        if (empty($cirugias)) return 0;
     
        $salarioMinimo = $this->obtenerSalarioMinimoActual();
        $valorDia = $salarioMinimo['GSAVALSAL'] / 30;
        
        // Agrupar cirugías por Ingreso y FechaFactura
        $cirugiasPorIngresoYFactura = [];
        foreach ($cirugias as $cirugia) {
            // Extraer solo la fecha (sin hora) de FechaFactura
            $fechaFactura = date('Y-m-d', strtotime($cirugia['FechaCirugia']));
            $ingreso = $cirugia['Ingreso'];
            $cirugiasPorIngresoYFactura[$ingreso][$fechaFactura][] = $cirugia;
        }
          
        // Seleccionar la cirugía con mayor GrupoQX por FechaFactura para cada ingreso
        $cirugiasMasAltas = [];
        foreach ($cirugiasPorIngresoYFactura as $ingreso => $cirugiasPorFactura) {
            foreach ($cirugiasPorFactura as $fechaFactura => $cirugiasDelDia) {
                // Ordenar por GrupoQX descendente
                usort($cirugiasDelDia, function($a, $b) {
                    return $b['GrupoQX'] - $a['GrupoQX'];
                });
                // Tomar la primera (mayor GrupoQX)
                $cirugiasMasAltas[] = $cirugiasDelDia[0];
            }
        }
          
        // Calcular honorarios para cada cirugía seleccionada
        $honorarioTotal = 0;
        $log = [];

        foreach ($cirugiasMasAltas as $cirugiaMasAlta) {
            $factor = $this->obtenerFactor($cirugiaMasAlta['Liquidar'], $cirugiaMasAlta['GrupoQX'], 'anestesiologo');
            $honorario = $this->calcularHonorarioIndividual(
                $cirugiaMasAlta['Liquidar'], 
                $factor, 
                $salarioMinimo, 
                $valorDia
            );
            
            // Redondear cada honorario individual a 2 decimales
            $honorario = round($honorario, 2);
            $honorarioTotal += $honorario;
        }
     
        return round($honorarioTotal, 2);
    }

    /**
     * Calcula el honorario individual para un procedimiento
     */
    private function calcularHonorarioIndividual($liquidar, $factor, $salarioMinimo, $valorDia) {
       
        if (strpos($liquidar, 'SOAT') !== false) {
            // Cálculo para SOAT
            return ($valorDia * $factor) * 0.1;
        } else {
            // Cálculo para UVB/UVT
            switch ($liquidar) {
                case 'UVB':
                    $valorBase = $salarioMinimo['GSAVALUVB'];
                    break;
                case 'UVT':
                    $valorBase = $salarioMinimo['GSAVALUVT'];
                    break;
                default:
                    $valorBase = 0; // O manejar el error como prefieras
                    break;
            }
            return ($factor * $valorBase) * 0.1;
        }
    }
        private function calcularHonorarioIndividualfull($liquidar, $factor, $salarioMinimo, $valorDia) {
       
        if (strpos($liquidar, 'SOAT') !== false) {
            // Cálculo para SOAT
            return ($valorDia * $factor) * 0.1;
        } else {
            // Cálculo para UVB/UVT
            switch ($liquidar) {
                case 'UVB':
                    $valorBase = $salarioMinimo['GSAVALUVB'];
                    break;
                case 'UVT':
                    $valorBase = $salarioMinimo['GSAVALUVT'];
                    break;
                default:
                    $valorBase = 0; // O manejar el error como prefieras
                    break;
            }
            return ($factor * $valorBase);
        }
    }
    /**
     * Obtiene estadísticas generales de honorarios
     */
    function obtenerEstadisticasHonorarios($fechaInicio, $fechaFin) {
        // Primero obtenemos todos los médicos
        $sqlMedicos = "SELECT 
            m.DocumentoMedico, 
            m.NombreMedico,
            e.NombreEspecialidad
            FROM medico m
            INNER JOIN especialidad e ON m.IdEspecialidadMedico = e.IdEspecialidad
            ";
        $stmt = $this->_conexion->query($sqlMedicos);
        $medicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Preparamos la cláusula WHERE para fechas usando FechaFactura
        $where = "1=1";
        $params = [];
        
        if ($fechaInicio && $fechaFin) {
            // Añadimos horas, minutos y segundos a las fechas
            $fechaInicioConHora = $fechaInicio . ' 00:00:00';
            $fechaFinConHora = $fechaFin . ' 23:59:59';
            
            $where .= " AND FechaFactura BETWEEN ? AND ?";
            $params = [$fechaInicioConHora, $fechaFinConHora];
        }
        
        // Obtenemos todos los ingresos únicos con sus honorarios
        $ingresos = $this->obtenerIngresosConHonorarios($where, $params);
        
        // Calculamos estadísticas por médico
        $estadisticasMedicos = [];
        foreach ($medicos as $medico) {
            $doc = $medico['DocumentoMedico'];
            $estadisticasMedicos[$doc] = [
                'NombreMedico' => $medico['NombreMedico'],
                'DocumentoMedico' => $doc,
                'NombreEspecialidad' => $medico['NombreEspecialidad'],
                'TotalCirujano' => 0,
                'TotalAnestesiologo' => 0
            ];
        }
        
        // Procesamos cada ingreso para acumular honorarios
        foreach ($ingresos as $ingreso) {
            if ($ingreso['DocumentoMedico'] && isset($estadisticasMedicos[$ingreso['DocumentoMedico']])) {
                $estadisticasMedicos[$ingreso['DocumentoMedico']]['TotalCirujano'] += $ingreso['CirujanoHonorario'];
            }
            
            if ($ingreso['DocumentoMedicoAnes'] && isset($estadisticasMedicos[$ingreso['DocumentoMedicoAnes']])) {
                $estadisticasMedicos[$ingreso['DocumentoMedicoAnes']]['TotalAnestesiologo'] += $ingreso['AnestesiologoHonorario'];
            }
        }
        
        // Ordenamos por total de honorarios
        usort($estadisticasMedicos, function($a, $b) {
            $totalA = $a['TotalCirujano'] + $a['TotalAnestesiologo'];
            $totalB = $b['TotalCirujano'] + $b['TotalAnestesiologo'];
            return $totalB - $totalA;
        });
        
        return [
            'medicos' => $estadisticasMedicos
        ];
    }

    function obtenerEstadisticasHonorarios2($filtros = []) {
        
        $sqlMedicos = "SELECT 
            m.DocumentoMedico, 
            m.NombreMedico,
            e.NombreEspecialidad
        FROM medico m
        INNER JOIN especialidad e ON m.IdEspecialidadMedico = e.IdEspecialidad";
        $stmt = $this->_conexion->query($sqlMedicos);
        $medicos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $where = "1=1";
        $params = [];

        if (!empty($filtros)) {
            if (isset($filtros['fechaInicio']) && isset($filtros['fechaFin'])) {
                // Añadir horas a las fechas usando FechaFactura
                $fechaInicioConHora = $filtros['fechaInicio'] . ' 00:00:00';
                $fechaFinConHora = $filtros['fechaFin'] . ' 23:59:59';
                
                $where .= " AND FechaFactura BETWEEN ? AND ?";
                $params = [$fechaInicioConHora, $fechaFinConHora];
            }
        }

        $ingresos = $this->obtenerIngresosConHonorarios($where, $params);

        $estadisticasMedicos = [];
        foreach ($medicos as $medico) {
            $doc = $medico['DocumentoMedico'];
            $estadisticasMedicos[$doc] = [
                'NombreMedico' => $medico['NombreMedico'],
                'DocumentoMedico' => $doc,
                'NombreEspecialidad' => $medico['NombreEspecialidad'],
                'TotalCirujano' => 0,
                'TotalAnestesiologo' => 0,
                'TotalHonorarios' => 0
            ];
        }

        // Procesamos cada ingreso para acumular honorarios
        foreach ($ingresos as $ingreso) {
            if ($ingreso['DocumentoMedico'] && isset($estadisticasMedicos[$ingreso['DocumentoMedico']])) {
                $estadisticasMedicos[$ingreso['DocumentoMedico']]['TotalCirujano'] += $ingreso['CirujanoHonorario'];
                $estadisticasMedicos[$ingreso['DocumentoMedico']]['TotalHonorarios'] += $ingreso['CirujanoHonorario'];
            }
            
            if ($ingreso['DocumentoMedicoAnes'] && isset($estadisticasMedicos[$ingreso['DocumentoMedicoAnes']])) {
                $estadisticasMedicos[$ingreso['DocumentoMedicoAnes']]['TotalAnestesiologo'] += $ingreso['AnestesiologoHonorario'];
                $estadisticasMedicos[$ingreso['DocumentoMedicoAnes']]['TotalHonorarios'] += $ingreso['AnestesiologoHonorario'];
            }
        }

        $estadisticasMedicos = array_values($estadisticasMedicos);
        usort($estadisticasMedicos, function($a, $b) {
            return $b['TotalHonorarios'] - $a['TotalHonorarios'];
        });

        return [
            'medicos' => $estadisticasMedicos
        ];
    }
    
    /**
     * Obtiene todos los ingresos con sus honorarios calculados
     */
    private function obtenerIngresosConHonorarios($where, $params) {
        // Primero obtenemos todos los ingresos únicos usando FechaFactura
        $sql = "SELECT DISTINCT Ingreso FROM cirugias WHERE $where";
        $stmt = $this->_conexion->prepare($sql);
        $stmt->execute($params);
        $ingresos = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $resultados = [];
        
        foreach ($ingresos as $ingreso) {
            // Obtenemos las cirugías para este ingreso, aplicando los mismos filtros de $where y $params
            $sqlCirugias = "SELECT * FROM cirugias WHERE $where AND Ingreso = ?";
            $currentParams = array_merge($params, [$ingreso]); // Agregamos el ingreso a los parámetros existentes
            $stmt = $this->_conexion->prepare($sqlCirugias);
            $stmt->execute($currentParams);
            $cirugias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($cirugias)) continue;
            
            // Ordenamos por GrupoQX descendente
            usort($cirugias, function($a, $b) {
                return $b['GrupoQX'] - $a['GrupoQX'];
            });
            
            // Obtenemos médicos únicos para este ingreso
            $medicosCirujanos = [];
            $medicosAnestesistas = [];
            
            foreach ($cirugias as $cirugia) {
                if ($cirugia['DocumentoMedico']) {
                    $medicosCirujanos[$cirugia['DocumentoMedico']] = true;
                }
                if ($cirugia['DocumentoMedicoAnes']) {
                    $medicosAnestesistas[$cirugia['DocumentoMedicoAnes']] = true;
                }
            }
            
            // Calculamos honorarios para cada médico en este ingreso
            foreach ($medicosCirujanos as $doc => $_) {
                $resultados[] = [
                    'Ingreso' => $ingreso,
                    'DocumentoMedico' => $doc,
                    'DocumentoMedicoAnes' => null,
                    'CirujanoHonorario' => $this->calcularHonorariosMedicoEnIngreso($doc, $ingreso, 'cirujano', $cirugias),
                    'AnestesiologoHonorario' => 0
                ];
            }
            
            foreach ($medicosAnestesistas as $doc => $_) {
                $resultados[] = [
                    'Ingreso' => $ingreso,
                    'DocumentoMedico' => null,
                    'DocumentoMedicoAnes' => $doc,
                    'CirujanoHonorario' => 0,
                    'AnestesiologoHonorario' => $this->calcularHonorariosMedicoEnIngreso($doc, $ingreso, 'anestesiologo', $cirugias)
                ];
            }
        }
        
        return $resultados;
    }
    
    /**
     * Calcula honorarios para un médico específico en un ingreso específico
     */
    private function calcularHonorariosMedicoEnIngreso($documento, $ingreso, $tipo, $cirugias) {
        $salarioMinimo = $this->obtenerSalarioMinimoActual();
        $valorDia = $salarioMinimo['GSAVALSAL'] / 30;
        
        $total = 0;
        $procedimientosMedico = [];
        
        // Filtrar cirugías donde participó el médico
        foreach ($cirugias as $cirugia) {
            if (($tipo == 'cirujano' && $cirugia['DocumentoMedico'] == $documento) || 
                ($tipo == 'anestesiologo' && $cirugia['DocumentoMedicoAnes'] == $documento)) {
                $procedimientosMedico[] = $cirugia;
            }
        }
        
        // Ordenar por GrupoQX descendente
        usort($procedimientosMedico, function($a, $b) {
            return $b['GrupoQX'] - $a['GrupoQX'];
        });
        
        // Calcular honorarios
        foreach ($procedimientosMedico as $index => $cirugia) {
            $factor = $this->obtenerFactor($cirugia['Liquidar'], $cirugia['GrupoQX'], $tipo);
            
            if (strpos($cirugia['Liquidar'], 'SOAT') !== false) {
                $honorario = $valorDia * $factor;
            } else {
                $valorBase = ($cirugia['Liquidar'] == 'UVB') ? $salarioMinimo['GSAVALUVB'] : $salarioMinimo['GSAVALUVT'];
                $honorario = $factor * $valorBase;
            }
            
            // Aplicar porcentajes según posición para cirujanos
            if ($tipo == 'cirujano') {
                if ($index == 0) {
                    $total += $honorario * 0.1; // 100% para el primero
                } elseif ($index == 1) {
                    $total += ($honorario * 0.75) * 0.1; // 75% para el segundo
                }
                // A partir del cuarto en adelante no se suma nada
            } else {
                // Anestesiólogo solo cobra el más alto
                if ($index == 0) {
                    $total = $honorario * 0.1;
                    break;
                }
            }
        }
        
        return round($total, 2);
    }

    function obtenerHonorarioMensualPorMedico($documentoMedico, $mes, $anio) {
        // Consulta optimizada que determina el rol del médico en cada ingreso usando FechaFactura
        $sql = "SELECT 
                i.Ingreso,
                i.FechaCirugia,
                i.FechaFactura,
                MAX(CASE WHEN c.DocumentoMedico = ? THEN 1 ELSE 0 END) as EsCirujano,
                MAX(CASE WHEN c.DocumentoMedicoAnes = ? THEN 1 ELSE 0 END) as EsAnestesiologo
                FROM (
                    SELECT DISTINCT Ingreso, FechaCirugia, FechaFactura 
                    FROM cirugias 
                    WHERE (DocumentoMedico = ? OR DocumentoMedicoAnes = ?)
                   ";
        
        // Añadir condición para filtrar por mes y año si están presentes (usando FechaFactura)
        if ($mes && $anio) {
            $sql .= " AND MONTH(FechaFactura) = ? AND YEAR(FechaFactura) = ?";
        }
        
        $sql .= ") i
                JOIN cirugias c ON i.Ingreso = c.Ingreso
                GROUP BY i.Ingreso, i.FechaFactura
                ORDER BY i.FechaFactura DESC";
        
        $params = [$documentoMedico, $documentoMedico, $documentoMedico, $documentoMedico];
        
        if ($mes && $anio) {
            array_push($params, $mes, $anio);
        }

        $stmt = $this->_conexion->prepare($sql);
        $stmt->execute($params);
        $ingresos = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $totalCirujano = 0;
        $totalAnestesiologo = 0;
        
        foreach ($ingresos as $ingreso) {
            // Obtenemos las cirugías del ingreso filtradas por el médico
            $sqlCirugias = "SELECT * FROM cirugias 
                            WHERE Ingreso = ? 
                            AND (DocumentoMedico = ? OR DocumentoMedicoAnes = ?)";
            $stmt = $this->_conexion->prepare($sqlCirugias);
            $stmt->execute([$ingreso['Ingreso'], $documentoMedico, $documentoMedico]);
            $cirugias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if ($ingreso['EsCirujano']) {
                $totalCirujano += $this->calcularHonorariosCirujano($cirugias);
            } 
            
            if ($ingreso['EsAnestesiologo']) {
                $totalAnestesiologo += $this->calcularHonorariosAnestesiologo($cirugias);
            }
        }
        
        return [
            'cirujano' => round($totalCirujano, 2),
            'anestesiologo' => round($totalAnestesiologo, 2),
            'TotalHonorarios' => round($totalCirujano + $totalAnestesiologo, 2),
            'ingresos' => count($ingresos) // Para información adicional
        ];
    }

    function ListarTurnosDisponibilidad($month, $year) {
        $listado = $this->_conexion->prepare("SELECT 
                    A.DocumentoMedico,
                    A.DocumentoMedicoAnes,
                    A.Liquidar,
                    A.GrupoQX,
                    A.FechaCirugia,
                    A.FechaFactura
                FROM cirugias A
                WHERE  MONTH(A.FechaFactura) = :month AND YEAR(A.FechaFactura) = :year");

        $listado->bindValue(':month', $month);
        $listado->bindValue(':year', $year);
        $listado->execute();
        $listado = $listado->fetchAll(PDO::FETCH_ASSOC);
        return $listado;
    }

    function ListarCirugias($filtros = []) {
        $sql = "SELECT c.*, 
               e.NombreEspecialidad as EspecialidadMedico,
               e_anes.NombreEspecialidad as EspecialidadAnestesiologo
        FROM cirugias c
        LEFT JOIN medico m ON c.DocumentoMedico = m.DocumentoMedico
        LEFT JOIN especialidad e ON m.IdEspecialidadMedico = e.IdEspecialidad
        LEFT JOIN medico m_anes ON c.DocumentoMedicoAnes = m_anes.DocumentoMedico
        LEFT JOIN especialidad e_anes ON m_anes.IdEspecialidadMedico = e_anes.IdEspecialidad
        LEFT JOIN cupsevento ce_med ON c.DocumentoMedico = ce_med.DocumentoMedico AND ce_med.Cups = c.OIDProcedimiento
        LEFT JOIN cupsevento ce_anes ON c.DocumentoMedicoAnes = ce_anes.DocumentoMedico AND ce_anes.Cups = c.OIDProcedimiento
        WHERE 1=1";
        
        $params = [];
        
        // Aplicar filtros si existen
        if (!empty($filtros)) {
            if (isset($filtros['fechaInicio']) && isset($filtros['fechaFin'])) {
                // Añadir horas a las fechas usando FechaFactura
                $fechaInicioConHora = $filtros['fechaInicio'] . ' 00:00:00';
                $fechaFinConHora = $filtros['fechaFin'] . ' 23:59:59';
                
                $sql .= " AND c.FechaFactura BETWEEN ? AND ?";
                $params[] = $fechaInicioConHora;
                $params[] = $fechaFinConHora;
            }

            if (isset($filtros['documentoMedico'])) {
                $documentoMedico = $filtros['documentoMedico'];
                $sql .= " AND (c.DocumentoMedico = ? OR c.DocumentoMedicoAnes = ?)";
                $params[] = $documentoMedico;
                $params[] = $documentoMedico;
                
                // Agregar condiciones para validar cupsevento solo si el documento coincide
                $sql .= " AND (ce_med.IdCupsEvento IS NULL OR c.DocumentoMedico != ?)";
                $sql .= " AND (ce_anes.IdCupsEvento IS NULL OR c.DocumentoMedicoAnes != ?)";
                $params[] = $documentoMedico;
                $params[] = $documentoMedico;
            } else {
                // Si no hay filtro por documento, excluir todos los que tengan registros en cupsevento
                $sql .= " AND ce_med.IdCupsEvento IS NULL AND ce_anes.IdCupsEvento IS NULL";
            }

            if (isset($filtros['especialidad'])) {
                $sql .= " AND (e.NombreEspecialidad = ? OR e_anes.NombreEspecialidad = ?)";
                $params[] = $filtros['especialidad'];
                $params[] = $filtros['especialidad'];
            }

            if (isset($filtros['eps'])) {
                $sql .= " AND c.EPS = ?";
                $params[] = $filtros['eps'];
            }

            if (isset($filtros['IdCirugia'])) {
                $sql .= " AND c.IdCirugia = ?";
                $params[] = $filtros['IdCirugia'];
            }

            if (isset($filtros['month'])) {
                $sql .= " AND MONTH(c.FechaFactura) = ?";
                $params[] = $filtros['month'];
            }
            if (isset($filtros['year'])) {
                $sql .= " AND YEAR(c.FechaFactura) = ?";
                $params[] = $filtros['year'];
            }
        } else {
            // Si no hay filtros, excluir todos los que tengan registros en cupsevento
            $sql .= " AND ce_med.IdCupsEvento IS NULL AND ce_anes.IdCupsEvento IS NULL";
        }

        $stmt = $this->_conexion->prepare($sql);
        $stmt->execute($params);
        $cirugias = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Obtener salario mínimo actual
        $salarioMinimo = $this->obtenerSalarioMinimoActual();
        $valorDia = $salarioMinimo['GSAVALSAL'] / 30;

        // Calcular honorarios para cada cirugía
        foreach ($cirugias as &$cirugia) {
            // Honorario del cirujano
            $factorCirujano = $this->obtenerFactor($cirugia['Liquidar'], $cirugia['GrupoQX'], 'cirujano');
            $cirugia['HonorarioCirujano'] = $this->calcularHonorarioIndividual(
                $cirugia['Liquidar'],
                $factorCirujano,
                $salarioMinimo,
                $valorDia
            );

            // Honorario del anestesiólogo
            $factorAnestesiologo = $this->obtenerFactor($cirugia['Liquidar'], $cirugia['GrupoQX'], 'anestesiologo');
            $cirugia['HonorarioAnestesiologo'] = $this->calcularHonorarioIndividual(
                $cirugia['Liquidar'],
                $factorAnestesiologo,
                $salarioMinimo,
                $valorDia
            );
        }

        return $cirugias;
    }

    function ListarMedicos($filtros = []) {
        $sql = "SELECT DISTINCT
                    m.DocumentoMedico,
                    m.NombreMedico,
                    m.CorreoMedico,
                    m.TelefonoMedico,
                    e.NombreEspecialidad
                FROM medico m
                INNER JOIN especialidad e ON m.IdEspecialidadMedico = e.IdEspecialidad
                INNER JOIN cirugias c ON (m.DocumentoMedico = c.DocumentoMedico OR m.DocumentoMedico = c.DocumentoMedicoAnes)
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($filtros)) {
            if (isset($filtros['fechaInicio']) && isset($filtros['fechaFin'])) {
                // Agregar horas a las fechas usando FechaFactura
                $fechaInicioConHora = $filtros['fechaInicio'] . ' 00:00:00';
                $fechaFinConHora = $filtros['fechaFin'] . ' 23:59:59';
                
                $sql .= " AND c.FechaFactura BETWEEN ? AND ?";
                $params[] = $fechaInicioConHora;
                $params[] = $fechaFinConHora;
            }
            
            if (isset($filtros['especialidad'])) {
                $sql .= " AND e.NombreEspecialidad = ?";  // Corregido para usar e.NombreEspecialidad
                $params[] = $filtros['especialidad'];
            }
            if (isset($filtros['documentoMedico'])) {
                
                $sql .= " AND m.IdMedico = ?";
                $params[] = $filtros['documentoMedico'];

            }
            if (isset($filtros['eps'])) {
                $sql .= " AND c.EPS = ?";
                $params[] = $filtros['eps'];
            }
        }
        
        $sql .= " ORDER BY m.NombreMedico";  // Corregido typo en NombreMedico
        
        $stmt = $this->_conexion->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    function obtenerHonorariosPorMes($DocumentoMedico, $fechaInicio = null, $fechaFin = null) {
        try {
            $where = "WHERE (DocumentoMedico = :DocumentoMedico OR DocumentoMedicoAnes = :DocumentoMedicoAnes)";
            $params = [
                ':DocumentoMedico' => $DocumentoMedico,
                ':DocumentoMedicoAnes' => $DocumentoMedico
            ];
            
            if ($fechaInicio !== null && $fechaFin !== null && $fechaInicio !== '' && $fechaFin !== '') {
                // Añadir horas a las fechas usando FechaFactura
                $fechaInicioConHora = $fechaInicio . ' 00:00:00';
                $fechaFinConHora = $fechaFin . ' 23:59:59';
                
                $where .= " AND FechaFactura BETWEEN :fechaInicio AND :fechaFin";
                $params[':fechaInicio'] = $fechaInicioConHora;
                $params[':fechaFin'] = $fechaFinConHora;
            }
            
            $sql = "SELECT 
                    YEAR(FechaFactura) as Ano,
                    MONTH(FechaFactura) as Mes,
                    DocumentoMedico,
                    DocumentoMedicoAnes,
                    Liquidar,
                    GrupoQX
                    FROM cirugias 
                    $where 
                    ORDER BY Ano, Mes";
            
            $stmt = $this->_conexion->prepare($sql);
            
            // Log query and parameters for debugging
            error_log("SQL Query: $sql");
            error_log("Parameters: " . json_encode($params));
            
            $stmt->execute($params);
            $cirugias = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $salarioMinimo = $this->obtenerSalarioMinimoActual();
            if (!$salarioMinimo) {
                throw new Exception("No se pudo obtener el salario mínimo actual");
            }
            $valorDia = $salarioMinimo['GSAVALSAL'] / 30;
            
            $resultados = [];
            
            foreach ($cirugias as $cirugia) {
                $ano = $cirugia['Ano'];
                $mes = $cirugia['Mes'];
                $key = "$ano-$mes";
                
                if (!isset($resultados[$key])) {
                    $resultados[$key] = [
                        'Ano' => (int)$ano,
                        'Mes' => (int)$mes,
                        'HonorariosCirujano' => 0,
                        'HonorariosAnestesiologo' => 0,
                        'TotalHonorarios' => 0
                    ];
                }
                
                if ($cirugia['DocumentoMedico'] == $DocumentoMedico) {
                    $factor = $this->obtenerFactor($cirugia['Liquidar'], $cirugia['GrupoQX'], 'cirujano');
                    $honorario = $this->calcularHonorarioIndividual($cirugia['Liquidar'], $factor, $salarioMinimo, $valorDia);
                    $resultados[$key]['HonorariosCirujano'] += round($honorario, 2);
                    $resultados[$key]['TotalHonorarios'] += round($honorario, 2);
                }
                
                if ($cirugia['DocumentoMedicoAnes'] == $DocumentoMedico) {
                    $factor = $this->obtenerFactor($cirugia['Liquidar'], $cirugia['GrupoQX'], 'anestesiologo');
                    $honorario = $this->calcularHonorarioIndividual($cirugia['Liquidar'], $factor, $salarioMinimo, $valorDia);
                    $resultados[$key]['HonorariosAnestesiologo'] += round($honorario, 2);
                    $resultados[$key]['TotalHonorarios'] += round($honorario, 2);
                }
            }
            
            $resultados = array_values($resultados);
            usort($resultados, function($a, $b) {
                return ($a['Ano'] * 100 + $a['Mes']) - ($b['Ano'] * 100 + $b['Mes']);
            });
            
            return $resultados;
        } catch (PDOException $e) {
            error_log("PDO Error in obtenerHonorariosPorMes: " . $e->getMessage());
            throw new Exception("Error executing query: " . $e->getMessage());
        } catch (Exception $e) {
            error_log("General Error in obtenerHonorariosPorMes: " . $e->getMessage());
            throw new Exception("Error processing honorarios: " . $e->getMessage());
        }
    }
    
    function obtenerHonorariosPorEPS($documentoMedico, $fechaInicio = null, $fechaFin = null) {
        // Corregir nombres de campos (typo en Documneto -> Documento)
        $where = "WHERE (DocumentoMedico = ? OR DocumentoMedicoAnes = ?) ";
        $params = [$documentoMedico, $documentoMedico];
        
        if ($fechaInicio && $fechaFin) {
            // Agregar horas a las fechas para incluir todo el día usando FechaFactura
            $fechaInicioConHora = $fechaInicio . ' 00:00:00';
            $fechaFinConHora = $fechaFin . ' 23:59:59';
            
            $where .= " AND FechaFactura BETWEEN ? AND ?";
            array_push($params, $fechaInicioConHora, $fechaFinConHora);
        }
        
        // Consulta optimizada para obtener solo los campos necesarios
        $sql = "SELECT EPS, DocumentoMedico, DocumentoMedicoAnes, Liquidar, GrupoQX 
                FROM cirugias 
                $where";
        
        $stmt = $this->_conexion->prepare($sql);
        $stmt->execute($params);
        $cirugias = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtenemos el salario mínimo actual una sola vez
        $salarioMinimo = $this->obtenerSalarioMinimoActual();
        if (!$salarioMinimo) {
            throw new Exception("No se pudo obtener el salario mínimo actual");
        }
        $valorDia = $salarioMinimo['GSAVALSAL'] / 30;
        
        // Procesamos las cirugías por EPS
        $resultados = [];
        
        foreach ($cirugias as $cirugia) {
            $eps = $cirugia['EPS'];
            
            if (!isset($resultados[$eps])) {
                $resultados[$eps] = [
                    'EPS' => $eps,
                    'HonorariosCirujano' => 0,
                    'HonorariosAnestesiologo' => 0,
                    'TotalHonorarios' => 0  // Nuevo campo para total
                ];
            }
            
            // Calculamos honorarios según el rol del médico
            if ($cirugia['DocumentoMedico'] == $documentoMedico) {
                $factor = $this->obtenerFactor($cirugia['Liquidar'], $cirugia['GrupoQX'], 'cirujano');
                $honorario = $this->calcularHonorarioIndividual(
                    $cirugia['Liquidar'], 
                    $factor, 
                    $salarioMinimo, 
                    $valorDia
                );
                $resultados[$eps]['HonorariosCirujano'] += round($honorario, 2);
                $resultados[$eps]['TotalHonorarios'] += round($honorario, 2);
            }
            
            if ($cirugia['DocumentoMedicoAnes'] == $documentoMedico) {
                $factor = $this->obtenerFactor($cirugia['Liquidar'], $cirugia['GrupoQX'], 'anestesiologo');
                $honorario = $this->calcularHonorarioIndividual(
                    $cirugia['Liquidar'], 
                    $factor, 
                    $salarioMinimo, 
                    $valorDia
                );
                $resultados[$eps]['HonorariosAnestesiologo'] += round($honorario, 2);
                $resultados[$eps]['TotalHonorarios'] += round($honorario, 2);
            }
        }
        
        // Convertimos el array asociativo a indexado y ordenamos por total descendente
        $resultados = array_values($resultados);
        usort($resultados, function($a, $b) {
            return $b['TotalHonorarios'] - $a['TotalHonorarios'];  // Orden descendente por total
        });
        
        return $resultados;
    }
    
    /**
     * Lista todos los factores SOAT
     */
    function Listarfactoressoat() {
        $sql = "SELECT * FROM factoressoat";
        $stmt = $this->_conexion->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Lista todos los salarios mínimos
     */
    function ListarSalarios() {
        $sql = "SELECT * FROM salariominimo ORDER BY IdSalarioMinimo DESC";
        $stmt = $this->_conexion->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function DocumentoMedicos($IdMedico) {
        $stmt = $this->_conexion->prepare("SELECT DocumentoMedico FROM medico WHERE IdMedico = :IdMedico");
        $stmt->bindValue(':IdMedico', $IdMedico);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function Especialidades() {
        $sql = "SELECT DISTINCT
            e.NombreEspecialidad
        FROM medico m
        INNER JOIN especialidad e ON m.IdEspecialidadMedico = e.IdEspecialidad
        INNER JOIN cirugias c ON (m.DocumentoMedico = c.DocumentoMedico OR m.DocumentoMedico = c.DocumentoMedicoAnes)
        WHERE 1=1;";
        $stmt = $this->_conexion->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    function EPS() {
        $sql = "SELECT DISTINCT
            c.EPS
        FROM medico m
        INNER JOIN especialidad e ON m.IdEspecialidadMedico = e.IdEspecialidad
        INNER JOIN cirugias c ON (m.DocumentoMedico = c.DocumentoMedico OR m.DocumentoMedico = c.DocumentoMedicoAnes)
        WHERE 1=1;";
        $stmt = $this->_conexion->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    
    function consultarCirugiasAPI($fecha_inicio, $fecha_fin, $DocumentoMedico = null) {
    $api_url = "http://186.117.157.246:8080/ApiTurnos/Honorarios/api_honorarios.php";
    $api_key = "6xp79W#=gb";
    
    $params = [
        'fecha_inicio' => $fecha_inicio,
        'fecha_fin' => $fecha_fin
    ];
    
    if ($DocumentoMedico) {
        $params['DocumentoMedico'] = $DocumentoMedico;
    }
    
    $url = $api_url . '?' . http_build_query($params);
    
    // Configurar cURL
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'X-API-Key: ' . $api_key,
            'Content-Type: application/json'
        ],
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSL_VERIFYPEER => false // Solo para testing, en producción usar true
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        return ['error' => 'Error cURL: ' . $error];
    }
    
    if ($http_code !== 200) {
        return ['error' => 'Error HTTP: ' . $http_code];
    }
    
    return json_decode($response, true);
}


}