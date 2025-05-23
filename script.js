document.addEventListener('DOMContentLoaded', () => {
    // Slider Functionality
    const slides = document.querySelector('.slides');
    const slideElements = document.querySelectorAll('.slide');
    const prevBtn = document.querySelector('.prev');
    const nextBtn = document.querySelector('.next');
    let currentSlide = 0;

    function showSlide(index) {
        if (!slides || slideElements.length === 0) return;
        if (index >= slideElements.length) index = 0;
        if (index < 0) index = slideElements.length - 1;
        slides.style.transform = `translateX(-${index * 100}%)`;
        currentSlide = index;
    }

    // Initialize slider
    showSlide(currentSlide);

    // Event listeners for buttons
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            showSlide(currentSlide - 1);
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            showSlide(currentSlide + 1);
        });
    }

    // Auto-slide every 5 seconds
    setInterval(() => {
        showSlide(currentSlide + 1);
    }, 5000);

    // Navbar Menu Toggle
    const menuToggle = document.getElementById('menu-toggle');
    const navLinks = document.querySelector('.nav-links');
    if (menuToggle && navLinks) {
        menuToggle.addEventListener('click', () => {
            navLinks.classList.toggle('active');
        });
    }

    // Other functionality (safeguarded to avoid errors)
    const userBtn = document.getElementById('user-btn');
    const userBox = document.getElementById('user-box');
    if (userBtn && userBox) {
        userBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            userBox.classList.toggle('active');
        });
        document.addEventListener('click', function (e) {
            if (!userBox.contains(e.target) && e.target !== userBtn) {
                userBox.classList.remove('active');
            }
        });
    }

    const menuBtn = document.getElementById('menu-btn');
    const navbar = document.getElementById('navbar');
    if (menuBtn && navbar) {
        menuBtn.addEventListener('click', function () {
            navbar.classList.toggle('active');
        });
    }

    const closeBtn = document.querySelector('#close-edit');
    const updateContainer = document.querySelector('.update-container');
    if (closeBtn && updateContainer) {
        closeBtn.addEventListener('click', () => {
            updateContainer.style.display = 'none';
        });
    }

    function showedit() {
        const showProducts = document.querySelector('.show-products');
        if (showProducts && updateContainer) {
            showProducts.style.display = 'none';
            updateContainer.style.display = 'block';
        }
    }

    // Expose showedit to global scope if needed
    window.showedit = showedit;

    const toggleButton = document.querySelector('.navbar-toggle');
    const navList = document.querySelector('header nav ul');
    if (toggleButton && navList) {
        toggleButton.addEventListener('click', () => {
            navList.classList.toggle('active');
            toggleButton.classList.toggle('active');
        });
    }
});
