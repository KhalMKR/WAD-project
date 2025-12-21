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