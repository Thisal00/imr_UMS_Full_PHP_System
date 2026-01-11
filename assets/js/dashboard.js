

document.addEventListener("DOMContentLoaded", () => {

    // Monthly Revenue
    if (document.getElementById('revChart')) {
        new Chart(document.getElementById('revChart'), {
            type: 'line',
            data: {
                labels: window.revLabels,
                datasets: [{
                    label: "Revenue (LKR)",
                    data: window.revData,
                    borderColor: "#2563eb",
                    backgroundColor: "rgba(37,99,235,0.15)",
                    borderWidth: 3,
                    pointRadius: 4,
                    tension: 0.4
                }]
            },
            options: { responsive: true }
        });
    }

    // Unpaid Bills – Donut
    if (document.getElementById('donutChart')) {
        new Chart(document.getElementById('donutChart'), {
            type: 'doughnut',
            data: {
                labels: window.tariffLabels,
                datasets: [{
                    data: window.tariffData,
                    backgroundColor: ['#3b82f6', '#ef4444', '#f59e0b', '#10b981'],
                }]
            }
        });
    }

    // Customer Growth
    if (document.getElementById('custChart')) {
        new Chart(document.getElementById('custChart'), {
            type: 'bar',
            data: {
                labels: window.custLabels,
                datasets: [{
                    label: "New Customers",
                    data: window.custData,
                    backgroundColor: "#10b981"
                }]
            },
            options: { responsive: true }
        });
    }
});
