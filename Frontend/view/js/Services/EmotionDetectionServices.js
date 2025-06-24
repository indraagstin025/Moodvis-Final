import { API_URL_EMOTION_RECORDS, API_URL_EMOTION_HISTORY_SUMMARY, API_URL_EMOTION_HISTORY_FREQUENCY_TREND, JWT_TOKEN_KEY, API_URL_STUDENT_EMOTION_HISTORY } from "../utils/constants.js";

/**
 * Mengambil token JWT dari localStorage.
 * @returns {string|null} Token JWT atau null jika tidak ada.
 */
function getAuthToken() {
  return localStorage.getItem("jwt_token");
}

/**
 * Fungsi helper terpusat untuk membuat request fetch.
 * @param {string} fullUrl - URL lengkap untuk API endpoint.
 * @param {object} options - Opsi untuk fetch (method, body, dll.)
 * @returns {Promise<object>} Hasil JSON dari API.
 */
async function makeApiRequest(fullUrl, options = {}) {
  // Siapkan header default jika belum ada
  if (!options.headers) {
    options.headers = {};
  }

  // Tambahkan header khusus ngrok
  options.headers["ngrok-skip-browser-warning"] = "true";
  options.headers["Accept"] = "application/json";
  options.headers["Content-Type"] = "application/json";

  // Tambahkan header Authorization jika ada token
  const token = localStorage.getItem("jwt_token");
  if (token) {
    options.headers["Authorization"] = `Bearer ${token}`;
  }

  try {
    const response = await fetch(fullUrl, options);
    const contentType = response.headers.get("content-type");

    if (!response.ok) {
      let errorText = await response.text();
      throw new Error(errorText || `Error: ${response.status} ${response.statusText}`);
    }

    if (contentType && contentType.indexOf("application/json") !== -1) {
      return await response.json();
    } else {
      const textResponse = await response.text();
      console.error("Response bukan JSON:", textResponse);
      throw new Error("Response dari server bukan JSON.");
    }
  } catch (error) {
    console.error(`Kesalahan pada makeApiRequest ke ${fullUrl}:`, error);
    throw error;
  }
}
/**
 * Menyimpan data deteksi emosi baru ke API.
 * @param {object} emotionData - Objek data emosi yang akan dikirim.
 * @returns {Promise<object>} Hasil JSON dari API.
 */
export async function saveEmotionRecord(emotionData) {
  const payload = {
    timestamp: emotionData.timestamp,
    dominant_emotion: emotionData.dominant_emotion,
    happiness_score: emotionData.happiness_score,
    sadness_score: emotionData.sadness_score,
    anger_score: emotionData.anger_score,
    fear_score: emotionData.fear_score,
    disgust_score: emotionData.disgust_score,
    surprise_score: emotionData.surprise_score,
    neutral_score: emotionData.neutral_score,

    gender: emotionData.gender,
    gender_probability: emotionData.gender_probability,
  };

  try {
    const result = await makeApiRequest(API_URL_EMOTION_RECORDS, {
      method: "POST",
      body: JSON.stringify(payload),
    });
    console.log("Data emosi berhasil disimpan:", result);
    return result;
  } catch (error) {
    console.error("Gagal menyimpan data emosi (saveEmotionRecord):", error.message);
    throw new Error(`Gagal menyimpan catatan emosi: ${error.message}`);
  }
}

/**
 * Mengambil daftar riwayat deteksi emosi (dengan paginasi).
 * @param {number} page - Nomor halaman yang ingin diambil.
 * @param {number} perPage - Jumlah item per halaman.
 * @returns {Promise<object>} Hasil JSON dari API (termasuk data paginasi).
 */
export async function fetchEmotionHistory(page = 1, perPage = 15) {
  const queryParams = `?page=${page}&per_page=${perPage}&sort_by=detection_timestamp&sort_order=desc`;
  const fullUrl = `${API_URL_EMOTION_RECORDS}${queryParams}`;

  try {
    const result = await makeApiRequest(fullUrl, { method: "GET" });
    console.log("Riwayat emosi berhasil diambil:", result);
    return result;
  } catch (error) {
    console.error("Gagal mengambil riwayat emosi (fetchEmotionHistory):", error.message);
    throw new Error(`Gagal mengambil riwayat emosi: ${error.message}`);
  }
}

/**
 * Mengambil satu catatan emosi spesifik berdasarkan ID.
 * @param {string|number} recordId - ID dari catatan emosi.
 * @returns {Promise<object>} Hasil JSON dari API (data satu record).
 */
export async function fetchEmotionRecordById(recordId) {
  const fullUrl = `${API_URL_EMOTION_RECORDS}/${recordId}`;
  try {
    const result = await makeApiRequest(fullUrl, { method: "GET" });
    console.log(`Catatan emosi ID ${recordId} berhasil diambil:`, result);
    return result;
  } catch (error) {
    console.error(`Gagal mengambil catatan emosi ID ${recordId} (fetchEmotionRecordById):`, error.message);
    throw new Error(`Gagal mengambil detail catatan emosi: ${error.message}`);
  }
}

/**
 * Menghapus satu catatan emosi spesifik berdasarkan ID.
 * @param {string|number} recordId - ID dari catatan emosi.
 * @returns {Promise<object|null>} Hasil JSON dari API (pesan sukses) atau null jika 204.
 */
export async function deleteEmotionRecordById(recordId) {
  const fullUrl = `${API_URL_EMOTION_RECORDS}/${recordId}`;
  try {
    const result = await makeApiRequest(fullUrl, { method: "DELETE" });
    console.log(`Catatan emosi ID ${recordId} berhasil dihapus:`, result);
    return result;
  } catch (error) {
    console.error(`Gagal menghapus catatan emosi ID ${recordId} (deleteEmotionRecordById):`, error.message);
    throw new Error(`Gagal menghapus catatan emosi: ${error.message}`);
  }
}

/**
 * Mengambil ringkasan rata-rata skor emosi untuk grafik.
 * @param {string} periodType - 'weekly' atau 'monthly'.
 * @param {object} params - Parameter tambahan seperti { date: 'YYYY-MM-DD' } untuk mingguan,
 * atau { year: YYYY, month: MM } untuk bulanan.
 * @returns {Promise<object>} Objek yang berisi summary_emotion dan average_scores.
 */
export async function fetchEmotionSummaryForChart(periodType, params = {}) {
  const queryParams = new URLSearchParams({ period_type: periodType });
  if (periodType === "weekly" && params.date) {
    queryParams.append("date", params.date);
  } else if (periodType === "monthly") {
    if (params.year) queryParams.append("year", params.year);
    if (params.month) queryParams.append("month", params.month);
  }

  const fullUrl = `${API_URL_EMOTION_HISTORY_SUMMARY}?${queryParams.toString()}`;
  try {
    const result = await makeApiRequest(fullUrl, { method: "GET" });
    console.log(`Ringkasan emosi (${periodType}) berhasil diambil:`, result);
    return result;
  } catch (error) {
    console.error(`Gagal mengambil ringkasan emosi (${periodType}) (fetchEmotionSummaryForChart):`, error.message);
    throw new Error(`Gagal mengambil ringkasan emosi: ${error.message}`);
  }
}

/**
 * Mengambil tren frekuensi kemunculan emosi per hari.
 * Fleksibel: bisa untuk user yang login atau untuk user ID spesifik.
 * @param {string} startDate - Tanggal mulai (YYYY-MM-DD).
 * @param {string} endDate - Tanggal selesai (YYYY-MM-DD).
 * @param {number|string|null} userId - ID pengguna spesifik (opsional).
 * @returns {Promise<object>} Data tren untuk charting.
 */
export async function fetchEmotionFrequencyTrend(startDate, endDate, userId = null) {
  let apiUrl;
  if (userId) {
    apiUrl = `${API_URL_STUDENT_EMOTION_HISTORY}/${userId}/emotion-history`;
  } else {
    apiUrl = API_URL_EMOTION_HISTORY_FREQUENCY_TREND;
  }

  const queryParams = new URLSearchParams({
    start_date: startDate,
    end_date: endDate,
  }).toString();

  const fullUrl = `${apiUrl}?${queryParams}`;

  console.log(`Frontend memanggil URL: ${fullUrl}`);

  try {
    const result = await makeApiRequest(fullUrl, { method: "GET" });
    console.log("Tren frekuensi emosi berhasil diambil:", result);
    return result;
  } catch (error) {
    console.error(`Gagal mengambil tren frekuensi (fetchEmotionFrequencyTrend):`, error.message);
    throw new Error(`Gagal mengambil data tren: ${error.message}`);
  }
}
