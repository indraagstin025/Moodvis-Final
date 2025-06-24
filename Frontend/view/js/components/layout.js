import { logoutUser } from "../Services/AuthServices.js";
import { API_BASE_URL, ensureHttps } from "../utils/constants.js";

function updateNavbarProfileInfo() {
  console.log("===================================");
  console.log("LAYOUT.JS: Memulai update info navbar...");

  const userPhotoElement = document.getElementById("navbar-user-photo");

  if (!userPhotoElement) {
    console.error("LAYOUT.JS: GAGAL! <img id='navbar-user-photo'> tidak ditemukan.");
    return;
  }

  const userString = localStorage.getItem("user");

  if (!userString || userString === "undefined") {
    console.error("LAYOUT.JS: Tidak ada data user di localStorage.");
    return;
  }

  try {
    const user = JSON.parse(userString);
    console.log("LAYOUT.JS: Data user berhasil di-parse:", user);

    let imageUrl = "/img/default-avatar.png"; // default

    if (user.photo) {
      imageUrl = `/profile/${user.photo}`; // Langsung gunakan nama file
      console.log("LAYOUT.JS: Menggunakan photo:", imageUrl);
    } else {
      console.warn("LAYOUT.JS: user.photo kosong, fallback default.");
    }

    userPhotoElement.src = imageUrl;

    userPhotoElement.onerror = function () {
      console.warn("LAYOUT.JS: Gagal memuat gambar profil:", this.src);
      this.onerror = null;
      this.src = "/img/default-avatar.png";
    };

  } catch (error) {
    console.error("LAYOUT.JS: Error saat parsing localStorage user:", error);
  }

  console.log("===================================");
}


document.addEventListener("DOMContentLoaded", function () {
  console.log("Skrip layout.js utama dimuat.");

  updateNavbarProfileInfo();

  const logoutButton = document.getElementById("logoutButton");
  const logoutButtonMobile = document.getElementById("logoutButtonMobile");
  const mobileMenuButton = document.getElementById("mobileMenuButton");
  const mobileMenuEl = document.getElementById("mobileMenu");

  function showLogoutConfirmation() {
    Swal.fire({
      title: "Apakah Anda yakin ingin logout?",
      text: "Anda akan keluar dari sesi ini.",
      icon: "warning",
      showCancelButton: true,
      confirmButtonColor: "#10B981",
      cancelButtonColor: "#EF4444",
      confirmButtonText: "Ya, Logout!",
      cancelButtonText: "Batal",
    }).then((result) => {
      if (result.isConfirmed) {
        logoutUser();
      }
    });
  }

  if (logoutButton) {
    logoutButton.addEventListener("click", showLogoutConfirmation);
  }

  if (logoutButtonMobile) {
    logoutButtonMobile.addEventListener("click", function () {
      if (mobileMenuEl && !mobileMenuEl.classList.contains("hidden")) {
        mobileMenuEl.classList.add("hidden");
      }
      showLogoutConfirmation();
    });
  }

  if (mobileMenuButton && mobileMenuEl) {
    mobileMenuButton.addEventListener("click", () => {
      mobileMenuEl.classList.toggle("hidden");
    });
  }

  document.querySelectorAll("nav a, #mobileMenu a").forEach((anchor) => {
    anchor.addEventListener("click", function (e) {
      const href = this.getAttribute("href");

      if (href && href.startsWith("#") && this.hostname === window.location.hostname && this.pathname === window.location.pathname) {
        e.preventDefault();
        const targetElement = document.querySelector(href);
        if (targetElement) {
          window.scrollTo({ top: targetElement.offsetTop - 70, behavior: "smooth" });
          if (mobileMenuEl && !mobileMenuEl.classList.contains("hidden")) {
            mobileMenuEl.classList.add("hidden");
          }
        }
      } else if (href && !href.startsWith("#")) {
        if (mobileMenuEl && !mobileMenuEl.classList.contains("hidden")) {
          mobileMenuEl.classList.add("hidden");
        }
      }
    });
  });
});
