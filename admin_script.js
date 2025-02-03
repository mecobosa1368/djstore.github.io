function deleteProduct(id) {
    if (confirm('Bu ürünü silmek istediğinizden emin misiniz?')) {
        const formData = new FormData();
        formData.append('id', id);
        
        fetch('delete_product.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
               
                fetch('get_products.php')
                    .then(response => response.json())
                    .then(products => {
                        localStorage.setItem('products', JSON.stringify(products));
                        
                        if (window.opener && !window.opener.closed) {
                            window.opener.location.reload();
                        }
                        
                        location.reload();
                    });
            } else {
                alert('Hata: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
    }
}

function updateStatistics() {
    fetch('admin_handler.php?action=get_statistics')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                Object.keys(data.data).forEach(key => {
                    const element = document.getElementById(key);
                    if (element) {
                        element.textContent = data.data[key];
                    }
                });
            }
        })
        .catch(error => console.error('Error:', error));
}

function editUser(userId) {
    fetch(`admin_handler.php?action=get_user&user_id=${userId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const user = data.user;
                document.getElementById('edit_user_id').value = user.id;
                document.getElementById('edit_username').value = user.username;
                document.getElementById('edit_email').value = user.email;
                document.getElementById('edit_role').value = user.role;
                document.getElementById('editUserModal').style.display = 'block';
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => console.error('Error:', error));
}

function updateUser() {
    const formData = new FormData(document.getElementById('editUserForm'));
    
    fetch('admin_handler.php?action=update_user', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('editUserModal').style.display = 'none';
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => console.error('Error:', error));
}

function deleteUser(userId) {
    if (confirm('Сигурни ли сте, че искате да изтриете този потребител?')) {
        const formData = new FormData();
        formData.append('user_id', userId);

        fetch('admin_handler.php?action=delete_user', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelector(`tr[data-user-id="${userId}"]`).remove();
                updateStatistics();
            } else {
                alert('Възникна грешка при изтриването на потребителя');
            }
        });
    }
}

function deleteMessage(messageId) {
    if (confirm('Сигурни ли сте, че искате да изтриете това съобщение?')) {
        const formData = new FormData();
        formData.append('message_id', messageId);

        fetch('admin_handler.php?action=delete_message', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                document.querySelector(`div[data-message-id="${messageId}"]`).remove();
                updateStatistics();
            } else {
                alert('Възникна грешка при изтриването на съобщението');
            }
        });
    }
}

function closeEditModal() {
    const modal = document.querySelector('.edit-modal');
    if (modal) {
        modal.remove();
    }
}

function checkSession() {
    fetch('check_session.php')
        .then(response => response.json())
        .then(data => {
            if (!data.loggedIn) {
                window.location.href = 'nacstr.html';
            }
        })
        .catch(error => console.error('Error:', error));
}

function showOrderDetails(orderId) {
    fetch(`admin_handler.php?action=get_order_details&order_id=${orderId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const modal = document.getElementById('orderModal');
                const details = document.getElementById('orderDetails');
                
                // Parse cart_items string to JSON
                const cartItems = JSON.parse(data.order.cart_items);
                let orderItems = '';
                cartItems.forEach(item => {
                    orderItems += `
                        <tr>
                            <td>${item.product_name}</td>
                            <td>${item.quantity}</td>
                            <td>${item.price} лв</td>
                            <td>${(item.quantity * item.price).toFixed(2)} лв</td>
                        </tr>
                    `;
                });

                details.innerHTML = `
                    <h2>Поръчки #${data.order.id}</h2>
                    <div class="order-info">
                        <p><strong>Клиент:</strong> ${data.order.name}</p>
                        <p><strong>Email:</strong> ${data.order.email}</p>
                        <p><strong>Телефон:</strong> ${data.order.phone}</p>
                        <p><strong>Адрес:</strong> ${data.order.address}</p>
                        <p><strong>Дата:</strong> ${new Date(data.order.created_at).toLocaleString()}</p>
                        <p><strong>Ситуация:</strong> ${data.order.status}</p>
                    </div>
                    <table class="order-items">
                        <thead>
                            <tr>
                                <th>Продукт</th>
                                <th>Сума</th>
                                <th>Единична цена</th>
                                <th>Общо</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${orderItems}
                        </tbody>
                    </table>
                    <div class="order-total">
                        <p><strong>Обща сума:</strong> ${data.order.total_price} лв</p>
                    </div>
                `;
                
                modal.style.display = 'block';
            }
        })
        .catch(error => console.error('Error:', error));
}

document.addEventListener('DOMContentLoaded', function() {
    // Navigasyon işlemleri
    document.querySelectorAll('.nav-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const section = this.getAttribute('data-section');
            showSection(section);
        });
    });

    // İlk yükleme ve periyodik güncelleme
    updateStatistics();
    setInterval(updateStatistics, 30000);

    // Tarih filtrelerini ayarla
    const startDateInput = document.getElementById('startDate');
    const endDateInput = document.getElementById('endDate');
    
    if (startDateInput && endDateInput) {
        const today = new Date().toISOString().split('T')[0];
        startDateInput.value = today;
        endDateInput.value = today;
    }

    const modal = document.getElementById('editUserModal');
    const span = document.getElementsByClassName('close')[0];
    
    span.onclick = function() {
        modal.style.display = 'none';
    }
    
    window.onclick = function(event) {
        if (event.target == modal) {
            modal.style.display = 'none';
        }
    }
    
    document.getElementById('editUserForm').onsubmit = function(e) {
        e.preventDefault();
        updateUser();
    }

    // Add product form handler
    const addProductForm = document.getElementById('addProductForm');
    if (addProductForm) {
        addProductForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('admin_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    alert('Продукт успешно добавен!');
                    location.reload();
                } else {
                    alert('Грешка: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Възникна грешка при добавянето на продукта');
            });
        });
    }

    // Edit product form handler - null check ekleyelim
    const editProductForm = document.getElementById('editProductForm');
    if (editProductForm) {
        editProductForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            formData.append('action', 'edit_product');
            
            fetch('admin_handler.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Ürünleri yeniden yükle
                    fetch('get_products.php')
                        .then(response => response.json())
                        .then(products => {
                            // LocalStorage'i güncelle
                            localStorage.setItem('products', JSON.stringify(products));
                            
                            // Ana sayfayı yenile (eğer açıksa)
                            if (window.opener && !window.opener.closed) {
                                window.opener.postMessage('refreshProducts', '*');
                            }
                            
                            alert(data.message);
                            document.getElementById('editProductModal').style.display = 'none';
                            location.reload(); // Admin panelini yenile
                        })
                        .catch(error => {
                            console.error('Грешка при извличане на продукти:', error);
                            alert('Възникна грешка при актуализирането на продуктите');
                        });
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        });
    }

    checkSession();
    // fetchCart(); // Bu satırı kaldırıyoruz çünkü admin panelde sepet yok
});

function editProduct(productId) {
    fetch(`admin_handler.php?action=get_product&product_id=${productId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const product = data.product;
                
                // Form alanlarını doldur
                document.getElementById('edit_product_id').value = product.id;
                document.getElementById('edit_product_name').value = product.name;
                document.getElementById('edit_product_price').value = product.price;
                document.getElementById('edit_product_description').value = product.description;
                document.getElementById('edit_product_category').value = product.category;
                
                // Mevcut resmi göster
                const currentImage = document.getElementById('current_product_image');
                if (currentImage) {
                    currentImage.src = product.image_path;
                }
                
                // Modalı göster
                document.getElementById('editProductModal').style.display = 'block';
            } else {
                alert('Грешка при инсталирането на продукта: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Възникна грешка при инсталирането на продукта');
        });
}
// Modal kapatma fonksiyonu
function closeModal() {
    document.getElementById('editProductModal').style.display = 'none';
}

// Event listener'ları ekle
document.addEventListener('DOMContentLoaded', function() {
    // X işaretine tıklama
    document.querySelector('.close').addEventListener('click', closeModal);

    // Modal dışına tıklama
    window.addEventListener('click', function(event) {
        if (event.target == document.getElementById('editProductModal')) {
            closeModal();
        }
    });
});