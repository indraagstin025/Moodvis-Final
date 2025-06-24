import { createUserByAdmin, getAllUsersByAdmin } from "../../Services/AuthServices.js";
import { createClass, getAllClasses } from "../../Services/ClassServices.js";

/**
 * Menggambar ulang daftar pengguna di dalam tabel.
 * @param {HTMLElement} tableBody - Elemen tbody dari tabel.
 * @param {Array} users - Array data pengguna yang akan ditampilkan.
 * @param {number} startIndex - Indeks awal untuk penomoran.
 */
function renderUserList(tableBody, users, startIndex = 0) {
  if (!users || users.length === 0) {
    tableBody.innerHTML = `<tr><td colspan="5" class="px-6 py-10 text-center text-gray-500">Belum ada pengguna.</td></tr>`;
    return;
  }

  const rowsHtml = users
    .map((user, index) => {
      let roleBadge = `<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">${user.role}</span>`;
      if (user.role === "pengajar") {
        roleBadge = `<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">Pengajar</span>`;
      } else if (user.role === "murid") {
        roleBadge = `<span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Murid</span>`;
      }

      const registrationDate = user.created_at ? new Date(user.created_at).toLocaleDateString("id-ID", { day: "numeric", month: "short", year: "numeric" }) : "N/A";

      return `
            <tr class="hover:bg-green-50 transition-colors duration-150">
                <td class="px-6 py-4 whitespace-nowrap text-gray-700">${startIndex + index + 1}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 h-10 w-10">
                            <img class="h-10 w-10 rounded-full object-cover" src="${user.photo_url || "public/img/default-avatar.png"}" alt="Foto ${user.name}">
                        </div>
                        <div class="ml-4">
                            <div class="font-medium text-gray-900">${user.name}</div>
                            <div class="text-gray-500">${user.email}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">${roleBadge}</td>
                <td class="px-6 py-4 whitespace-nowrap text-gray-500">${registrationDate}</td>
                <td class="px-6 py-4 whitespace-nowrap text-center">
                    </td>
            </tr>
        `;
    })
    .join("");

  tableBody.innerHTML = rowsHtml;
}

/**
 * Menggambar ulang daftar kelas di dalam tabel.
 * @param {HTMLElement} tableBodyElement - Elemen tbody dari tabel kelas.
 * @param {Array} classes - Array data kelas yang akan ditampilkan.
 */
function renderClassTable(tableBodyElement, classes) {
  const emptyMessageElement = document.getElementById("class-list-empty-message");

  if (!classes || classes.length === 0) {
    tableBodyElement.innerHTML = `<tr><td colspan="3" class="px-4 py-6 text-center text-gray-500">Belum ada kelas yang dibuat.</td></tr>`;
    emptyMessageElement.classList.remove("hidden");
    return;
  }

  emptyMessageElement.classList.add("hidden"); // Sembunyikan pesan kosong jika ada kelas

  const rowsHtml = classes
    .map((cls) => {
      const teacherName = cls.teacher?.name ? cls.teacher.name : `<span class="text-gray-400 italic">Belum ada wali kelas</span>`;

      return `
            <tr class="hover:bg-gray-50 transition-colors duration-150">
                <td class="px-4 py-3 text-gray-800 font-medium">${cls.name}</td>
                <td class="px-4 py-3 text-gray-700">${teacherName}</td>
                <td class="px-4 py-3 text-center">
                    <button data-class-id="${cls.id}" class="delete-class-btn text-red-600 hover:text-red-800 transition" title="Hapus Kelas ${cls.name}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>
                    </button>
                    </td>
            </tr>
        `;
    })
    .join("");

  tableBodyElement.innerHTML = rowsHtml;
  // Tambahkan event listener untuk tombol hapus setelah HTML dirender
  tableBodyElement.querySelectorAll(".delete-class-btn").forEach((button) => {
    button.addEventListener("click", (event) => {
      const classId = event.currentTarget.dataset.classId;
      // Panggil fungsi untuk menghapus kelas
      // handleDeleteClass(classId); // Anda perlu membuat fungsi ini
      Toastify({ text: `Fitur hapus untuk kelas ID ${classId} belum diimplementasikan.`, style: { background: "#ef4444" } }).showToast();
    });
  });
}

/**
 * Mengisi dropdown dengan daftar guru (pengguna dengan role 'pengajar').
 * @param {HTMLElement} selectElement - Elemen select yang akan diisi.
 */
async function populateTeachersDropdown(selectElement) {
  if (!selectElement) return;
  try {
    const users = await getAllUsersByAdmin(); // Asumsi ini mengambil semua pengguna
    const teachers = users.filter((user) => user.role === "pengajar");

    selectElement.innerHTML = '<option value="">-- Pilih Wali Kelas (Opsional) --</option>';
    teachers.forEach((teacher) => {
      const option = document.createElement("option");
      option.value = teacher.id;
      option.textContent = teacher.name;
      selectElement.appendChild(option);
    });
  } catch (error) {
    console.error("Gagal memuat daftar pengajar:", error);
    selectElement.innerHTML = '<option value="">-- Gagal memuat pengajar --</option>';
  }
}

// =================================================================================
// SETUP FUNCTIONS (FUNGSI UNTUK INISIALISASI & EVENT LISTENER)
// =================================================================================

async function handleCreateUserSubmit(event) {
  event.preventDefault();
  const form = event.target;
  const button = form.querySelector('button[type="submit"]');
  const role = form.querySelector("#newUserRole").value;

  const newUserData = {
    name: form.querySelector("#newUserName").value,
    email: form.querySelector("#newUserEmail").value,
    password: form.querySelector("#newUserPassword").value,
    role: role,
  };

  if (role === "murid") {
    const classId = form.querySelector("#newUserClass").value;
    if (!classId) {
      Toastify({ text: "Silakan pilih kelas untuk murid.", style: { background: "#ef4444" } }).showToast();
      return;
    }
    newUserData.class_id = classId;
  }

  button.disabled = true;
  button.innerHTML = "Menciptakan...";

  try {
    const result = await createUserByAdmin(newUserData);
    Toastify({ text: `Sukses! Pengguna "${result.user.name}" berhasil dibuat.`, style: { background: "#00b09b" } }).showToast();
    form.reset();
    document.getElementById("class-selection-container").classList.add("hidden"); // Sembunyikan lagi dropdown kelas

    // Refresh daftar pengguna
    setupAdminUserList();
  } catch (error) {
    Toastify({ text: `Error: ${error.message}`, style: { background: "#ef4444" } }).showToast();
  } finally {
    button.disabled = false;
    button.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-2" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            Buat Akun
        `;
  }
}

async function setupAdminUserList() {
  const userListBody = document.getElementById("user-list-body");
  if (!userListBody) return;

  userListBody.innerHTML = `<tr><td colspan="5" class="px-6 py-10 text-center text-gray-500">Memuat data pengguna...</td></tr>`;

  try {
    const users = await getAllUsersByAdmin();
    renderUserList(userListBody, users, 0);
  } catch (error) {
    console.error("Gagal saat memuat daftar pengguna:", error);
    userListBody.innerHTML = `<tr><td colspan="5" class="px-6 py-10 text-center text-red-500">Gagal memuat data pengguna. Silakan refresh.</td></tr>`;
  }
}

async function setupClassManagement() {
  const addBtn = document.getElementById("add-class-btn");
  const classNameInput = document.getElementById("new-class-name");
  const classTeacherSelect = document.getElementById("new-class-teacher"); // Dapatkan elemen select wali kelas
  const errorP = document.getElementById("class-form-error");
  const classListBody = document.getElementById("class-list-body"); // Mengganti listUl menjadi tableBody

  // Populate the teacher dropdown initially
  populateTeachersDropdown(classTeacherSelect);

  const loadClasses = async () => {
    classListBody.innerHTML = `<tr><td colspan="3" class="px-4 py-6 text-center text-gray-500">Memuat...</td></tr>`;
    try {
      const classes = await getAllClasses();
      renderClassTable(classListBody, classes); // Menggunakan renderClassTable
    } catch (error) {
      console.error("Gagal memuat daftar kelas:", error);
      classListBody.innerHTML = `<tr><td colspan="3" class="px-4 py-6 text-center text-red-500">Gagal memuat kelas.</td></tr>`;
    }
  };

  addBtn.addEventListener("click", async () => {
    const newName = classNameInput.value.trim();
    const teacherId = classTeacherSelect.value; // Dapatkan ID guru yang dipilih
    const teacherName = classTeacherSelect.options[classTeacherSelect.selectedIndex].textContent; // Dapatkan nama guru

    if (!newName) {
      errorP.textContent = "Nama kelas tidak boleh kosong.";
      return;
    }
    errorP.textContent = "";
    addBtn.disabled = true;
    addBtn.innerHTML = `
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-2" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            Menyimpan...
        `;

    const classData = { name: newName };
    if (teacherId) {
      classData.teacher_id = teacherId; // Asumsi API Anda menerima teacher_id
    }

    try {
      const newClass = await createClass(classData); // Kirim data guru juga
      classNameInput.value = "";
      classTeacherSelect.value = ""; // Reset dropdown wali kelas
      Toastify({ text: `Kelas "${newClass.name}" berhasil ditambahkan.`, style: { background: "#00b09b" } }).showToast();
      await loadClasses(); // Muat ulang daftar kelas
    } catch (error) {
      console.error("Error creating class:", error);
      errorP.textContent = error.message || "Gagal menambahkan kelas.";
    } finally {
      addBtn.disabled = false;
      addBtn.innerHTML = `
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 5a1 1 0 011 1v3h3a1 1 0 110 2h-3v3a1 1 0 11-2 0v-3H6a1 1 0 110-2h3V6a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                Tambah Kelas
            `;
    }
  });

  loadClasses(); // Panggil saat inisialisasi
}

async function populateClassDropdownForAdmin() {
  const selectElement = document.getElementById("newUserClass");
  if (!selectElement) return;
  try {
    const classes = await getAllClasses();
    selectElement.innerHTML = '<option value="">-- Pilih Kelas --</option>';
    classes.forEach((cls) => {
      const option = document.createElement("option");
      option.value = cls.id;
      option.textContent = cls.name;
      selectElement.appendChild(option);
    });
  } catch (error) {
    console.error("Gagal memuat kelas untuk form admin:", error);
    selectElement.innerHTML = '<option value="">-- Gagal memuat kelas --</option>';
  }
}

export function init() {
  console.log("Inisialisasi modul dashboard ADMIN.");
  const createUserForm = document.getElementById("createUserForm");
  const roleSelect = document.getElementById("newUserRole");
  const classContainer = document.getElementById("class-selection-container");

  if (createUserForm) {
    populateClassDropdownForAdmin();
    if (roleSelect && classContainer) {
      roleSelect.addEventListener("change", (e) => {
        if (e.target.value === "murid") {
          classContainer.classList.remove("hidden");
        } else {
          classContainer.classList.add("hidden");
        }
      });
    }
    createUserForm.addEventListener("submit", handleCreateUserSubmit);
  }

  setupAdminUserList();
  setupClassManagement();
}
