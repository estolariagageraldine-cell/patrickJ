const modal = document.getElementById('emailModal');
const modalImage = document.getElementById('modalImage');
const modalInput = document.getElementById('emailInput');
const modalForm = document.getElementById('emailForm');
const modalMessage = document.getElementById('modalMessage');
let selectedImage = '';

function openModal(imgElement) {
    selectedImage = imgElement.getAttribute('data-image') || imgElement.getAttribute('src').split('/').pop();
    modalImage.src = imgElement.src;
    modalImage.alt = imgElement.alt;
    modalInput.value = '';
    modalMessage.textContent = '';
    modal.classList.remove('hidden');
    modalInput.focus();
}

function closeModal() {
    modal.classList.add('hidden');
    modalForm.reset();
}

document.querySelectorAll('.imagenes img').forEach((img) => {
    img.style.cursor = 'pointer';
    img.addEventListener('click', () => openModal(img));
});

document.getElementById('closeModal').addEventListener('click', closeModal);
document.getElementById('cancelBtn').addEventListener('click', closeModal);

modal.addEventListener('click', (event) => {
    if (event.target === modal) closeModal();
});

modalForm.addEventListener('submit', async (event) => {
    event.preventDefault();

    const email = modalInput.value.trim();
    if (!email) {
        modalMessage.textContent = 'Debes ingresar un correo válido.';
        return;
    }

    modalMessage.textContent = 'Enviando...';
    const submitBtn = document.getElementById('submitBtn');
    submitBtn.disabled = true;

    try {
        const response = await fetch('enviar.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email, imagen: selectedImage })
        });

        const data = await response.json();
        modalMessage.textContent = data.mensaje || 'Ocurrió un error.';

        if (data.ok) {
            setTimeout(closeModal, 1200);
        }
    } catch (error) {
        modalMessage.textContent = 'No se pudo conectar con el servidor. Intenta nuevamente.';
    } finally {
        submitBtn.disabled = false;
    }
});