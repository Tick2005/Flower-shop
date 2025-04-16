<?php
    include 'connection.php';
    session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="style.css">
  <title>Flower Shop Website</title>    
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>
  <header class="header bg-green-50 shadow-md sticky top-0 z-50">
    <div class="container mx-auto px-4 py-4 flex justify-between items-center">
      <!-- Logo and Contact Info (Left) -->
      <div class="flex items-center space-x-6">
        <a href="/" class="text-2xl font-bold text-green-600">Flower Shop</a>
        <div class="contact-info">
          <div class="flex items-center gap-1">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span>7:30 - 21:30</span>
          </div>
          <div class="flex items-center gap-1">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h2l1 7h12l1-7h2m-2 0a2 2 0 110 4 2 2 0 010-4zm-10 0a2 2 0 110 4 2 2 0 010-4zm-2 7h14l2 5H5l2-5z"></path>
            </svg>
            <span>0976491322</span>
          </div>
        </div>
      </div>

      <!-- Search Bar (Center) -->
      <div class="search-bar">
        <div class="relative">
          <input type="text" placeholder="Search flowers..." class="w-full pl-10 pr-4 py-2 rounded-full border border-gray-300 focus:outline-none focus:border-green-500 text-gray-700">
          <svg class="w-5 h-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
          </svg>
        </div>
      </div>

      <!-- Nav Links and Icons (Right) -->
      <nav class="nav-links flex space-x-6 items-center">
        <!-- Dropdown for Products -->
        <div class="relative dropdown">
          <a href="/products" class="text-gray-700 hover:text-green-500">Products</a>
          <div class="dropdown-menu hidden absolute bg-white shadow-lg rounded-md mt-2 w-48">
            <a href="/products/birthday" class="block px-4 py-2 text-gray-700 hover:bg-green-100">Birthday Flowers</a>
            <a href="/products/wedding" class="block px-4 py-2 text-gray-700 hover:bg-green-100">Wedding Flowers</a>
            <a href="/products/bouquet" class="block px-4 py-2 text-gray-700 hover:bg-green-100">Bouquets</a>
            <a href="/products/basket" class="block px-4 py-2 text-gray-700 hover:bg-green-100">Baskets</a>
          </div>
        </div>

        <a href="/about" class="text-gray-700 hover:text-green-500">About</a>

        <!-- User Icon -->
        <a href="/login" class="text-gray-700 hover:text-green-500">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
          </svg>
        </a>

        <!-- Cart Icon -->
        <a href="/cart" class="text-gray-700 hover:text-green-500 relative">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path>
          </svg>
          <span class="absolute -top-2 -right-2 bg-green-500 text-white text-xs rounded-full px-2">0</span>
        </a>

        <!-- Hamburger Menu for Mobile -->
        <button id="menu-toggle" class="md:hidden text-gray-700">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
          </svg>
        </button>
      </nav>
    </div>
  </header>

  <!-- Main Content -->
  <main>
    <div class="slider">
      <div class="slides">
        <!-- Slide 1 -->
        <div class="slide" style="background-image: url('image/flower5.jpg');">
          <div class="slide-content">
            <h2 class="text-green-500">Pink Rose Bouquet</h2>
            <h3 class="text-green-400">Spring - Summer 2025</h3>
            <p>A beautiful bouquet of pink roses, perfect for any occasion. Freshly picked and elegantly arranged to bring joy to your loved ones.</p>
          </div>
        </div>
        <!-- Slide 2 -->
        <div class="slide" style="background-image: url('image/flower14.jpg');">
          <div class="slide-content">
            <h2 class="text-green-500">White Flower Box</h2>
            <h3 class="text-green-400">Spring - Summer 2025</h3>
            <p>An elegant box of white flowers, designed to impress. Ideal for gifting or decorating your special events.</p>
          </div>
        </div>
        <!-- Slide 3 -->
        <div class="slide" style="background-image: url('image/flower16.jpg');">
          <div class="slide-content">
            <h2 class="text-green-500">Yellow & White Roses</h2>
            <h3 class="text-green-400">Spring - Summer 2025</h3>
            <p>A vibrant mix of yellow and white roses, symbolizing friendship and purity. Perfect for brightening someone's day.</p>
          </div>
        </div>
      </div>
      <!-- Slider Buttons -->
      <button class="slider-btn prev">❮</button>
      <button class="slider-btn next">❯</button>
    </div>

    <!-- Product Grid Section -->
    <section class="py-12 bg-gray-100">
      <div class="container mx-auto px-4">
        <h2 class="text-2xl font-bold text-white text-center bg-green-800 py-4 mb-8 rounded">BỘ HOA TƯƠI ĐẸP</h2>
        <div class="grid grid-cols-2 md:grid-cols-5 gap-6">
          <!-- Product 1 -->
          <div class="product-card">
            <div class="product-label">NEW</div>
            <img src="https://placehold.co/300x250" alt="Midnight Bloom" class="product-image">
            <div class="product-info">
              <h3 class="product-name">MIDNIGHT BLOOM</h3>
              <div>
                <span class="product-price-old">1,050,000đ</span>
                <span class="product-price-new">950,000đ</span>
              </div>
            </div>
          </div>
          <!-- Product 2 -->
          <div class="product-card">
            <div class="product-label">-17%</div>
            <img src="https://placehold.co/300x250" alt="Blissful Bouquet" class="product-image">
            <div class="product-info">
              <h3 class="product-name">BLISSFUL BOUQUET - BỘ HOA HẠNH PHÚC</h3>
              <div>
                <span class="product-price-old">480,000đ</span>
                <span class="product-price-new">400,000đ</span>
              </div>
            </div>
          </div>
          <!-- Product 3 -->
          <div class="product-card">
            <div class="product-label">-21%</div>
            <img src="https://placehold.co/300x250" alt="Hoa Bó Pink Whispers" class="product-image">
            <div class="product-info">
              <h3 class="product-name">HOA BÓ PINK WHISPERS</h3>
              <div>
                <span class="product-price-old">480,000đ</span>
                <span class="product-price-new">380,000đ</span>
              </div>
            </div>
          </div>
          <!-- Product 4 -->
          <div class="product-card">
            <div class="product-label">-18%</div>
            <img src="https://placehold.co/300x250" alt="Hydrangea Muse Bouquet" class="product-image">
            <div class="product-info">
              <h3 class="product-name">HYDRANGEA MUSE BOUQUET</h3>
              <div>
                <span class="product-price-old">550,000đ</span>
                <span class="product-price-new">450,000đ</span>
              </div>
            </div>
          </div>
          <!-- Product 5 -->
          <div class="product-card">
            <div class="product-label">-13%</div>
            <img src="https://placehold.co/300x250" alt="Bó Hoa Sinh Nhật Tặng Người Yêu Xinh" class="product-image">
            <div class="product-info">
              <h3 class="product-name">BÓ HOA SINH NHẬT TẶNG NGƯỜI YÊU XINH</h3>
              <div>
                <span class="product-price-old">480,000đ</span>
                <span class="product-price-new">420,000đ</span>
              </div>
            </div>
          </div>
          <!-- Product 6 -->
          <div class="product-card">
            <div class="product-label">-20%</div>
            <img src="https://placehold.co/300x250" alt="Bó hoa cầm tự câu Nắng Thơ" class="product-image">
            <div class="product-info">
              <h3 class="product-name">BÓ HOA CẦM TỰ CÂU NẮNG THƠ</h3>
              <div>
                <span class="product-price-old">350,000đ</span>
                <span class="product-price-new">280,000đ</span>
              </div>
            </div>
          </div>
          <!-- Product 7 -->
          <div class="product-card">
            <div class="product-label">-13%</div>
            <img src="https://placehold.co/300x250" alt="Bó Hoa Giá Rẻ Cầm Tự Câu Hàn Quốc" class="product-image">
            <div class="product-info">
              <h3 class="product-name">BÓ HOA GIÁ RẺ CẦM TỰ CÂU HÀN QUỐC</h3>
              <div>
                <span class="product-price-old">320,000đ</span>
                <span class="product-price-new">280,000đ</span>
              </div>
            </div>
          </div>
          <!-- Product 8 -->
          <div class="product-card">
            <div class="product-label">NEW</div>
            <img src="https://placehold.co/300x250" alt="Hoa Bó Sinh Nhật Amethyst Aura" class="product-image">
            <div class="product-info">
              <h3 class="product-name">HOA BÓ SINH NHẬT AMETHYST AURA</h3>
              <div>
                <span class="product-price-old">950,000đ</span>
                <span class="product-price-new">850,000đ</span>
              </div>
            </div>
          </div>
          <!-- Product 9 -->
          <div class="product-card">
            <div class="product-label">-5%</div>
            <img src="https://placehold.co/300x250" alt="Hoa Sinh Nhật Bó Hoa Hồng Shimmer Hạnh" class="product-image">
            <div class="product-info">
              <h3 class="product-name">HOA SINH NHẬT BÓ HOA HỒNG SHIMMER HẠNH</h3>
              <div>
                <span class="product-price-old">420,000đ</span>
                <span class="product-price-new">400,000đ</span>
              </div>
            </div>
          </div>
          <!-- Product 10 -->
          <div class="product-card">
            <div class="product-label">-14%</div>
            <img src="https://placehold.co/300x250" alt="Bó Hoa Hồng Love Daily Rose" class="product-image">
            <div class="product-info">
              <h3 class="product-name">BÓ HOA HỒNG LOVE DAILY ROSE</h3>
              <div>
                <span class="product-price-old">350,000đ</span>
                <span class="product-price-new">300,000đ</span>
              </div>
            </div>
          </div>
        </div>
        <!-- View More Button -->
        <a href="/products" class="view-more-btn">Xem thêm bó hoa</a>
      </div>
    </section>

  </main>

  <!-- Footer -->
  <footer class="bg-green-800 text-white py-8">
    <div class="container mx-auto px-4 grid grid-cols-1 md:grid-cols-3 gap-8">
      <!-- Contact Info -->
      <div>
        <h3 class="text-lg font-bold mb-4">Contact Us</h3>
        <p class="mb-2">Flower Shop</p>
        <p class="mb-2">123 Flower Street, City, Country</p>
        <p class="mb-2">Phone: +123 456 7890</p>
        <p>Email: support@flowershop.com</p>
      </div>

      <!-- Quick Links -->
      <div>
        <h3 class="text-lg font-bold mb-4">Quick Links</h3>
        <ul class="space-y-2">
          <li><a href="/products" class="hover:text-green-300">Products</a></li>
          <li><a href="/about" class="hover:text-green-300">About</a></li>
          <li><a href="/login" class="hover:text-green-300">Login/Register</a></li>
        </ul>
      </div>

      <!-- Social Media -->
      <div>
        <h3 class="text-lg font-bold mb-4">Follow Us</h3>
        <div class="flex space-x-4">
          <a href="https://facebook.com" class="hover:text-green-300">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path d="M9 8h-3v4h3v12h5v-12h3.642l.358-4h-4v-1.667c0-.955.192-1.333 1.115-1.333h2.885v-5h-3.808c-3.596 0-5.192 1.583-5.192 4.615v3.385z"/>
            </svg>
          </a>
          <a href="https://twitter.com" class="hover:text-green-300">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
            </svg>
          </a>
          <a href="https://instagram.com" class="hover:text-green-300">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.948-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
            </svg>
          </a>
        </div>
      </div>
    </div>
    <!-- Copyright -->
    <div class="border-t border-green-700 mt-8 pt-4 text-center">
      <p>© 2025 Flower Shop. All rights reserved.</p>
    </div>
  </footer>
  <script src="script.js"></script>
</body>
</html>
