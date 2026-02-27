function abrirEditor() {
  document.getElementById("editorModal").style.display = "block";
}

function cerrarEditor() {
  document.getElementById("editorModal").style.display = "none";
}

function guardarCambios(event) {
  event.preventDefault();

  // Actualiza los datos del perfil
  document.getElementById("nombrePerfil").textContent = document.getElementById("nuevoNombre").value;
  document.getElementById("correoPerfil").textContent = document.getElementById("nuevoCorreo").value;
  document.getElementById("descripcionPerfil").textContent = document.getElementById("nuevaDescripcion").value;

  // Cierra el modal
  cerrarEditor();

  // Mensaje de confirmación
  alert("Perfil actualizado correctamente ✅");
}