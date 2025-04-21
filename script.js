const userBtn = document.getElementById('user-btn');
const userBox = document.getElementById('user-box');
const menuBtn = document.getElementById('menu-btn');
const navbar = document.getElementById('navbar');
userBtn.addEventListener('click', function (e) {
    e.stopPropagation();
    userBox.classList.toggle('active');
});
document.addEventListener('click', function (e) {
    if (!userBox.contains(e.target) && e.target !== userBtn) {
        userBox.classList.remove('active');
    }
});
menuBtn.addEventListener('click', function () {
     navbar.classList.toggle('active');
});
// close form
const closeBtn = document.querySelector('#close-edit');
closeBtn.addEventListener('click',()=>{
    document.querySelector('.update-container').style.display='none';
})
function showedit() {
    document.querySelector(".show-products").style.display = "none";
    document.querySelector(".update-container").style.display = "block";
}
// index header
const toggleButton = document.querySelector('.navbar-toggle');
const navList = document.querySelector('header nav ul');

toggleButton.addEventListener('click', () => {
    navList.classList.toggle('active');
    toggleButton.classList.toggle('active');
});
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
