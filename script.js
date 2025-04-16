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
