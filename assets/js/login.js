const loginForm = document.getElementById("loginForm");
const email = document.getElementById("email");
const password = document.getElementById("password");
const mensaje = document.getElementById("mensaje");
const btnLogin = document.getElementById("btnLogin");
const togglePassword = document.getElementById("togglePassword");
const toggleIcon = togglePassword.querySelector("i");

// Desplazamiento acumulado del boton cuando faltan campos
let moveX = 0;
let moveY = 0;

// Alterna entre mostrar y ocultar la contrasena
togglePassword.addEventListener("click", function () {
    const isPassword = password.type === "password";
    password.type = isPassword ? "text" : "password";
    toggleIcon.className = isPassword ? "fa-regular fa-eye-slash" : "fa-regular fa-eye";
});

function camposValidos() {
    return email.value.trim() !== "" && password.value.trim() !== "";
}

function obtenerMensajeCamposFaltantes() {
    const correoVacio = email.value.trim() === "";
    const passwordVacia = password.value.trim() === "";

    if (correoVacio && passwordVacia) {
        return "Debes escribir tu correo y contraseña.";
    }

    if (correoVacio) {
        return "Debes escribir tu correo electrónico.";
    }

    if (passwordVacia) {
        return "Debes escribir tu contraseña.";
    }

    return "";
}

function mostrarWarningCampos() {
    const texto = obtenerMensajeCamposFaltantes();
    mensaje.textContent = texto;
    mensaje.className = texto ? "mensaje warning" : "mensaje";
}

// Activa/desactiva el estado visual del boton segun validacion
function actualizarEstadoBoton() {
    if (camposValidos()) {
        btnLogin.classList.add("ready");
        btnLogin.classList.remove("locked");
        btnLogin.style.transform = "translate(0px, 0px)";
        moveX = 0;
        moveY = 0;
        mensaje.textContent = "";
        mensaje.className = "mensaje";
    } else {
        btnLogin.classList.remove("ready");
        btnLogin.classList.add("locked");
        mostrarWarningCampos();
    }
}

// Mueve el boton para incentivar completar los campos primero
function moverBoton() {
    const maxX = 90;
    const maxY = 55;

    moveX = (Math.random() * 2 - 1) * maxX;
    moveY = (Math.random() * 2 - 1) * maxY;

    btnLogin.style.transform = `translate(${moveX}px, ${moveY}px)`;
    mostrarWarningCampos();
}

// Si el cursor se acerca al boton bloqueado, este se mueve
btnLogin.addEventListener("mouseenter", function () {
    if (!camposValidos()) {
        moverBoton();
    }
});

btnLogin.addEventListener("click", function (e) {
    if (!camposValidos()) {
        e.preventDefault();
        moverBoton();
    }
});

document.addEventListener("mousemove", function (e) {
    if (camposValidos()) {
        return;
    }

    const rect = btnLogin.getBoundingClientRect();
    const distanciaX = e.clientX - (rect.left + rect.width / 3);
    const distanciaY = e.clientY - (rect.top + rect.height / 3);
    const distancia = Math.sqrt(distanciaX * distanciaX + distanciaY * distanciaY);

    if (distancia < 120) {
        moverBoton();
    }
});

// El boton reacciona cuando cambia el contenido de los campos
email.addEventListener("input", actualizarEstadoBoton);
password.addEventListener("input", actualizarEstadoBoton);

// Envia credenciales al endpoint real y muestra retroalimentacion al usuario
loginForm.addEventListener("submit", async function (e) {
    e.preventDefault();

    if (!camposValidos()) {
        mostrarWarningCampos();
        moverBoton();
        return;
    }

    mensaje.textContent = "";
    mensaje.className = "mensaje";
    btnLogin.classList.add("loading");
    btnLogin.style.transform = "translate(0px, 0px)";

    const formData = new FormData();
    formData.append("email", email.value.trim());
    formData.append("password", password.value.trim());

    try {
        const response = await fetch("api/login.php", {
            method: "POST",
            body: formData,
            credentials: "same-origin"
        });

        const data = await response.json();

        if (response.ok && data.ok) {
            mensaje.textContent = "Acceso correcto. Bienvenido a Koalicius.";
            mensaje.className = "mensaje success";

            setTimeout(() => {
                window.location.href = "index.php";
            }, 1000);
            return;
        }

        mensaje.textContent = data.error || "No se pudo iniciar sesión.";
        mensaje.className = "mensaje error";
    } catch (error) {
        mensaje.textContent = "Error de conexión con el servidor.";
        mensaje.className = "mensaje error";
    } finally {
        btnLogin.classList.remove("loading");
    }
});

// Estado inicial al cargar la pantalla
actualizarEstadoBoton();