// admin/assets/js/products.js

document.addEventListener('DOMContentLoaded', () => {
    loadProducts();
    setupProductListeners();
});

let currentProducts = [];
let uploadModal = null;

function setupProductListeners() {
    // Search
    let debounceTimer;
    document.getElementById('searchProductInput').addEventListener('input', (e) => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(() => loadProducts(), 300);
    });

    // Logout
    const btnLogout = document.getElementById('btnLogout');
    if(btnLogout) {
         btnLogout.addEventListener('click', async () => {
            await fetch('api/auth.php', { method: 'POST', body: JSON.stringify({ action: 'logout' }) });
            window.location.href = 'login.php';
        });
    }
}

async function loadProducts() {
    const tbody = document.getElementById('products-table-body');
    const loadingEl = document.getElementById('products-loading');
    const emptyEl = document.getElementById('products-empty');

    tbody.innerHTML = '';
    loadingEl.classList.remove('d-none');
    emptyEl.classList.add('d-none');

    const search = document.getElementById('searchProductInput').value;
    const url = new URL('api/get_products.php', window.location.href);
    if (search) url.searchParams.append('search', search);

    try {
        const res = await fetch(url);
        const data = await res.json();

        loadingEl.classList.add('d-none');

        if (data.success) {
            currentProducts = data.products;
            if (data.products.length === 0) {
                emptyEl.classList.remove('d-none');
                return;
            }
            renderProducts(data.products);
        } else {
            showToast("Error cargando productos", "error");
        }
    } catch (e) {
        loadingEl.classList.add('d-none');
        showToast("Error de conexión", "error");
    }
}

function renderProducts(products) {
    const tbody = document.getElementById('products-table-body');
    let html = '';
    
    products.forEach(p => {
        html += `
            <tr class="animate-fade-in">
                <td class="ps-3">
                    <div class="fw-bold text-dark">${p.nombre}</div>
                </td>
                <td><span class="text-muted small font-monospace">${p.codigo}</span></td>
                <td class="text-center"><span class="badge bg-primary rounded-pill">${p.stock}</span></td>
                <td><span class="text-muted small">${p.categorias || '<em class="text-light-emphasis">Sin categoría</em>'}</span></td>
                <td class="text-end">$${parseFloat(p.precio_usd).toFixed(2)}</td>
                <td class="text-end">${parseFloat(p.precio_bs).toFixed(2)} Bs</td>
                <td class="text-end pe-3">
                    <button class="btn btn-light btn-sm text-dark btn-upload" data-id="${p.id}" data-nombre="${p.nombre.replace(/"/g, '&quot;')}" title="Cargar Imagen">
                        <i class="bi bi-image"></i>
                    </button>
                    <button class="btn btn-light btn-sm text-dark btn-associate" data-id="${p.id}" data-nombre="${p.nombre.replace(/"/g, '&quot;')}" title="Asociar Categorías">
                        <i class="bi bi-tag"></i>
                    </button>
                </td>
            </tr>
        `;
    });
    tbody.innerHTML = html;

    // Attach listeners for association
    document.querySelectorAll('.btn-associate').forEach(btn => {
        btn.onclick = () => openAssociateModal(btn.dataset.id, btn.dataset.nombre);
    });

    // Attach listeners for upload
    document.querySelectorAll('.btn-upload').forEach(btn => {
        btn.onclick = () => openUploadModal(btn.dataset.id, btn.dataset.nombre);
    });
}

// Category Association Logic
let assocModal = null;
window.openAssociateModal = async (id, name) => {
    document.getElementById('assocProductId').value = id;
    document.getElementById('assocProductName').innerText = name;
    const listContainer = document.getElementById('categoriesList');
    listContainer.innerHTML = '<div class="col-12 text-center py-3"><div class="spinner-border text-primary spinner-border-sm"></div></div>';
    
    assocModal = new bootstrap.Modal(document.getElementById('modalAssociateCategories'));
    assocModal.show();

    try {
        // Fetch all categories and product categories in parallel
        const [allRes, prodRes] = await Promise.all([
            fetch('api/categories.php'),
            fetch(`api/product_categories.php?id_producto=${id}`)
        ]);
        
        const allData = await allRes.json();
        const prodData = await prodRes.json();

        if (allData.success && prodData.success) {
            const currentCatIds = prodData.categories.map(c => parseInt(c.id));
            
            if (allData.categories.length === 0) {
                listContainer.innerHTML = '<div class="col-12 text-center text-muted py-3">No hay categorías creadas. Ve al módulo de Categorías primero.</div>';
                return;
            }

            listContainer.innerHTML = allData.categories.map(cat => `
                <div class="col-md-6">
                    <div class="form-check card-select p-2 border rounded">
                        <input class="form-check-input ms-0 me-2" type="checkbox" value="${cat.id}" id="cat${cat.id}" ${currentCatIds.includes(parseInt(cat.id)) ? 'checked' : ''}>
                        <label class="form-check-label small fw-semibold cursor-pointer" for="cat${cat.id}">
                            ${cat.nombre}
                        </label>
                    </div>
                </div>
            `).join('');
        }
    } catch (e) {
        listContainer.innerHTML = '<div class="col-12 text-center text-danger py-3">Error al cargar datos</div>';
    }
}

document.getElementById('btnSaveAssociations')?.addEventListener('click', async () => {
    const id = document.getElementById('assocProductId').value;
    const checkboxes = document.querySelectorAll('#categoriesList input[type="checkbox"]:checked');
    const categoryIds = Array.from(checkboxes).map(cb => parseInt(cb.value));

    const btn = document.getElementById('btnSaveAssociations');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Guardando...';

    try {
        const res = await fetch('api/product_categories.php?action=sync', {
            method: 'POST',
            body: JSON.stringify({ id_producto: id, category_ids: categoryIds }),
            headers: { 'Content-Type': 'application/json' }
        });
        const data = await res.json();
        
        if (data.success) {
            showToast("Categorías actualizadas", "success");
            assocModal.hide();
            loadProducts();
        } else {
            showToast(data.message || "Error al guardar", "error");
        }
    } catch (e) {
        showToast("Error de conexión", "error");
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
});

// Image Upload Logic
let uploadModalObj = null;
window.openUploadModal = (id, name) => {
    document.getElementById('uploadProductId').value = id;
    document.getElementById('uploadProductName').innerText = name;
    document.getElementById('inputProductImage').value = '';
    
    // Set preview to current image if exists (cache bust with timestamp)
    document.getElementById('imgUploadPreview').src = `../assets/img/stock/${id}.png?t=${Date.now()}`;
    
    // Handle preview error (if no image exists)
    document.getElementById('imgUploadPreview').onerror = function() {
        this.src = '../assets/img/no-images.png';
        this.onerror = null;
    };

    if(!uploadModalObj) {
        uploadModalObj = new bootstrap.Modal(document.getElementById('modalUploadImage'));
    }
    uploadModalObj.show();
}

// Preview image before upload
document.getElementById('inputProductImage')?.addEventListener('change', (e) => {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
            document.getElementById('imgUploadPreview').src = e.target.result;
        }
        reader.readAsDataURL(file);
    }
});

document.getElementById('btnDoUpload')?.addEventListener('click', async () => {
    const id = document.getElementById('uploadProductId').value;
    const fileInput = document.getElementById('inputProductImage');
    const file = fileInput.files[0];

    if (!file) {
        showToast("Selecciona una imagen primero", "error");
        return;
    }

    const btn = document.getElementById('btnDoUpload');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Subiendo...';

    const formData = new FormData();
    formData.append('id', id);
    formData.append('image', file);

    try {
        const url = new URL('api/upload_product_image.php', window.location.href);
        url.searchParams.append('id', id); // Redundancy for better server handling
        
        const res = await fetch(url, {
            method: 'POST',
            body: formData
        });
        
        // Try to parse as JSON, handle potential PHP errors/non-JSON response
        let data;
        const text = await res.text();
        console.log("Raw response from server:", text); // Debugging
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error("Non-JSON response:", text);
            showToast("Error del servidor (respuesta no válida)", "error");
            return;
        }

        if (data.success) {
            showToast("Imagen cargada con éxito", "success");
            uploadModalObj.hide();
            loadProducts(); // Refresh list to see change
        } else {
            showToast(data.message || "Error al subir imagen", "error");
        }
    } catch (e) {
        console.error("Upload error:", e);
        showToast("Error de conexión al subir", "error");
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalText;
    }
});



function showToast(msg, type = 'info') {
    Toastify({
        text: msg,
        duration: 3000,
        gravity: "top", 
        position: "right", 
        style: {
            background: type === 'success' ? "green" : (type === 'error' ? "red" : "blue"),
        }
    }).showToast();
}
