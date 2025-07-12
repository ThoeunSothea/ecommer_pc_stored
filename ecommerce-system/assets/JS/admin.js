document.addEventListener("DOMContentLoaded", function () {
  initDataTables();

  initModals();

  initImageUpload();
});

function initDataTables() {
  $(".data-table").DataTable({
    responsive: true,
    language: {
      url: "//cdn.datatables.net/plug-ins/1.10.21/i18n/Khmer.json",
    },
  });
}

function initImageUpload() {
  const imageInput = document.getElementById("product-image");
  if (imageInput) {
    imageInput.addEventListener("change", function (e) {
      const reader = new FileReader();
      reader.onload = function (event) {
        document.getElementById("image-preview").src = event.target.result;
      };
      reader.readAsDataURL(e.target.files[0]);
    });
  }
}

document.addEventListener("DOMContentLoaded", () => {
  const deleteButtons = document.querySelectorAll(".btn-delete");

  deleteButtons.forEach((btn) => {
    btn.addEventListener("click", function (e) {
      const confirmed = confirm("តើអ្នកប្រាកដជាចង់លុបផលិតផលនេះមែនទេ?");
      if (!confirmed) {
        e.preventDefault();
      }
    });
  });

  const currentURL = window.location.href;
  const sidebarLinks = document.querySelectorAll(".sidebar a");

  sidebarLinks.forEach((link) => {
    if (currentURL.includes(link.getAttribute("href"))) {
      link.classList.add("active");
    }
  });
});
