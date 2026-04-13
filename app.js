const contenedor = document.getElementById('contenedor');
//console.log(contenedor.tagName);

//const titulo = document.getElementById('titulo');
//console.log(typeof titulo);

//const toppings = document.getElementsByClassName('topping');
//console.log(toppings[3]);

// const eti = document.getElementsByTagName('li');
// //console.log(eti);

// const fondoMarron = document.getElementsByClassName('fondo-marron');
// //console.log(fondoMarron);

// const primerToppinNaranja = document.querySelector('ul li:not(.fondo-marron)');
// //console.log(primerToppinNaranja);

// const toppingsNaranja = document.querySelectorAll('.topping.fondo-naranja');
//console.log(toppingsNaranja[0]);
//console.log(toppingsNaranja[1]);

//const primerTopping = document.querySelector('.topping');
//console.log(primerTopping.style);

// primerTopping.style.backgroundColor = 'blue';
// primerTopping.style.color = '#6dff00';
// primerTopping.style.textTransform = 'uppercase';

//const listaDeToppings = document.getElementById('lista-toppings');
//console.log(listaDeToppings);

// console.log('> innerText');
// console.log(listaDeToppings.innerText);

// console.log('> textContent');
// console.log(listaDeToppings.textContent);

// console.log('> innerHTML');
// console.log(listaDeToppings.innerHTML);

//const titulo = document.getElementById('titulo');
//console.log(titulo.innerText);

//titulo.innerText = 'Mis Toppings Favoritos';

// const enlaces = document.getElementsByTagName('a');
// console.log(enlaces[0].getAttribute('href'));
// console.log(enlaces[0].setAttribute('href', 'https://www.freecodecamp.org'));

//console.log(enlaces[0].removeAttribute('href'));
const primerTopping = document.querySelector('.topping');
primerTopping.classList.add('texto-verde');
//primerTopping.classList.remove('topping');
console.log(primerTopping.classList.contains('fondo-azul'));
console.log(primerTopping.classList);
//referencia de la lista de topping
const listaDeToppings = document.getElementById('lista-toppings');

console.log(listaDeToppings.parentElement);

const toppingNuevo = document.createElement('li');
toppingNuevo.classList.add('topping', 'fondo-marron');
toppingNuevo.innerText = 'Queso Extra';
listaDeToppings.append(toppingNuevo);
toppingNuevo.remove();



