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
    }
}

// Mueve el boton para incentivar completar los campos primero
function moverBoton() {
    const maxX = 90;
    const maxY = 55;

    moveX = (Math.random() * 2 - 1) * maxX;
    moveY = (Math.random() * 2 - 1) * maxY;

    btnLogin.style.transform = `translate(${moveX}px, ${moveY}px)`;
    mensaje.textContent = "Primero escribe tu correo y contraseña.";
    mensaje.className = "mensaje error";
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

// Simula autenticacion y muestra retroalimentacion al usuario
loginForm.addEventListener("submit", function (e) {
    e.preventDefault();

    if (!camposValidos()) {
        moverBoton();
        return;
    }

    mensaje.textContent = "";
    mensaje.className = "mensaje";
    btnLogin.classList.add("loading");
    btnLogin.style.transform = "translate(0px, 0px)";

    const correoIngresado = email.value.trim();
    const passwordIngresada = password.value.trim();

    setTimeout(() => {
        if (correoIngresado === "admin@koalicius.com" && passwordIngresada === "1234") {
            mensaje.textContent = "Acceso correcto. Bienvenido a Koalicius.";
            mensaje.className = "mensaje success";

            setTimeout(() => {
                window.location.href = "home.html";
            }, 1200);
        } else {
            mensaje.textContent = "Correo o contraseña incorrectos.";
            mensaje.className = "mensaje error";
            btnLogin.classList.remove("loading");
        }
    }, 1200);
});

// Estado inicial al cargar la pantalla
actualizarEstadoBoton();