<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Flower Shop</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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
      width: 18rem;
      background: #fff;
      box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
      border-radius: 5px;
      padding: 1rem;
      display: none;
    }
    .user-box.active {
      display: block;
    }
    .user-box p {
      font-size: 14px;
      color: #555;
      margin: 0.5rem 0;
    }
    .user-box span {
      color: #aa4ff3;
    }
    .logout-btn {
      background: #f4e9ff;
      width: 100%;
      height: 40px;
      border: none;
      border-radius: 5px;
      color: #aa4ff3;
      font-weight: bold;
      cursor: pointer;
      margin-top: 1rem;
    }

    @media(max-width: 991px) {
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
  </style>
</head>
<body>
  <header class="header">
    <div class="flex">
      <a href="index.php" class="logo">Flower <span>Shop</span></a>
      <nav class="navbar" id="navbar">
        <a href="index.php">Home</a>
        <a href="index_about.php">About</a>
        <a href="index_product.php">Products</a>
        <a href="index_contacts.php">Contacts</a>
      </nav>
      <div class="icons">
        <i class="fa-solid fa-cart-shopping"></i>
        <i class="fa-regular fa-user" id="user-btn"></i>
        <i><?php echo $_SESSION['user_name']; ?></i>
        <i class="fas fa-bars" id="menu-btn"></i>
      </div>
      <div class="user-box" id="user-box">
        <p>Username: <span><?php echo $_SESSION['user_name']; ?></span></p>
        <p>Email: <span><?php echo $_SESSION['user_email']; ?></span></p>
        <form method="post">
          <button name="logout" class="logout-btn">Log Out</button>
        </form>
      </div>
    </div>
  </header>

  <script src="script.js"> </script>
</body>
</html>
