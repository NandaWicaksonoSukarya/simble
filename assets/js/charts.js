// Chart untuk Pendaftaran Siswa
if (document.getElementById('studentChart')) {
    const ctx = document.getElementById('studentChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'],
            datasets: [{
                label: 'Jumlah Siswa Baru',
                data: [35, 42, 38, 50, 45, 35],
                borderColor: 'rgb(13, 110, 253)',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

// Chart untuk Status Pembayaran
if (document.getElementById('paymentChart')) {
    const ctx2 = document.getElementById('paymentChart').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['Lunas', 'Belum Lunas', 'Terlambat'],
            datasets: [{
                data: [180, 45, 20],
                backgroundColor: [
                    'rgb(25, 135, 84)',
                    'rgb(255, 193, 7)',
                    'rgb(220, 53, 69)'
                ]
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
}
