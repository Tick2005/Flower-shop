// Navbar Menu Toggle
const menuToggle = document.getElementById('menu-toggle');
const navLinks = document.querySelector('.nav-links');
menuToggle.addEventListener('click', () => {
    navLinks.classList.toggle('active');
});

// Slider Functionality
const slides = document.querySelector('.slides');
const slideElements = document.querySelectorAll('.slide');
const prevBtn = document.querySelector('.prev');
const nextBtn = document.querySelector('.next');
let currentSlide = 0;

function showSlide(index) {
    if (index >= slideElements.length) index = 0;
    if (index < 0) index = slideElements.length - 1;
    slides.style.transform = `translateX(-${index * 100}%)`;
    currentSlide = index;
}

prevBtn.addEventListener('click', () => {
    showSlide(currentSlide - 1);
});

nextBtn.addEventListener('click', () => {
    showSlide(currentSlide + 1);
});

// Auto-slide every 5 seconds
setInterval(() => {
    showSlide(currentSlide + 1);
}, 5000);
