import { getLoggedInUser, updateUserProfile, getUserData } from "../Services/AuthServices.js";
import { getAllClasses } from "../Services/ClassServices.js";
import { ensureHttps } from "../utils/constants.js";

document.addEventListener("DOMContentLoaded", () => {
  initializeProfilePage();
  setupImagePreview();
  setupProfileFormSubmit();
});

/**
 * Menginisialisasi halaman profil dengan logika yang lebih tangguh.
 */
async function initializeProfilePage() {
  console.log("--- Memulai Inisialisasi Halaman Profil ---");

  const nameInput = document.getElementById("name");
  const emailInput = document.getElementById("email");
  const profileImagePreview = document.getElementById("profileImagePreview");
  const classSelectElement = document.getElementById("kelas");
  const classWrapper = document.getElementById("class-selection-wrapper");

  let currentUser = null;
  try {
    currentUser = await getLoggedInUser();
    if (!currentUser || !currentUser.id) {
      console.error("PROFILE.JS ERROR: Data pengguna tidak valid setelah getLoggedInUser().");
      currentUser = getUserData();
      if (!currentUser) {
        Swal.fire({
          title: "Sesi Habis",
          text: "Mohon login kembali.",
          icon: "warning",
        }).then(() => (window.location.href = "login.html"));
        return;
      }
    }
    console.log("PROFILE.JS: Data pengguna lengkap setelah getLoggedInUser():", currentUser);
  } catch (error) {
    console.error("PROFILE.JS ERROR: Gagal sinkronisasi data pengguna dari server:", error);
    currentUser = getUserData();
    if (!currentUser) {
      Swal.fire({
        title: "Sesi Habis",
        text: "Mohon login kembali.",
        icon: "warning",
      }).then(() => (window.location.href = "login.html"));
      return;
    }
  }

  // let oldClassId = currentUser.class_id ?? currentUser.proper_class?.id; // Ini tidak lagi diperlukan untuk validasi langsung di event change

  nameInput.value = currentUser.name || "";
  emailInput.value = currentUser.email || "";

  let photoUrl = "/img/default-avatar.jpg";
  if (currentUser.photo_url) {
    const filename = currentUser.photo_url.replace(/^.*[\\/]/, "");
    photoUrl = ensureHttps(`${window.location.origin}/profile/${filename}`);
  } else if (currentUser.photo) {
    photoUrl = ensureHttps(`${window.location.origin}/profile/${currentUser.photo}`);
  }
  profileImagePreview.src = photoUrl;
  profileImagePreview.onerror = function () {
    this.onerror = null;
    this.src = "/img/default-avatar.jpg";
  };

  if (currentUser.role === "murid" && classWrapper) {
    classWrapper.classList.remove("hidden");
    try {
      const classes = await getAllClasses();
      classSelectElement.innerHTML = '<option value="">-- Pilih Kelas Anda --</option>';

      classes.forEach((cls) => {
        const option = document.createElement("option");
        option.value = cls.id;
        option.textContent = cls.name;
        if (currentUser.proper_class?.id == cls.id || currentUser.class_id == cls.id) {
          option.selected = true;
        }
        classSelectElement.appendChild(option);
      });
      console.log("PROFILE.JS: Dropdown kelas berhasil diisi.");

      // =================================================================
      // ===== BAGIAN INI DIHAPUS/DIKOMENTARI =====
      // =================================================================
      // classSelectElement.addEventListener("change", async (event) => {
      //   console.log("EVENT: Dropdown kelas diubah!");
      //   const newClassId = event.target.value;
      //   console.log("DATA: ID Kelas Lama (oldClassId) =", oldClassId);
      //   console.log("DATA: ID Kelas Baru (newClassId) =", newClassId);
      //   console.log("TIPE DATA: typeof oldClassId =", typeof oldClassId, ", typeof newClassId =", typeof newClassId);

      //   if (!newClassId || newClassId == oldClassId) {
      //     console.log("KONDISI: Benar (true). Aksi dihentikan karena kelas sama atau kosong.");
      //     return;
      //   }

      //   console.log("KONDISI: Salah (false). Melanjutkan ke proses joinClass...");

      //   try {
      //     const result = await joinClass(newClassId);
      //     console.log("API SUCCESS: joinClass result:", result);
      //     Toastify({
      //       text: result.message || "Berhasil pindah kelas!",
      //       duration: 3000,
      //       gravity: "top",
      //       position: "center",
      //       style: { background: "linear-gradient(to right, #00b09b, #96c93d)" },
      //     }).showToast();

      //     const updatedUser = await getLoggedInUser();
      //     oldClassId = updatedUser.class_id ?? updatedUser.proper_class?.id;
      //     console.log("UPDATED: oldClassId setelah joinClass =", oldClassId);
      //   } catch (error) {
      //     console.error("API ERROR: joinClass failed:", error);
      //     Toastify({
      //       text: error.message || "Gagal pindah kelas.",
      //       duration: 5000,
      //       gravity: "top",
      //       position: "center",
      //       style: { background: "linear-gradient(to right, #ef4444, #ff8c00)" },
      //     }).showToast();
      //     classSelectElement.value = oldClassId || "";
      //   }
      // });
    } catch (error) {
      console.error("PROFILE.JS ERROR: Gagal mengambil daftar kelas:", error);
      if (classWrapper) classWrapper.innerHTML = '<p class="text-red-500 text-sm">Gagal memuat daftar kelas.</p>';
    }
  }
  console.log("--- Inisialisasi Halaman Profil Selesai ---");
}

/**
 * Mengatur listener untuk pratinjau gambar saat file dipilih.
 */
function setupImagePreview() {
  const photoInput = document.getElementById("photo");
  const profileImagePreview = document.getElementById("profileImagePreview");
  if (photoInput && profileImagePreview) {
    photoInput.addEventListener("change", (event) => {
      const file = event.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = (e) => {
          profileImagePreview.src = e.target.result;
        };
        reader.readAsDataURL(file);
      }
    });
  }
}

/**
 * Mengatur listener untuk submit form profil.
 */
function setupProfileFormSubmit() {
  const profileForm = document.getElementById("profileForm");
  if (!profileForm) return;

  const classSelectElement = document.getElementById("kelas");
  const classErrorElement = document.getElementById("class-id-error");

  if (classSelectElement && classErrorElement) {
    classSelectElement.addEventListener("change", () => {
      classErrorElement.textContent = "";
      classSelectElement.classList.remove("border-red-500");
      classSelectElement.classList.add("border-gray-300");
    });
  }

  profileForm.addEventListener("submit", async (event) => {
    event.preventDefault();

    if (classErrorElement) {
      classErrorElement.textContent = "";
      classSelectElement.classList.remove("border-red-500");
      classSelectElement.classList.add("border-gray-300");
    }

    const currentUser = getUserData();
    if (currentUser && currentUser.role === "murid" && !classSelectElement.value) {
      if (classErrorElement) {
        classErrorElement.textContent = "Anda harus memilih kelas terlebih dahulu.";
        classSelectElement.classList.remove("border-gray-300");
        classSelectElement.classList.add("border-red-500");
      }
      Toastify({
        text: "Validasi Gagal: Silakan pilih kelas Anda.",
        duration: 3000,
        gravity: "top",
        position: "center",
        style: { background: "linear-gradient(to right, #ef4444, #ff8c00)" },
      }).showToast();
      return;
    }

    const submitButton = profileForm.querySelector('button[type="submit"]');
    const originalButtonText = submitButton.innerHTML;
    submitButton.disabled = true;
    submitButton.innerHTML = `
            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            Menyimpan...
        `;

    try {
      const formData = new FormData(profileForm);
      formData.append("_method", "PUT");

      // Penting: Pastikan input 'kelas' memiliki atribut 'name="class_id"'
      // sehingga FormData secara otomatis menyertakan nilainya.
      // Jika tidak ada, Anda perlu menambahkannya secara manual:
      // if (currentUser.role === "murid" && classSelectElement.value) {
      //     formData.append("class_id", classSelectElement.value);
      // }

      const result = await updateUserProfile(formData);

      if (result.user) {
        await getLoggedInUser();
        console.log("PROFILE.JS: localStorage berhasil diupdate setelah update profil.");

        const updatedUserFromStorage = getUserData();
        let updatedPhotoUrl = "/img/default-avatar.jpg";
        if (updatedUserFromStorage.photo_url) {
          const filename = updatedUserFromStorage.photo_url.replace(/^.*[\\/]/, "");
          updatedPhotoUrl = ensureHttps(`${window.location.origin}/profile/${filename}`);
        } else if (updatedUserFromStorage.photo) {
          updatedPhotoUrl = ensureHttps(`${window.location.origin}/profile/${updatedUserFromStorage.photo}`);
        }
        console.log("PROFILE.JS: Set src profileImagePreview:", updatedPhotoUrl);
        profileImagePreview.src = updatedPhotoUrl;
        profileImagePreview.onerror = function () {
          console.warn("PROFILE.JS: Gagal memuat gambar:", this.src);
          this.onerror = null;
          this.src = "/img/default-avatar.jpg";
        };
      }

      Toastify({
        text: result.message || "Profil berhasil diperbarui!",
        duration: 3000,
        gravity: "top",
        position: "center",
        style: { background: "linear-gradient(to right, #00b09b, #96c93d)" },
      }).showToast();
    } catch (error) {
      console.error("PROFILE.JS ERROR: Gagal memperbarui profil:", error);
      if (error.message && error.message.includes("is not defined")) {
        Toastify({
          text: "Terjadi masalah jaringan atau server. Pastikan server berjalan.",
          duration: 5000,
          gravity: "top",
          position: "center",
          style: { background: "linear-gradient(to right, #ef4444, #ff8c00)" },
          close: true,
        }).showToast();
      } else {
        Toastify({ text: error.message || "Terjadi kesalahan. Periksa kembali data Anda.", duration: 5000, gravity: "top", position: "center", close: true, style: { background: "linear-gradient(to right, #ef4444, #ff8c00)" } }).showToast();
      }
    } finally {
      submitButton.disabled = false;
      submitButton.innerHTML = originalButtonText;
    }
  });
}