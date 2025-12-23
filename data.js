// Chung & Afiq's Cart Logic
let cart = JSON.parse(localStorage.getItem('uniMerchCart')) || [];

function addToCart(productId) {
    // 1. Find the product details from the page
    const productCard = document.getElementById(`product-${productId}`);
    
    // 2. Create the item object
    const item = {
        id: productId,
        name: productCard.querySelector('.product-name').innerText,
        price: productCard.querySelector('.product-price').innerText,
        quantity: 1
    };

    // 3. Add to our cart array
    cart.push(item);
    
    // 4. Save to LocalStorage (This is a project requirement!)
    localStorage.setItem('uniMerchCart', JSON.stringify(cart));
    
    // 5. Update the number on the red bubble
    updateCartCount();
    
    alert(item.name + " added to cart!");
}

function updateCartCount() {
    const countElement = document.querySelector('.cart-count') || document.querySelector('span[style*="background-color: red"]');
    if (countElement) {
        countElement.innerText = cart.length;
    }
}

// ========================================
// Profile Page - Order History Logic
// ========================================

/**
 * Placeholder for loading user orders from the database
 * This will be populated later with actual order data from the backend
 */
function loadOrders() {
    // Fetch orders from server (session-based). Returns JSON array.
    fetch('fetch_orders.php', { credentials: 'same-origin' })
        .then(res => res.json())
        .then(orders => displayOrders(orders))
        .catch(error => {
            console.error('Error loading orders:', error);
        });
}

/**
 * Display orders in the transaction list
 * @param {Array} orders - Array of order objects from the database
 */
function displayOrders(orders) {
    const container = document.getElementById('transactionsList');
    
    if (!container) return;
    
    if (orders.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <p style="font-size: 14px;">📦</p>
                <p>No orders yet</p>
                <p style="font-size: 12px;">Your past orders and transactions will appear here.</p>
            </div>
        `;
        return;
    }
    
    container.innerHTML = orders.map(order => `
        <div class="transaction-item">
            <div class="transaction-info">
                <h4>Order #${order.orderID}</h4>
                <div class="transaction-date">${new Date(order.orderDate).toLocaleDateString()}</div>
            </div>
            <div class="transaction-amount">RM ${parseFloat(order.totalAmount).toFixed(2)}</div>
            <div style="margin-left:12px;"><a href="orderdetails.php?order=${encodeURIComponent(order.orderID)}">View details</a></div>
        </div>
    `).join('');
}

// Initialize profile page when loaded
window.addEventListener('load', function() {
    if (document.getElementById('transactionsList')) {
        loadOrders();
    }
});