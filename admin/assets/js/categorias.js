document.addEventListener('DOMContentLoaded', function() {
    const tableBody = document.getElementById('categories-table-body');
    const loadingState = document.getElementById('loading-state');
    const emptyState = document.getElementById('empty-state');
    const btnNewCategory = document.getElementById('btnNewCategory');
    const modalCategory = new bootstrap.Modal(document.getElementById('modalCategory'));
    const formCategory = document.getElementById('formCategory');
    const categoryIdInput = document.getElementById('categoryId');
    const categoryNameInput = document.getElementById('categoryName');
    const modalTitle = document.getElementById('modalTitle');

    function fetchCategories() {
        loadingState.classList.remove('d-none');
        tableBody.innerHTML = '';
        emptyState.classList.add('d-none');

        fetch('api/categories.php')
            .then(res => res.json())
            .then(data => {
                loadingState.classList.add('d-none');
                if (data.success) {
                    if (data.categories.length === 0) {
                        emptyState.classList.remove('d-none');
                    } else {
                        renderCategories(data.categories);
                    }
                } else {
                    Notiflix.Notify.failure('Error al cargar categorías');
                }
            })
            .catch(err => {
                loadingState.classList.add('d-none');
                Notiflix.Notify.failure('Error de conexión');
            });
    }

    function renderCategories(categories) {
        tableBody.innerHTML = categories.map(cat => `
            <tr>
                <td class="ps-3 text-muted small">#${cat.id}</td>
                <td><span class="fw-semibold">${cat.nombre}</span></td>
                <td><span class="text-muted small">${new Date(cat.created_at).toLocaleDateString()}</span></td>
                <td class="text-end pe-3">
                    <button class="btn btn-sm btn-light rounded-pill me-2 btn-edit" data-id="${cat.id}" data-nombre="${cat.nombre}">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-light rounded-pill text-danger btn-delete" data-id="${cat.id}">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>
        `).join('');

        // Re-attach listeners
        document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.onclick = () => {
                modalTitle.innerText = 'Editar Categoría';
                categoryIdInput.value = btn.dataset.id;
                categoryNameInput.value = btn.dataset.nombre;
                modalCategory.show();
            };
        });

        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.onclick = () => {
                const id = btn.dataset.id;
                Notiflix.Confirm.show(
                    'Eliminar Categoría',
                    '¿Estás seguro de que deseas eliminar esta categoría?',
                    'Sí, eliminar',
                    'Cancelar',
                    () => deleteCategory(id)
                );
            };
        });
    }

    function deleteCategory(id) {
        fetch('api/categories.php?action=delete', {
            method: 'POST',
            body: JSON.stringify({ id: id }),
            headers: { 'Content-Type:': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Notiflix.Notify.success('Categoría eliminada');
                fetchCategories();
            } else {
                Notiflix.Notify.failure(data.message || 'Error al eliminar');
            }
        });
    }

    btnNewCategory.onclick = () => {
        modalTitle.innerText = 'Nueva Categoría';
        categoryIdInput.value = '';
        categoryNameInput.value = '';
        modalCategory.show();
    };

    formCategory.onsubmit = (e) => {
        e.preventDefault();
        const id = categoryIdInput.value;
        const nombre = categoryNameInput.value;
        const action = id ? 'update' : 'create';

        fetch(`api/categories.php?action=${action}`, {
            method: 'POST',
            body: JSON.stringify({ id, nombre }),
            headers: { 'Content-Type': 'application/json' }
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Notiflix.Notify.success(id ? 'Categoría actualizada' : 'Categoría creada');
                modalCategory.hide();
                fetchCategories();
            } else {
                Notiflix.Notify.failure(data.message || 'Error al guardar');
            }
        })
        .catch(() => Notiflix.Notify.failure('Error de conexión'));
    };

    fetchCategories();
});
