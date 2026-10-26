class Charts {
    // Gráficas del Dashboard
    static renderDistributionChart(data) {
        const ctx = document.getElementById('distribution-chart').getContext('2d');
        
        // Destruir gráfica anterior si existe
        if (this.distributionChart) {
            this.distributionChart.destroy();
        }
        
        const labels = data.map(item => item.calificacion);
        const values = data.map(item => item.total);
        
        this.distributionChart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: [
                        'rgba(231, 76, 60, 0.7)',
                        'rgba(60, 183, 231, 0.7)',
                        'rgba(46, 204, 113, 0.7)',
                        'rgba(149, 165, 166, 0.7)'
                    ],
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    static renderTrendChart(data) {
        const ctx = document.getElementById('trend-chart').getContext('2d');
        
        // Destruir gráfica anterior si existe
        if (this.trendChart) {
            this.trendChart.destroy();
        }
        
        const labels = data.map(item => item.mes);
        const values = data.map(item => item.promedio);
        
        this.trendChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Promedio de Calificación',
                    data: values,
                    borderColor: 'rgba(52, 152, 219, 1)',
                    backgroundColor: 'rgba(52, 152, 219, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 1,
                        max: 5,
                        ticks: {
                            stepSize: 0.5
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Promedio: ${context.raw.toFixed(2)}`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Gráficas del Detalle de Médico
static renderMedicoDistributionChart(data) {
    const canvas = document.getElementById('medico-distribution-chart');
    if (!canvas) {
        console.error('Error: No se encontró el elemento <canvas> con ID "medico-distribution-chart"');
        return;
    }
    const ctx = canvas.getContext('2d');
    
    // Destruir gráfica anterior si existe
    if (this.medicoDistributionChart) {
        this.medicoDistributionChart.destroy();
    }
    
    this.medicoDistributionChart = new Chart(ctx, {
        type: 'pie',
        data: data,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}
    
    static renderMedicoTrendChart(data) {
        const ctx = document.getElementById('medico-trend-chart').getContext('2d');
        
        // Destruir gráfica anterior si existe
        if (this.medicoTrendChart) {
            this.medicoTrendChart.destroy();
        }
        
        this.medicoTrendChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(item => item.mes),
                datasets: [{
                    label: 'Promedio por Evaluación',
                    data: data.map(item => item.promedio),
                    backgroundColor: 'rgba(52, 219, 163, 0.7)',
                    borderColor: 'rgba(52, 219, 122, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: false,
                        min: 1,
                        max: 5,
                        ticks: {
                            stepSize: 0.5
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return `Promedio: ${context.raw.toFixed(2)}`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Gráficas del Detalle de Formulario
    static renderFormularioDistributionChart(data) {
        const ctx = document.getElementById('formulario-distribution-chart').getContext('2d');
        
        // Destruir gráfica anterior si existe
        if (this.formularioDistributionChart) {
            this.formularioDistributionChart.destroy();
        }
        
        this.formularioDistributionChart = new Chart(ctx, {
            type: 'polarArea',
            data: data,
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw || 0;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    
static renderFormularioTrendChart(data) {
    const ctx = document.getElementById('formulario-trend-chart').getContext('2d');
    
    // Destruir gráfica anterior si existe
    if (this.formularioTrendChart) {
        this.formularioTrendChart.destroy();
    }
    
    // Invertir el orden de los datos para mostrar de menor a mayor (junio a septiembre)
    const reversedData = [...data].reverse();
    
    this.formularioTrendChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: reversedData.map(item => item.mes),
            datasets: [{
                label: 'Promedio Mensual',
                data: reversedData.map(item => item.promedio),
                borderColor: 'rgba(155, 89, 182, 1)',
                backgroundColor: 'rgba(155, 89, 182, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: false,
                    min: 1,
                    max: 5,
                    ticks: {
                        stepSize: 0.5
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `Promedio: ${context.raw.toFixed(2)}`;
                        }
                    }
                }
            }
        }
    });
}

// En ChartAdeherencia.js, agregar este método
static renderEspecialidadDistributionChart(data) {
    const canvas = document.getElementById('especialidad-distribution-chart');
    if (!canvas) {
        console.error('Error: No se encontró el elemento <canvas> con ID "especialidad-distribution-chart"');
        return;
    }
    const ctx = canvas.getContext('2d');
    
    // Destruir gráfica anterior si existe
    if (this.especialidadDistributionChart) {
        this.especialidadDistributionChart.destroy();
    }
    
    this.especialidadDistributionChart = new Chart(ctx, {
        type: 'pie',
        data: data,
        options: {
            responsive: true,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
}
}