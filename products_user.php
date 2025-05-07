<div class="logo">🍔 FastFood Express</div>
    <div>
        <a href="index_user.php">Home</a>
        <a href="products_user.php">Products</a>
        <a href="profile.php">Profile</a>
        <a href="about.php">About</a>
        <a href="contact.php">Contact</a>
        <a href="logout.php">Logout</a>
    </div>
</div>

<div class="categories" data-aos="fade-up">
    <button onclick="filterProducts('all')">All</button>
    <button onclick="filterProducts('beverages')">Beverages</button>
    <button onclick="filterProducts('chicken')">Chicken</button>
    <button onclick="filterProducts('burger')">Burger</button>
    <button onclick="filterProducts('desserts_sides')">Desserts & Sides</button>
</div>

<div class="product-grid">
<?php foreach ($products as $product): ?>
    <div class="product-card" data-aos="zoom-in" data-category="<?php echo $product['category']; ?>"
        onclick="showDetails('<?php echo $product['name']; ?>', '<?php echo $product['price']; ?>', '<?php echo $product['image_url']; ?>')">
        <img src="<?php echo $product['image_url']; ?>" alt="<?php echo $product['name']; ?>" onerror="this.onerror=null; this.src='images/default.jpg';">
        <h3><?php echo $product['name']; ?></h3>
        <button class="add-cart-btn" onclick="event.stopPropagation(); addToCart('<?php echo $product['name']; ?>')">Add to Cart</button>
    </div>
<?php endforeach; ?>
</div>

<!-- Modal -->
<div id="productModal" data-aos="fade-up">
    <span class="close-btn" onclick="closeModal()">❌</span>
    <img id="modalImage" src="" alt="">
    <h3 id="modalName"></h3>
    <p>RM<span id="modalPrice"></span></p>
    <div class="quantity-controls">
        <button onclick="decreaseQty()">-</button>
        <input type="text" id="modalQty" value="1" readonly style="width:30px; text-align:center;">
        <button onclick="increaseQty()">+</button>
    </div>
</div>

<script>
function filterProducts(category) {
    var cards = document.querySelectorAll('.product-card');
    cards.forEach(function(card) {
        if (category === 'all' || card.dataset.category === category) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function showDetails(name, price, image) {
    document.getElementById('modalName').innerText = name;
    document.getElementById('modalPrice').innerText = parseFloat(price).toFixed(2);
    document.getElementById('modalImage').src = image;
    document.getElementById('modalQty').value = 1;

    var modal = document.getElementById('productModal');
    modal.style.display = 'block';
    setTimeout(function() {
        modal.classList.add('active');
    }, 10);
}

function closeModal() {
    var modal = document.getElementById('productModal');
    modal.classList.remove('active');
    setTimeout(function() {
        modal.style.display = 'none';
    }, 300);
}

function increaseQty() {
    var input = document.getElementById('modalQty');
    input.value = parseInt(input.value) + 1;
}

function decreaseQty() {
    var input = document.getElementById('modalQty');
    if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
    }
}

function addToCart(productName) {
    console.log("Added to cart:", productName);
    alert("✅ " + productName + " has been added to cart!");
}
</script>

<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script>
  AOS.init({ duration: 1000, once: true });
</script>

</body>
</html>