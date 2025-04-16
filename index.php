<?php
    include 'connection.php';
    session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <title>Flora & Life</title>
    <style>
        header {
      background-color: #436B46;
      width: 100%;
      padding: 2rem 0;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      position: sticky;
      top: 0;
      z-index: 1000;
    }
    .flex {
      max-width: 1200px;
      margin: 0 auto;
      display: flex;
      justify-content: space-around;
      align-items: center;
    }
    .logo {
      font-size: 2rem;
      font-weight: bold;
      color: #aa4ff3;
      text-decoration: none;
    }
    .logo span {
      color: #e382c5;
    }
    .navbar a {
      margin: 0 1rem;
      font-size: 1rem;
      color: #fff;
      text-transform: uppercase;
      text-decoration: none;
    }
    .navbar a:hover,
    .icons i:hover {
      color: #c7b3f5;
    }
    .icons {
      display: flex;
      align-items: center;
    }
    .icons i {
      margin-left: 1.2rem;
      font-size: 1.5rem;
      cursor: pointer;
      color: #fff;
    }
    .icons i:last-child {
      font-size: 1rem;
    }
    #menu-btn {
      display: none;
    }
    .user-box {
      position: absolute;
      top: 120%;
      right: 10%;
      background: #fff;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      border-radius: 5px;
      padding: 1rem;
      display: none;
    }
    .user-box.active {
      display: block;
    }
    .user-box a{
        color: #186d3c;
        display: flex;
        border-top: 2px solid #558759;
    }
        #home {
            background-image: url(image/background3.webp);
            background-size: cover;
            background-position: center;
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: center;
            padding-right: 5%;
            text-align: justify;
            color:rgb(235, 235, 235);
        }

        #home p {
            width: 45%;
        }

        #home button {
            margin-bottom: 15px;
        }

        .about-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
            padding: 20px;
        }

        .about-container video {
            width: 50%;
            border-radius: 10px;
        }

        .about-text {
            width: 40%;
            text-align: left;
        }

        button {
            background: black;
            color: white;
            font-weight: bold;
            padding: 10px;
            border-radius: 5px;
            border: none;
            cursor: pointer;
            margin-top: 10px;
            transition: background 0.3s ease;
        }

        button:hover {
            background: #558759;
        }

        body h2 {
            text-align: center;
            padding: 1rem;
            background: rgb(141, 221, 148);
        }

        body h2 span {
            color: #aa4ff3;
        }

        @media (max-width: 1050px) {
            #about .about-container video {
                width: 90%;
            }

            .about-text {
                width: 90%;
                text-align: justify;
            }
            #menu-btn {
        display: block;
      }
      .navbar {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background:rgb(208, 245, 211);
        display: none;
        flex-direction: row;
        border-top: 2px solid #aa4ff3;
        padding: 1rem 0;
        width: 100%;
        height: auto;
        box-sizing: border-box;
        transition: 0.3s ease;
      }
      .navbar.active {
        display: flex;
      }
      .navbar a {
        margin: 1rem;
        color: #000000;
        font-weight:bold;
        text-align: center;
      }
      .icons i {
        display: none;
      }
      .icons .fa-regular.fa-user, .icons .fa-solid.fa-cart-shopping {
        display: inline-block;
      }
        }

        #products {
            padding: 1rem;
            background: #f8f8f8;
        }

        .product-wrapper {
            padding: 20px; 
        }

        .product {
            background: #fff;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            height: 100%; 
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .product:hover {
            transform: scale(1.05);
        }

        .product img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            border-radius: 10px;
        }

        .product h3 {
            font-size: 16px;
            margin: 10px 0;
            font-weight: bold;
        }

        .price {
            font-weight: bold;
            color: #e67e22;
            font-size: 18px;
        }
        .group{
            display:flex;
        }
        .group input{
            margin: 1rem auto;
            width:50%;
        }
        .add-to-cart {
            background: #239a54;
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
            width: 40px;
            height: 40px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 1rem auto;
        }

        .add-to-cart:hover {
            background: #186d3c;
        }
        .contacts{
            display: flex;
            justify-content:center;
            align-items:center;
            padding:2rem;
            width:100%;        }
        .contacts form{
            border:1px solid #ccc;
            padding:1rem;
            display: flex;
            flex-direction: column;
            width:50%;
            height:100%;
            border-radius:10px;
            background-color: #fff;
            box-shadow:10px 10px 8px rgba(0, 0, 0, 0.1)
        }
        .contacts form input, .contacts form textarea{
            padding: 5px;
            margin-bottom:1rem;
            border-radius:5px;
        }
        .contacts form textarea{
            height:200px;
        }
        .contacts form input:hover, .contacts form textarea:hover{
            border:2px solid #558759;
            background-color:rgb(131, 190, 135);
        }
    </style>
</head>
<body>
<header class="header">
    <div class="flex">
      <a href="index.php" class="logo">Flower <span>Shop</span></a>
        
      <div class="icons">
        <i class="fa-solid fa-cart-shopping" href="cart.php"></i>
        <i class="fa-regular fa-user" id="user-btn"></i>
        <i class="fas fa-bars" id="menu-btn"></i>
      </div>
      <div class="user-box" id="user-box">
        <a href="login.php">Sign up now</a>
        <a href="register.php">Register new account</a>
      </div>
    </div>
  </header>
    <h2>The origin <span>of Happiness</span></h2>
    <section id="home">
        <h1>Blossoms of Elegance</h1>
        <h3>Nature's Beauty for You</h3>
        <p>Flowers are more than just petals and stems—they are emotions, memories, and timeless expressions of love. Whether you're celebrating a special moment, sending heartfelt wishes, or simply brightening someone's day, our handpicked floral arrangements bring nature's finest artistry to life.</p>
        <p>At our flower shop, we carefully curate every bouquet with fresh, fragrant blooms, ensuring each arrangement speaks volumes without saying a word. Let the magic of flowers transform your moments into cherished memories, wrapped in beauty and elegance.</p>
    </section>

    <h2 id="about-us">About <span>Us</span></h2>
    <section id="about">
        <div class="about-container">
            <video autoplay loop muted controls>
                <source src="video.mp4" type="video/mp4">
            </video>
            <div class="about-text">
                <h3>Why Choose Us?</h3>
                <p>At <strong>Flower Shop</strong>, we believe that flowers are more than just decorations—they are messengers of love, joy, and heartfelt emotions. Our passion for floristry drives us to create stunning floral arrangements that transform every moment into something truly magical.</p>
                <p>Sourcing our flowers from the finest growers, we guarantee unparalleled freshness, fragrance, and longevity. Whether you're celebrating a birthday, wedding, anniversary, or simply brightening someone's day, our handpicked blooms are designed to leave a lasting impression.</p>
                <p>What sets us apart is our commitment to excellence and personalized service. Our team of expert florists will work closely with you to create a bespoke arrangement that perfectly expresses your feelings.</p>
                <button><a href="login.php">Learn More</a></button>
            </div>
        </div>
    </section>

    <h2 id="product">Our <span>Products</span></h2>
    <section id="products">
        <div class="container">
            <div class="row">
                <?php

                    $select_products = mysqli_query($conn, "SELECT * FROM products") or die("Query failed");
                    if (mysqli_num_rows($select_products) > 0) {
                        while ($row = mysqli_fetch_assoc($select_products)) {
                ?>
                <div class="col-6 col-md-4 col-lg-3 product-wrapper">
                    <div class="product">
                        <img src="image/<?php echo $row['image']; ?>" alt="<?php echo $row['name']; ?>" class="img-fluid">
                        <h3><?php echo $row['name']; ?></h3>
                        <p class="price">$<?php echo $row['price']; ?></p>
                        <p><?php echo $row['product_detail']; ?></p>
                        <div class="group">
                        <input type="number" class="quantity-input" value="1" min="1">
                        <button class="add-to-cart">🛒</button>
                        </div>
                    </div>
                </div>
                <?php } }?>
            </div>
        </div>
    </section>
    <h2 id="contact-us">Contacts <span>Us</span></h2>
    <section class="contacts">
            <form  class="form-control">
                <input type="text" name="name" placeholder="Your Name">
                <input type="email" name="email" placeholder="Your Email">
                <input type="text" name="number" placeholder="Your Phone">
                <textarea name="message" placeholder="Your Message"></textarea>
                <input class="btn btn-success" value="Send message">
                <input class="btn btn-danger" value="Cancel">
            </form>
    </section>
    <footer class="text-light pt-5 pb-3 mt-5" style="background-color:#436B46;">
        <div class="container">
            <div class="row">
            <div class="col-md-4 mb-4">
                <h5>Main Info</h5>
                <ul class="list-unstyled">
                <li><i class="fas fa-map-marker-alt"></i> Address: 123 Main St, Anytown, USA</li>
                <li><i class="fas fa-phone"></i> Phone: 555-555-5555</li>
                <li><i class="fas fa-envelope"></i> Email: <a href="mailto:admin@gmail.com" class="text-light">admin@gmail.com</a></li>
                </ul>
            </div>
            <div class="col-md-4 mb-4">
                <h5>Our Fanpages</h5>
                <ul class="list-unstyled">
                <li><i class="fab fa-facebook"></i> <a href="#" class="text-light">Facebook: Flower in life</a></li>
                <li><i class="fab fa-instagram"></i> <a href="#" class="text-light">Instagram: Flower in life</a></li>
                <li><i class="fab fa-linkedin"></i> <a href="#" class="text-light">LinkedIn: Flower in life</a></li>
                </ul>
            </div>
            <div class="col-md-4 mb-4">
                <h5>Follow Us</h5>
                <div class="d-flex gap-3">
                <a href="#" class="text-light"><i class="fab fa-facebook fa-lg"></i></a>
                <a href="#" class="text-light"><i class="fab fa-instagram fa-lg"></i></a>
                <a href="#" class="text-light"><i class="fab fa-linkedin fa-lg"></i></a>
                </div>
            </div>
            </div>
                <hr class="bg-secondary" />
                <p class="text-center mb-0" style="text-transform: uppercase;">&copy; 2025 Flower Shop. All rights reserved.</p>
            </div>
        </footer>
    <script src="script.js"></script>
</body>
</html>
