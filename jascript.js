const menuToggle = document.querySelector('.menu-toggle');
const navUl = document.querySelector('nav ul');
const inicioLink = document.querySelector('nav ul li:first-child a'); // Obtiene el enlace "Inicio"
menuToggle.addEventListener('click', () => {
    navUl.classList.toggle('active');
});
inicioLink.addEventListener('click', () => {
    navUl.classList.remove('active'); // Cierra el menú al hacer clic en "Inicio"
});