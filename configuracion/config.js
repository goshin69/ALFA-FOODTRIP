// ========== GESTIÓN DE SECCIONES ==========
document.addEventListener('DOMContentLoaded', function() {
    const menuItems = document.querySelectorAll('.menu-item');
    const sections = document.querySelectorAll('.config-section');
    const logoutBtn = document.getElementById('logoutBtn');

    // Manejar cambio de secciones (Privacidad, Seguridad, Notificaciones)
    menuItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();

            const sectionId = this.getAttribute('data-section');

            // Remover clase activa de todos los items y secciones
            menuItems.forEach(m => m.classList.remove('active'));
            sections.forEach(s => s.classList.remove('active-section'));

            // Agregar clase activa al item y sección seleccionada
            this.classList.add('active');
            const activeSection = document.getElementById(sectionId);
            if (activeSection) {
                activeSection.classList.add('active-section');
            }
        });
    });

    // Manejar cierre de sesión
    logoutBtn.addEventListener('click', function() {
        // Confirmar cierre de sesión
        if (confirm('¿Estás seguro de que deseas cerrar sesión?')) {
            console.log('Cerrando sesión...');

            // Redirigir a la página de inicio o login
            window.location.href = '../index.php';

            // Alternativa si tienes un endpoint específico:
            // fetch('../logout.php', {method: 'POST'})
            //     .then(response => {
            //         if (response.ok) {
            //             window.location.href = '../index.php';
            //         }
            //     })
            //     .catch(error => console.error('Error al cerrar sesión:', error));
        }
    });

    // Manejar guardar cambios
    const saveBtns = document.querySelectorAll('.btn-save');
    saveBtns.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();

            const section = this.closest('.config-section');
            const sectionId = section.id;

            // Recopilar datos del formulario
            const formData = new FormData();
            const inputs = section.querySelectorAll('input, textarea');

            inputs.forEach(input => {
                if (input.type === 'checkbox') {
                    formData.append(input.id || input.name, input.checked);
                } else if (input.value) {
                    formData.append(input.id || input.name, input.value);
                }
            });

            console.log('Guardando cambios en sección:', sectionId);
            console.log('Datos:', Object.fromEntries(formData));

            // Mostrar feedback visual
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="fas fa-check"></i><span>¡Guardado!</span>';
            this.style.background = 'linear-gradient(135deg, #27ae60 0%, #229954 100%)';

            setTimeout(() => {
                this.innerHTML = originalText;
                this.style.background = '';
            }, 2000);

            // Aquí iría la llamada AJAX al servidor:
            // fetch('./actualizar_config.php', {
            //     method: 'POST',
            //     body: formData
            // })
            // .then(response => response.json())
            // .then(data => {
            //     if (data.success) {
            //         // Mostrar éxito
            //     }
            // })
            // .catch(error => console.error('Error:', error));
        });
    });
});
