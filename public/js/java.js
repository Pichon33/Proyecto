// Acceso y registro comparten la opción de mostrar la contraseña.
document.addEventListener('DOMContentLoaded', function () {
    const mostrar = document.getElementById('mostrarContrasenas');
    if (mostrar) {
        mostrar.parentElement.hidden = false;
        mostrar.addEventListener('change', function () {
            document.querySelectorAll('[data-contrasena]').forEach(function (campo) {
                campo.type = mostrar.checked ? 'text' : 'password';
            });
        });
    }

    const error = document.getElementById('error-formulario');
    if (error) error.focus();
});

document.addEventListener('DOMContentLoaded', function () {
    const avatarInput = document.getElementById('avatarInput');
    let avatarPreview = document.getElementById('avatarPreview');
    const form = document.getElementById('profileForm');
    const editButton = document.getElementById('editProfile');
    const saveButton = document.getElementById('saveProfile');
    const cancelButton = document.getElementById('cancelProfile');
    const nameLabel = document.getElementById('profileNameLabel');

    if (!form || !editButton || !saveButton) return;

    const fields = form.querySelectorAll('input:not([type="file"]), select');
    const initialValues = [];
    fields.forEach(function (field) {
        initialValues.push(field.value);
    });

    function setEditing(editing) {
        fields.forEach(function (field) {
            field.disabled = !editing;
        });

        editButton.hidden = editing;
        saveButton.hidden = !editing;
        cancelButton.hidden = !editing;
    }

    editButton.addEventListener('click', function () {
        setEditing(true);
        fields[0].focus();
    });

    cancelButton.addEventListener('click', function () {
        fields.forEach(function (field, index) {
            field.value = initialValues[index];
        });

        avatarInput.value = '';
        setEditing(false);
    });

    avatarInput.addEventListener('change', function (event) {
        const image = event.target.files[0];
        if (!image) return;

        setEditing(true);

        const reader = new FileReader();
        reader.onload = function () {
            if (avatarPreview.tagName === 'DIV') {
                const photo = document.createElement('img');
                photo.id = 'avatarPreview';
                photo.className = 'avatar-perfil';
                photo.alt = 'Vista previa de la foto de perfil';
                avatarPreview.replaceWith(photo);
                avatarPreview = photo;
            }

            avatarPreview.src = reader.result;
        };
        reader.readAsDataURL(image);
    });

    form.addEventListener('submit', function () {
        const inputName = form.querySelector('[name="nombre"]');
        if (inputName.value.trim() !== '') {
            nameLabel.textContent = inputName.value.trim();
        }
    });

    setEditing(false);
});
