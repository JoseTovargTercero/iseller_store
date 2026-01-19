const modalContainer = document.getElementById("modal-container");
const openModalButton = document.getElementById("open-modal");
const closeModalButton = document.getElementById("modal-close");
const modalOverlay = document.getElementById("modal-overlay");

// Abrir el modal solo si el botón existe
if (openModalButton) {
  openModalButton.addEventListener("click", () => {
    if (modalContainer) {
      modalContainer.classList.add("active");
    }
  });
}

// Cerrar el modal al hacer clic en el botón de cerrar
if (closeModalButton) {
  closeModalButton.addEventListener("click", () => {
    if (modalContainer) {
      modalContainer.classList.remove("active");
    }
  });
}

// Cerrar el modal al hacer clic en el overlay
if (modalOverlay) {
  modalOverlay.addEventListener("click", () => {
    if (modalContainer) {
      modalContainer.classList.remove("active");
    }
  });
}

const showModal = () => {
  if (modalContainer) {
    modalContainer.classList.add("active");
  }
};
