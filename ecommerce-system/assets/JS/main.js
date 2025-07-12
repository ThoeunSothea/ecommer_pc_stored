document.addEventListener("DOMContentLoaded", () => {
  const CART_KEY = "ecom_cart";
  const WISHLIST_KEY = "ecom_wishlist";

async function updateCartCount() {
  const cart = getCart();
  console.log("Cart:", cart);
  const total = cart.reduce((sum, i) => sum + (i.quantity || 0), 0);
  console.log("Total items:", total);

  const countEls = document.querySelectorAll(".cart-count");
  if (countEls.length === 0) {
    console.warn("No .cart-count elements found!");
  }

  countEls.forEach((el) => {
    el.textContent = total;
    el.setAttribute("aria-label", `នៅក្នុងកន្ត្រក: ${total}`);
  });
}


  // ————— Toast —————————————————————————————————————————
  function showToast(msg, type = "success") {
    const t = document.createElement("div");
    t.className = `toast toast-${type}`;
    t.textContent = msg;
    document.body.appendChild(t);
    setTimeout(() => t.classList.add("toast-show"), 50);
    setTimeout(() => {
      t.classList.remove("toast-show");
      setTimeout(() => t.remove(), 300);
    }, 3050);
  }

  // ————— Add to Cart ————————————————————————————————————
 async function addToCart(id, qty = 1) {
    const cart = getCart();
    const item = cart.find((i) => i.productId == id);
    if (item) item.quantity = qty;
    else cart.push({ productId: id, quantity: qty });
    saveCart(cart);
    showToast("បញ្ចូលកន្ត្រកដោយជោគជ័យ", "success");
  }

  // Attach to any .btn-add-to-cart
  document.querySelectorAll(".btn-add-to-cart").forEach((btn) => {
    btn.addEventListener("click", () => {
      addToCart(
        btn.dataset.productId || btn.getAttribute("data-product-id"),
        1
      );
    });
  });

  // ————— Wishlist ——————————————————————————————————————
  function getWishlist() {
    return JSON.parse(localStorage.getItem(WISHLIST_KEY)) || [];
  }
  function toggleWishlist(id) {
    const wish = getWishlist();
    const idx = wish.indexOf(id);
    if (idx === -1) {
      wish.push(id);
      showToast("បានបន្ថែមចំណូលចិត្ត", "success");
    } else {
      wish.splice(idx, 1);
      showToast("បានដកចេញចំណូលចិត្ត", "info");
    }
    localStorage.setItem(WISHLIST_KEY, JSON.stringify(wish));
    updateWishlistBtn(id);
  }
  function updateWishlistBtn(id) {
    const wish = getWishlist();
    const active = wish.includes(id);
    document.querySelectorAll(`.wishlist-btn[data-id="${id}"]`).forEach((b) => {
      b.innerHTML = active
        ? '<i class="fas fa-heart"></i>'
        : '<i class="far fa-heart"></i>';
    });
  }
  document.querySelectorAll(".btn-wishlist, .wishlist-btn").forEach((b) => {
    const id = b.dataset.productId || b.getAttribute("data-id");
    updateWishlistBtn(id);
    b.addEventListener("click", () => toggleWishlist(id));
  });

  // ————— Quickview ——————————————————————————————————————
  const modal = document.getElementById("quickviewModal");
  function closeModal() {
    modal.classList.remove("active");
    document.body.classList.remove("modal-open");
  }
  async function showQuickView(id) {
    try {
      const res = await fetch(`/api/product.php?id=${id}`);
      const p = await res.json();
      modal.innerHTML = `
        <div class="modal-content">
          <button class="modal-close" aria-label="បិទ">×</button>
          <div class="quickview-container">
            <img src="/assets/images/products/${
              p.image || "no-image.png"
            }" alt="${p.name}" style="max-width:100%;margin-bottom:1em;">
            <h3>${p.name}</h3>
            <p>${p.description || ""}</p>
            <div class="price">
              ${
                p.discount > 0
                  ? `<span class="original-price">${formatKhr(p.price)}</span>
                   <span class="current-price">${formatKhr(
                     p.price * (1 - p.discount / 100)
                   )}</span>`
                  : `<span class="current-price">${formatKhr(p.price)}</span>`
              }
            </div>
            <button class="btn btn-primary add-to-cart" data-product-id="${id}">
              <i class="fas fa-shopping-cart"></i> បន្ថែមកន្ត្រក
            </button>
          </div>
        </div>`;
      document.body.classList.add("modal-open");
      modal.classList.add("active");
      modal.querySelector(".modal-close").onclick = closeModal;
      modal.querySelector(".add-to-cart").onclick = () => {
        addToCart(id, 1);
        closeModal();
      };
    } catch (err) {
      showToast("មិនអាចទាញព័ត៌មានផលិតផល", "error");
    }
  }
  document.querySelectorAll(".btn-quickview, .quickview-btn").forEach((btn) => {
    btn.addEventListener("click", () =>
      showQuickView(btn.dataset.productId || btn.getAttribute("data-id"))
    );
  });
  modal &&
    modal.addEventListener("click", (e) => {
      if (e.target === modal) closeModal();
    });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") closeModal();
  });

  // Utility for formatting KHR
  function formatKhr(val) {
    return new Intl.NumberFormat("km-KH", {
      style: "currency",
      currency: "KHR",
    })
      .format(val)
      .replace("KHR", "៛");
  }

  // ————— Initialize —————————————————————————————————————
  updateCartCount();
});
// JS (assets/js/logo.js)
// Add this right before </body> or in your global script bundle

document.addEventListener("DOMContentLoaded", () => {
  const header = document.querySelector("header");
  if (!header) return;

  const onScroll = () => {
    if (window.scrollY > 50) {
      header.classList.add("scrolled");
    } else {
      header.classList.remove("scrolled");
    }
  };

  window.addEventListener("scroll", onScroll);
  // initialize
  onScroll();

  // Optional: Smooth scroll back to top when logo clicked
  const logoLink = document.querySelector("header .logo a");
  if (logoLink) {
    logoLink.addEventListener("click", (e) => {
      e.preventDefault();
      window.scrollTo({ top: 0, behavior: "smooth" });
      // Or navigate home:
      // window.location.href = logoLink.getAttribute('href');
    });
  }
});

function updateCartBadge(newCount) {
  const badge = document.querySelector(".cart-count");
  if (newCount > 0) {
    if (badge) {
      badge.textContent = newCount;
    } else {
      const cartIcon = document.querySelector(".cart-link");
      const span = document.createElement("span");
      span.className = "cart-count";
      span.textContent = newCount;
      cartIcon.appendChild(span);
    }
  } else {
    if (badge) badge.remove();
  }
}
