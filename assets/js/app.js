// assets/js/app.js

document.addEventListener('DOMContentLoaded', () => {
    // Sandwich menu toggle
    const menuIcon = document.querySelector('.menu-icon');
    const navLinks = document.querySelector('.nav-links');
    if (menuIcon && navLinks) {
        menuIcon.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });
    }
});

// --- Dynamic Cart Logic ---
let cart = [];

function addToCart(productId, name, price, maxStock) {
    const existingItem = cart.find(item => item.id === productId);
    if (existingItem) {
        if (existingItem.quantity < maxStock) {
            existingItem.quantity += 1;
        } else {
            alert('Cannot add more than available stock.');
        }
    } else {
        if (maxStock > 0) {
            cart.push({ id: productId, name: name, price: price, quantity: 1, maxStock: maxStock });
        } else {
            alert('Out of stock.');
        }
    }
    renderCart();
}

function removeFromCart(productId) {
    cart = cart.filter(item => item.id !== productId);
    renderCart();
}

function renderCart() {
    const cartItemsDiv = document.getElementById('cart-items');
    const cartTotalSpan = document.getElementById('cart-total');
    
    if (!cartItemsDiv || !cartTotalSpan) return;
    
    cartItemsDiv.innerHTML = '';
    let total = 0;
    
    if (cart.length === 0) {
        cartItemsDiv.innerHTML = '<p style="color: #666; font-style: italic;">Your cart is empty.</p>';
    } else {
        cart.forEach(item => {
            total += item.price * item.quantity;
            const div = document.createElement('div');
            div.className = 'cart-item';
            div.innerHTML = `
                <div>
                    <strong>${item.name}</strong> (x${item.quantity})
                    <br><small>LKR ${item.price.toFixed(2)} each</small>
                </div>
                <div style="text-align: right;">
                    <strong>LKR ${(item.price * item.quantity).toFixed(2)}</strong>
                    <br>
                    <button class="btn btn-danger" style="padding: 0.2rem 0.5rem; font-size: 0.8rem; margin-top: 0.2rem;" onclick="removeFromCart(${item.id})">Remove</button>
                </div>
            `;
            cartItemsDiv.appendChild(div);
        });
    }
    
    cartTotalSpan.textContent = total.toFixed(2);
}

function checkout() {
    if (cart.length === 0) {
        alert('Cart is empty.');
        return;
    }
    
    const checkoutBtn = document.getElementById('checkout-btn');
    checkoutBtn.disabled = true;
    checkoutBtn.textContent = 'Processing...';
    
    fetch('api/orders.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ cart: cart })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Order placed successfully!');
            cart = [];
            renderCart();
            window.location.reload(); 
        } else {
            alert('Checkout failed: ' + (data.error || 'Unknown error'));
            checkoutBtn.disabled = false;
            checkoutBtn.textContent = 'Checkout';
        }
    })
    .catch(err => {
        console.error(err);
        alert('A network error occurred.');
        checkoutBtn.disabled = false;
        checkoutBtn.textContent = 'Checkout';
    });
}
