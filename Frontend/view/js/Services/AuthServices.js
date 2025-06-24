const API_BASE_URL = "/api";

/**
 * Helper function untuk melakukan request API dengan penanganan error dan header yang konsisten.
 */
async function makeApiRequest(fullUrl, options = {}) {
    if (!options.headers) options.headers = {};
    options.headers["ngrok-skip-browser-warning"] = "true";
    options.headers.Accept = "application/json";
    if (!options.headers["Content-Type"] && options.method && options.method !== "GET") {
        options.headers["Content-Type"] = "application/json";
    }
    const token = localStorage.getItem("jwt_token");
    if (token) {
        options.headers["Authorization"] = `Bearer ${token}`;
    }
    try {
        const response = await fetch(fullUrl, options);
        console.log(`DEBUG-makeApiRequest: Response status for ${fullUrl}:`, response.status, response.statusText); // LOG STATUS
        console.log(`DEBUG-makeApiRequest: Response headers for ${fullUrl}:`, Array.from(response.headers.entries())); // LOG SEMUA HEADERS

        const contentType = response.headers.get("content-type");
        console.log(`DEBUG-makeApiRequest: Content-Type header for ${fullUrl}:`, contentType); // LOG CONTENT-TYPE

        if (!response.ok) {
            let errorText = await response.text();
            console.error(`DEBUG-makeApiRequest: Error response body for ${fullUrl}:`, errorText); // LOG BODY ERROR
            throw new Error(errorText || `Error: ${response.status} ${response.statusText}`);
        }

        // Ambil body respons sebagai teks dulu untuk debugging
        const textResponse = await response.text();
        console.log(`DEBUG-makeApiRequest: Raw response body for ${fullUrl}:`, textResponse); // LOG RAW BODY

        if (contentType && contentType.indexOf("application/json") !== -1) {
            try {
                const jsonResponse = JSON.parse(textResponse); // Coba parse manual
                console.log(`DEBUG-makeApiRequest: JSON response body (parsed) for ${fullUrl}:`, jsonResponse); // LOG PARSED JSON
                return jsonResponse;
            } catch (jsonError) {
                console.error(`DEBUG-makeApiRequest: Gagal parsing JSON untuk ${fullUrl}. Body: ${textResponse}`, jsonError); // LOG JSON PARSE ERROR
                throw new Error("Gagal mem-parse JSON response dari server.");
            }
        } else {
            console.error("DEBUG-makeApiRequest: Response bukan JSON (Content-Type mismatch). Body:", textResponse); // LOG CONTENT-TYPE MISMATCH
            throw new Error("Response dari server bukan JSON.");
        }
    } catch (error) {
        console.error(`DEBUG-makeApiRequest: Kesalahan fatal pada makeApiRequest ke ${fullUrl}:`, error);
        throw error;
    }
}

/**
 * Mengirim data registrasi pengguna ke API.
 */
export async function registerUser(name, email, password, passwordConfirmation) {
    const formData = {
        name: name,
        email: email,
        password: password,
        password_confirmation: passwordConfirmation,
    };
    return makeApiRequest(`${API_BASE_URL}/register`, {
        method: "POST",
        body: JSON.stringify(formData),
    });
}

/**
 * Mengirim data login pengguna ke API.
 * Setelah login berhasil, panggil /me untuk mendapatkan data lengkap dan simpan.
 */
export async function loginUser(email, password) {
    const formData = { email, password };
    const result = await makeApiRequest(`${API_BASE_URL}/login`, {
        method: "POST",
        body: JSON.stringify(formData),
    });

    if (result.token) {
        localStorage.setItem("jwt_token", result.token); // Simpan token
        console.log("DEBUG-AuthServices: Token berhasil disimpan dari login response.");

        try {
            // PENTING: Panggil API /me untuk mendapatkan data user LENGKAP (termasuk proper_class)
            const fullUserDataResponse = await makeApiRequest(`${API_BASE_URL}/me`, { method: "GET" });
            
            // Asumsi API /me mengembalikan objek user langsung, atau { user: {...} }
            // Berdasarkan log terakhir, API /me Anda langsung mengembalikan objek user.
            const fullUserData = fullUserDataResponse.user || fullUserDataResponse; // Sesuaikan jika /me langsung user object atau dibungkus 'user'
            
            if (fullUserData && fullUserData.id && fullUserData.role) { // Minimal validasi
                localStorage.setItem("user", JSON.stringify(fullUserData)); // Simpan data user LENGKAP
                console.log("DEBUG-AuthServices: Data user LENGKAP (termasuk proper_class) berhasil diambil dari /me dan disimpan.");
            } else {
                console.error("DEBUG-AuthServices: Data user dari /me tidak valid atau lengkap:", fullUserData);
                // Fallback: simpan user basic dari response login jika /me gagal
                localStorage.setItem("user", JSON.stringify(result.user)); 
            }
        } catch (error) {
            console.error("DEBUG-AuthServices: Gagal mendapatkan data user lengkap dari /me setelah login:", error);
            // Fallback: simpan user basic dari response login jika /me gagal total
            localStorage.setItem("user", JSON.stringify(result.user));
        }
    }
    return result; // Kembalikan hasil asli dari loginUser
}

/**
 * Melakukan logout pengguna dengan meng-invalidate token di backend.
 */
export async function logoutUser() {
    const token = localStorage.getItem("jwt_token");
    try {
        if (token) {
            await makeApiRequest(`${API_BASE_URL}/logout`, {
                method: "POST",
            });
        }
    } catch (error) {
        console.error("Gagal logout di server, token tetap akan dihapus di client:", error);
    } finally {
        localStorage.removeItem("jwt_token");
        localStorage.removeItem("user");
        window.location.href = "login.html"; // Redirect ke halaman login
    }
}

/**
 * Mengambil data pengguna yang sedang login dari API /me.
 * Ini bisa digunakan untuk refresh data user kapan pun diperlukan.
 */
export async function getLoggedInUser() {
    try {
        console.log("DEBUG-AuthServices: Memulai panggilan makeApiRequest ke /me..."); // Tambahkan ini
        const fullApiResult = await makeApiRequest(`${API_BASE_URL}/me`, { method: "GET" });
        console.log("DEBUG-AuthServices: makeApiRequest ke /me selesai. Respon mentah:", fullApiResult); // Tambahkan ini
        
        // Asumsi /me selalu mengembalikan { "user": { ... } } atau langsung objek user.
        // Kita akan membuat ini adaptif untuk log debugging.
        const currentUserData = fullApiResult.user || fullApiResult; 

        if (currentUserData && currentUserData.id && currentUserData.role) {
            localStorage.setItem("user", JSON.stringify(currentUserData));
            console.log("DEBUG-AuthServices: getLoggedInUser berhasil update localStorage dengan data lengkap.");
            return currentUserData;
        } else {
            console.error("DEBUG-AuthServices: Data dari /me tidak valid di getLoggedInUser (tidak lengkap):", currentUserData);
            // Tambahkan Toastify untuk notifikasi user
            Toastify({
                text: "Data pengguna tidak valid. Mohon login kembali.",
                duration: 5000,
                gravity: "top",
                position: "center",
                style: { background: "linear-gradient(to right, #ef4444, #ff8c00)" },
            }).showToast();
            return null;
        }
    } catch (error) {
        console.error("DEBUG-AuthServices: EXCEPTION di getLoggedInUser:", error); // Tangkap dan log exception
        // Tambahkan Toastify untuk notifikasi error jaringan/API
        Toastify({
            text: error.message || "Gagal terhubung ke server. Mohon coba lagi.",
            duration: 5000,
            gravity: "top",
            position: "center",
            style: { background: "linear-gradient(to right, #ef4444, #ff8c00)" },
        }).showToast();
        return null;
    }
}

/**
 * Mengirim data pembaruan profil pengguna ke API.
 */
export async function updateUserProfile(formData) {
    const token = localStorage.getItem("jwt_token");
    if (!token) throw new Error("Tidak ada token autentikasi.");
    
    const headers = {
        "ngrok-skip-browser-warning": "true",
        Accept: "application/json",
        Authorization: `Bearer ${token}`,
    };
    // Untuk FormData, jangan set Content-Type: application/json secara manual
    // Browser akan menanganinya dengan boundary yang benar.

    try {
        const response = await fetch(`${API_BASE_URL}/profile`, {
            method: "POST", // Perhatikan: metode POST untuk update profil? Biasanya PUT/PATCH. Pastikan sesuai API.
            headers,
            body: formData,
        });
        const contentType = response.headers.get("content-type");
        if (!response.ok) {
            let errorText = await response.text();
            throw new Error(errorText || `Error: ${response.status} ${response.statusText}`);
        }
        if (contentType && contentType.indexOf("application/json") !== -1) {
            const result = await response.json();
            // PENTING: Update localStorage dengan data user yang diperbarui
            if (result.user && result.user.id) {
                localStorage.setItem("user", JSON.stringify(result.user));
                console.log("DEBUG-AuthServices: localStorage user diupdate setelah update profile.");
            }
            return result;
        } else {
            const textResponse = await response.text();
            console.error("Response bukan JSON:", textResponse);
            throw new Error("Response dari server bukan JSON.");
        }
    } catch (error) {
        console.error("Error in updateUserProfile:", error);
        throw error;
    }
}

/**
 * Membuat pengguna baru melalui endpoint admin.
 */
export async function createUserByAdmin(userData) {
    return makeApiRequest(`${API_BASE_URL}/admin/users`, {
        method: "POST",
        body: JSON.stringify(userData),
    });
}

/**
 * Mengambil daftar semua pengguna dari endpoint admin.
 */
export async function getAllUsersByAdmin() {
    const result = await makeApiRequest(`${API_BASE_URL}/admin/users`, {
        method: "GET",
    });
    return result.users;
}

/**
 * Mengambil data pengguna dari localStorage.
 * Ini adalah fungsi sinkron yang membaca apa yang ada di localStorage.
 */
export function getUserData() { // Ini tetap sinkron
    const userString = localStorage.getItem("user");
    if (!userString) return null;
    try {
        const user = JSON.parse(userString);
        // Validasi dasar agar tidak mengembalikan objek rusak
        if (user && user.id && user.name && user.role) {
            console.log("DEBUG-AuthServices: Data user berhasil dibaca dari localStorage.");
            return user;
        } else {
            console.warn("DEBUG-AuthServices: Data user di localStorage tidak valid (kurang properti inti). Akan dianggap null.");
            localStorage.removeItem("user"); // Hapus data rusak
            return null;
        }
    } catch (e) {
        console.error("DEBUG-AuthServices: Gagal parse data user dari localStorage:", e);
        localStorage.removeItem("user");
        localStorage.removeItem("jwt_token");
        return null;
    }
}