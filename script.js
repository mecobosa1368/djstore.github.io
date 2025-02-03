let cart = {};
let totalPrice = 0;

function toggleProducts(category) {
    const productsDiv = document.getElementById(category);
    const otherDivs = ['mixers', 'players', 'controllers', 'turntables', 'effects', 'headphones', 'cases', 'stands', 'software', 'cables', 'monitors', 'production', 'usb', 'tables', 'accessories', 'hearing'];
    
    const categoryTitle = document.getElementById("category-title");
    const priceFilter = document.getElementById("price-filter");
    
    const categoryNames = {
        mixers: "DJ Миксери",
        players: "DJ Плейъри",
        controllers: "DJ Контролери",
        turntables: "DJ Грамофони",
        effects: "DJ Ефектори",
        headphones: "DJ Слушалки",
        cases: "DJ Кейсове",
        stands: "DJ Стойки",
        software: "DJ Софтуер",
        cables: "DJ Кабели",
        monitors: "Мониторни Колони",
        production: "Музикално Продуциране",
        usb: "USB Flash / SD Card Памети",
        tables: "DJ Маси - Пултове",
        accessories: "Аксесоари / Резервни Части",
        hearing: "Защита на Слуха"
    };

    for (let div of otherDivs) {
        if (div !== category) {
            document.getElementById(div).style.display = 'none'; 
        }
    }

    if (productsDiv.style.display === "none" || productsDiv.style.display === "") {
        productsDiv.style.display = "flex";  
        categoryTitle.innerText = categoryNames[category]; 
        categoryTitle.style.display = "block"; 
        priceFilter.style.display = "block"; // Show price filter
        priceFilter.setAttribute("data-category", category); // Set category for filter
    } else {
        productsDiv.style.display = "none"; 
        categoryTitle.style.display = "none"; 
        priceFilter.style.display = "none"; // Hide price filter
    }
}

function addToCart(productName, price, quantity = 1) {
    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_name: productName,
            quantity: quantity,
            price: price
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log(data.message); 
                fetchCart(); 
            } else {
                console.error(data.message);
            }
        })
        .catch(error => {
            console.error('Hata:', error);
        });
}

function fetchCart() {
    fetch('get_cart.php', {
        method: 'GET',
        headers: { 'Content-Type': 'application/json' },
    })
        .then(response => response.json())
        .then(result => {
            if (result.success) {
                updateCartDisplay(result.cart_items);
            } else {
                console.error(`Hata: ${result.message}`);
            }
        })
        .catch(error => console.error('Hata:', error));
}

function updateCartDisplay(cartItems) {
    const cartItemsDiv = document.getElementById("cart-items");
    cartItemsDiv.innerHTML = ""; 
    let totalPrice = 0;

    cartItems.forEach(item => {
        const itemDiv = document.createElement("div");
        itemDiv.className = "cart-item";
        itemDiv.innerHTML = `
            <span class="item-name">${item.product_name}</span>
            <div class="quantity-controls">
                <input 
                    type="number" 
                    class="quantity-input" 
                    value="${item.quantity}" 
                    min="1" 
                    onchange="changeItemQuantity('${item.product_name}', this.value)">
            </div>
            <span class="item-price">${item.price * item.quantity}лв</span>
            <button class="remove-button" onclick="removeFromCart('${item.product_name}')">Изтрий</button>
        `;
        cartItemsDiv.appendChild(itemDiv);
        totalPrice += item.price * item.quantity;
    });

    document.getElementById("total").innerText = `Общо: ${totalPrice}лв`;
}

function updateQuantity(productName, change) {
    fetch('update_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            product_name: productName,
            change: change,
        }),
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                fetchCart(); 
            } else {
                console.error(data.message);
            }
        })
        .catch(error => console.error('Грешка:', error));
}

function changeItemQuantity(productName, quantity) {
    if (quantity < 1) {
        alert("Количестжото на продукт трябва да бъде най-малко 1.");
        return;
    }

    fetch('update_cart.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            product_name: productName,
            new_quantity: parseInt(quantity), 
        }),
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                fetchCart(); 
                console.log(data.message);
            } else {
                console.error(data.message);
            }
        })
        .catch(error => {
            console.error('Грешка:', error);
        });
}

document.addEventListener("DOMContentLoaded", () => {
    fetchCart(); 
});

function removeFromCart(productName) {
    fetch('update_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_name: productName,
            new_quantity: 0
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            fetchCart(); // Sepeti yeniden yükle
        } else {
            alert('Грешка: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Възникна грешка при премахване на продукта');
    });
}

function updateCart() {
    const cartItemsDiv = document.getElementById("cart-items");
    cartItemsDiv.innerHTML = ""; 
    totalPrice = 0;

    for (const product in cart) {
        const item = cart[product];
        const itemDiv = document.createElement("div");
        itemDiv.className = "cart-item";
        itemDiv.innerHTML = `
            ${product} - ${item.price}лв x 
            <button class="quantity-button" onclick="updateQuantity('${product}', -1)">-</button>
            <input type="text" class="quantity-input" value="${item.quantity}" readonly>
            <button class="quantity-button" onclick="updateQuantity('${product}', 1)">+</button>
            = ${item.price * item.quantity}лв
        `;
        cartItemsDiv.appendChild(itemDiv);
        totalPrice += item.price * item.quantity;
    }

    document.getElementById("total").innerText = `Общо: ${totalPrice}лв`;
}

function closeModal() {
    document.getElementById("productModal").style.display = "none";
}

function goToCategory(category) {
    const categoryDiv = document.getElementById(category);
    if (categoryDiv) {
        const otherDivs = Object.keys(categoryMap);
        otherDivs.forEach(div => {
            document.getElementById(div).style.display = 'none';
        });

        categoryDiv.style.display = "flex";

        const categoryTitle = document.getElementById("category-title");
        categoryTitle.innerText = category;
        categoryTitle.style.display = "block";
        
        
        closeModal();
    }
}

function changeQuantity(change) {
    const quantityInput = document.getElementById("quantity");
    let currentQuantity = parseInt(quantityInput.value);
    currentQuantity += change;

    
    if (currentQuantity < 1) {
        currentQuantity = 1;
    }

    quantityInput.value = currentQuantity; 
}

let modalRandomProducts = []; 
let modalCurrentStartIndex = 0; 
const modalItemsPerPage = 3; 
let shownModalProducts = []; 

function showModalRandomProducts() {
    copyModalRandomProducts(); 
    shuffleArray(modalRandomProducts); 
    updateModalDisplayedProducts("next"); 
}

function copyModalRandomProducts() {
    
    modalRandomProducts = allProducts.filter(product => !shownModalProducts.includes(product.name)); 
    if (modalRandomProducts.length === 0) {
        
        modalRandomProducts = [...allProducts];
        shownModalProducts = []; 
    }
}

function shuffleArray(array) {
    for (let i = array.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [array[i], array[j]] = [array[j], array[i]]; 
    }
    return array;
}

function updateModalDisplayedProducts(direction) {
    if (direction === "next") {
        modalCurrentStartIndex = (modalCurrentStartIndex + modalItemsPerPage) % modalRandomProducts.length;
    } else if (direction === "prev") {
        modalCurrentStartIndex = (modalCurrentStartIndex - modalItemsPerPage + modalRandomProducts.length) % modalRandomProducts.length;
    }
    const productsToShow = modalRandomProducts.slice(modalCurrentStartIndex, modalCurrentStartIndex + modalItemsPerPage);
    renderModalRandomProducts(productsToShow);
}

function renderModalRandomProducts(products) {
    const container = document.getElementById("modal-random-products");
    container.innerHTML = ""; 
    products.forEach(product => {
        const productDiv = document.createElement("div");
        productDiv.className = "product"; 
        productDiv.innerHTML = `
            <h2>${product.name}</h2>
            <img src="${product.image}" alt="${product.name}" style="max-width: 100%; height: auto;">
            <p>Цена: ${product.price}лв</p>
            <button class="go-button" onclick="goToCategory('${product.category}')">Виж</button>
        `;
        container.appendChild(productDiv); 
    });
}

document.addEventListener("DOMContentLoaded", function () {
    let isCartOpen = false;

    
    function toggleCart() {
        const cart = document.getElementById("cart");
        if (cart.style.display === "none" || cart.style.display === "") {
            cart.style.display = "block"; 
            isCartOpen = true;
        } else {
            cart.style.display = "none"; 
            isCartOpen = false;
        }
    }

    
    document.addEventListener("click", function (event) {
        const cart = document.getElementById("cart");
        const cartIcon = document.querySelector(".cart-icon");

        
        if (cart && cartIcon && !cart.contains(event.target) && !cartIcon.contains(event.target) && isCartOpen) {
            cart.style.display = "none"; 
            isCartOpen = false;
        }
    });

    
    const cartIcon = document.querySelector(".cart-icon");
    if (cartIcon) {
        cartIcon.addEventListener("click", toggleCart);
    }
});

let slider = document.querySelector('.slider .list');
let items = document.querySelectorAll('.slider .list .item');
let next = document.getElementById('next');
let prev = document.getElementById('prev');
let dots = document.querySelectorAll('.slider .dots li');

let lengthItems = items.length - 1;
let active = 0;

next.onclick = function (event) {
    event.stopPropagation(); 
    active = active + 1 <= lengthItems ? active + 1 : 0;
    reloadSlider();
}

prev.onclick = function (event) {
    event.stopPropagation(); 
    active = active - 1 >= 0 ? active - 1 : lengthItems;
    reloadSlider();
}

let refreshInterval = setInterval(() => { next.click() }, 3000);

function reloadSlider() {
    slider.style.left = -items[active].offsetLeft + 'px';

    let last_active_dot = document.querySelector('.slider .dots li.active');
    last_active_dot.classList.remove('active');
    dots[active].classList.add('active');

    clearInterval(refreshInterval);
    refreshInterval = setInterval(() => { next.click() }, 3000);
}

dots.forEach((li, key) => {
    li.addEventListener('click', (event) => {
        event.stopPropagation(); 
        active = key;
        reloadSlider();
    });
});

window.onresize = function (event) {
    reloadSlider();
};

function placeOrder() {
    window.location.href = "order.html";
}

function applyPriceFilter() {
    const maxPrice = parseFloat(document.getElementById("max-price").value);
    const category = document.getElementById("price-filter").getAttribute("data-category");
    const productsDiv = document.getElementById(category);
    const products = productsDiv.querySelectorAll('.product');

    products.forEach(product => {
        const price = parseFloat(product.querySelector('p').textContent.replace('Цена: ', '').replace('лв', ''));
        if (price <= maxPrice) {
            product.style.display = 'block';
        } else {
            product.style.display = 'none';
        }
    });
}

function applySortOrder() {
    const sortOrder = document.getElementById("sort-order").value;
    const category = document.getElementById("price-filter").getAttribute("data-category");
    const productsDiv = document.getElementById(category);
    const products = Array.from(productsDiv.querySelectorAll('.product'));

    products.sort((a, b) => {
        const priceA = parseFloat(a.querySelector('p').textContent.replace('Цена: ', '').replace('лв', ''));
        const priceB = parseFloat(b.querySelector('p').textContent.replace('Цена: ', '').replace('лв', ''));
        return sortOrder === 'asc' ? priceA - priceB : priceB - priceA;
    });

    products.forEach(product => {
        productsDiv.appendChild(product);
    });
}

function loadProducts() {
    fetch('get_products.php')
        .then(response => response.json())
        .then(data => {
            if (data) {
                const productsByCategory = {};
                
                data.forEach(product => {
                    if (!productsByCategory[product.category]) {
                        productsByCategory[product.category] = [];
                    }
                    productsByCategory[product.category].push(product);
                });

                Object.keys(productsByCategory).forEach(category => {
                    const container = document.getElementById(category);
                    if (container) {
                        container.innerHTML = productsByCategory[category].map(product => `
                            <div class="product">
                                <h2>${product.name}</h2>
                                <img src="${product.image_path}" alt="${product.name}">
                                <p>Цена: ${product.price}лв</p>
                                <button class="buy-button" onclick="openModal('${product.name}', ${product.price}, '${product.image_path}', '${product.description}')">Купи</button>
                            </div>
                        `).join('');
                    }
                });

                // Rastgele ürünler için
                showRandomProducts(data);
            }
        })
        .catch(error => console.error('Error:', error));
}

// Sayfa yüklendiğinde ürünleri yükle
document.addEventListener('DOMContentLoaded', loadProducts);

function showRandomProducts(allProducts) {
    if (!allProducts || allProducts.length === 0) return;
    
    const randomProducts = allProducts.sort(() => 0.5 - Math.random()).slice(0, 5);
    const container = document.getElementById("random-products");
    
    if (container) {
        container.innerHTML = randomProducts.map(product => `
            <div class="product">
                <h2>${product.name}</h2>
                <img src="${product.image_path}" alt="${product.name}">
                <p>Категория: ${product.category}</p>
                <button class="go-button" onclick="goToCategory('${product.category}')">Виж</button>
            </div>
        `).join('');
    }
}

function openModal(name, price, image, description) {
    document.getElementById("modalTitle").innerText = name;
    document.getElementById("modalImage").src = image;
    document.getElementById("modalDescription").innerText = description || 'Описание не е налично';
    document.getElementById("modalPrice").innerText = `Цена: ${price}лв`;
    document.getElementById("quantity").value = "1";
    
    window.selectedProduct = { 
        name: name, 
        price: price, 
        image: image,
        description: description
    };

    document.getElementById("productModal").style.display = "block";
    
    // Rastgele ürünler için
    fetch('get_products.php')
        .then(response => response.json())
        .then(data => {
            if (data) {
                const otherProducts = data.filter(p => p.name !== name);
                const randomProducts = otherProducts
                    .sort(() => 0.5 - Math.random())
                    .slice(0, 3);
                    
                const container = document.getElementById("modal-random-products");
                container.innerHTML = randomProducts.map(product => `
                    <div class="product">
                        <h2>${product.name}</h2>
                        <img src="${product.image_path}" alt="${product.name}">
                        <p>Цена: ${product.price}лв</p>
                        <button class="buy-button" onclick="openModal('${product.name}', ${product.price}, '${product.image_path}', '${product.description}')">
                            Виж
                        </button>
                    </div>
                `).join('');
            }
        });
}

function closeModal() {
    document.getElementById("productModal").style.display = "none";
}

// Modal dışına tıklandığında kapanması için
window.onclick = function(event) {
    const modal = document.getElementById("productModal");
    if (event.target == modal) {
        closeModal();
    }
}

function addToCartFromModal() {
    const productName = window.selectedProduct.name;
    const price = window.selectedProduct.price;
    const quantity = parseInt(document.getElementById("quantity").value);

    fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            product_name: productName,
            quantity: quantity,
            price: price
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Sepeti güncelle
            fetchCart();
            
            // Modalı kapat
            closeModal();
            
            // Cart'ı görünür yap
            document.getElementById("cart").style.display = "block";
            
            // Opsiyonel: Başarılı mesajı göster
            alert("Продуктът е добавен в кошницата!");
        } else {
            alert("Грешка: " + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert("Възникна грешка при добавяне на продукта в кошницата");
    });
}


