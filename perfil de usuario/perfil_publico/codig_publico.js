
const btn = document.getElementById("btnSeguir");

btn.addEventListener("click", function() {
    if (!btn.classList.contains("siguiendo")) {
        btn.classList.add("siguiendo");
        btn.innerText = "Siguiendo";
    } else {
        btn.classList.remove("siguiendo");
        btn.innerText = "Seguir";
    }
});


