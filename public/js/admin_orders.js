document.addEventListener('DOMContentLoaded', () => {
    loadOrders();
});

function loadOrders() {
    fetch('/project/api/admin.php?action=list_orders')
        .then(res => res.json())
        .then(data => {
            const tbody = document.querySelector('#ordersTable tbody');
            tbody.innerHTML = '';

            if (!data.orders || data.orders.length === 0) {
                tbody.innerHTML = `<tr><td colspan="6">沒有訂單</td></tr>`;
                return;
            }

            data.orders.forEach(order => {
                tbody.innerHTML += `
                   <tr>
                        <td>${order.order_id}</td>
                        <td>${order.email}</td>
                        <td>${order.status}</td>
                        <td>$${Number(order.total_amount).toFixed(2)}</td>
                        <td>${order.created_at}</td>
                    </tr>
                `;
            });
        })
        .catch(err => {
            console.error('載入訂單失敗:', err);
        });
}