// មុខងារសម្រាប់ទំព័រផលិតផល
document.addEventListener("DOMContentLoaded", function () {
  initProductSlider();
  initQuantitySelector();
  initWishlist();
});

function initProductSlider() {
  // កូដសម្រាប់ស្លាយរូបផលិតផល
  $(".product-gallery").slick({
    dots: true,
    arrows: false,
    infinite: true,
    speed: 300,
    slidesToShow: 1,
    adaptiveHeight: true,
  });
}

function initQuantitySelector() {
  // កូដសម្រាប់ការជ្រើសរើសចំនួន
  const minusBtns = document.querySelectorAll(".quantity-minus");
  const plusBtns = document.querySelectorAll(".quantity-plus");

  minusBtns.forEach((btn) => {
    btn.addEventListener("click", function () {
      const input = this.nextElementSibling;
      if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
      }
    });
  });

  plusBtns.forEach((btn) => {
    btn.addEventListener("click", function () {
      const input = this.previousElementSibling;
      input.value = parseInt(input.value) + 1;
    });
  });
}
